/**
 * WordPress dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { getBlockDefaultClassName, registerBlockType } from '@wordpress/blocks';
import {
	Notice,
	PanelBody,
	RangeControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './editor.scss';
import './style.scss';

const blockClass = getBlockDefaultClassName( metadata.name );

function RelatedArticlesList( { title, showExcerpt, items } ) {
	return (
		<>
			{ title ? (
				<h2 className={ `${ blockClass }__title` }>{ title }</h2>
			) : null }
			<ul className={ `${ blockClass }__list` }>
				{ items.map( ( item ) => (
					<li
						className={ `${ blockClass }__item` }
						key={ item.post_id }
					>
						<span className={ `${ blockClass }__link` }>
							{ item.title }
						</span>
						{ item.display_date ? (
							<time
								className={ `${ blockClass }__date` }
								dateTime={ item.date }
							>
								{ item.display_date }
							</time>
						) : null }
						{ showExcerpt && item.excerpt ? (
							<p className={ `${ blockClass }__excerpt` }>
								{ item.excerpt }
							</p>
						) : null }
					</li>
				) ) }
			</ul>
		</>
	);
}

function Edit( { attributes, context, setAttributes } ) {
	const {
		limit = 5,
		showExcerpt = true,
		title = __( 'Related articles', 'wpvdb-blocks' ),
	} = attributes;
	const postId = context.postId;
	const blockProps = useBlockProps();
	const [ items, setItems ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		if ( ! postId ) {
			setItems( [] );
			setError(
				__(
					'Select a post to preview related articles.',
					'wpvdb-blocks'
				)
			);
			return;
		}

		let isMounted = true;
		setIsLoading( true );
		setError( '' );

		apiFetch( {
			path: addQueryArgs( `/wpvdb-blocks/v1/related/${ postId }`, {
				limit,
			} ),
		} )
			.then( ( response ) => {
				if ( ! isMounted ) {
					return;
				}
				setItems( response.items || [] );
			} )
			.catch( ( apiError ) => {
				if ( ! isMounted ) {
					return;
				}
				setItems( [] );
				setError(
					apiError.message ||
						__(
							'Related articles preview unavailable.',
							'wpvdb-blocks'
						)
				);
			} )
			.finally( () => {
				if ( isMounted ) {
					setIsLoading( false );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [ postId, limit ] );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Related articles settings', 'wpvdb-blocks' ) }
					initialOpen
				>
					<TextControl
						label={ __( 'Title', 'wpvdb-blocks' ) }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					<RangeControl
						label={ __( 'Number of articles', 'wpvdb-blocks' ) }
						value={ limit }
						min={ 1 }
						max={ 10 }
						onChange={ ( value ) =>
							setAttributes( { limit: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show excerpts', 'wpvdb-blocks' ) }
						checked={ showExcerpt }
						onChange={ ( value ) =>
							setAttributes( { showExcerpt: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<section { ...blockProps }>
				{ isLoading ? <Spinner /> : null }
				{ ! isLoading && error ? (
					<Notice status="warning" isDismissible={ false }>
						{ error }
					</Notice>
				) : null }
				{ ! isLoading && ! error && items.length ? (
					<RelatedArticlesList
						title={ title }
						showExcerpt={ showExcerpt }
						items={ items }
					/>
				) : null }
				{ ! isLoading && ! error && ! items.length ? (
					<p className={ `${ blockClass }__notice` }>
						{ __(
							'No related articles found yet.',
							'wpvdb-blocks'
						) }
					</p>
				) : null }
			</section>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
