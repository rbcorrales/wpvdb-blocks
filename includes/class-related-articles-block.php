<?php
/**
 * Related articles block renderer.
 *
 * @package WPVDB_Blocks
 */

declare(strict_types=1);

namespace WPVDB_Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the related articles block.
 */
class Related_Articles_Block {
	public const string NAME           = 'wpvdb-blocks/related-articles';
	public const int    MAX_LIMIT      = 10;
	public const string REST_NAMESPACE = 'wpvdb-blocks/v1';

	/**
	 * Register hooks for this block.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', self::register_routes( ... ) );
	}

	/**
	 * Register the dynamic block.
	 */
	public static function register(): void {
		register_block_type( WPVDB_BLOCKS_DIR . '/build/related-articles' );
	}

	/**
	 * Register block editor script translations.
	 */
	public static function register_script_translations(): void {
		$handle = 'wpvdb-blocks-related-articles-editor-script';

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		wp_set_script_translations( $handle, 'wpvdb-blocks', WPVDB_BLOCKS_DIR . '/languages' );
	}

	/**
	 * Register editor preview routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/related/(?P<post_id>[\d]+)',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => self::rest_preview( ... ),
				'permission_callback' => self::can_preview( ... ),
				'args'                => [
					'post_id' => [
						'type'     => 'integer',
						'required' => true,
					],
					'limit'   => [
						'type'    => 'integer',
						'default' => 5,
						'minimum' => 1,
						'maximum' => self::MAX_LIMIT,
					],
				],
			]
		);
	}

	/**
	 * Check whether the current user can preview related articles.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return bool
	 */
	public static function can_preview( \WP_REST_Request $request ): bool {
		$post_id = absint( $request['post_id'] );
		return $post_id && current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Return related article data for the editor preview.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_preview( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$items = self::items_for_post(
			absint( $request['post_id'] ),
			(int) $request->get_param( 'limit' ),
			true
		);

		if ( is_wp_error( $items ) ) {
			return $items;
		}

		return rest_ensure_response( [ 'items' => $items ] );
	}

	/**
	 * Render the block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Saved block content.
	 * @param \WP_Block            $block      Block instance.
	 * @return string
	 */
	public static function render( array $attributes, string $content = '', ?\WP_Block $block = null ): string {
		$post_id = self::current_post_id( $block );
		if ( ! $post_id ) {
			return self::editor_notice( __( 'Select a post to preview related articles.', 'wpvdb-blocks' ) );
		}

		$title = trim( (string) ( $attributes['title'] ?? __( 'Related articles', 'wpvdb-blocks' ) ) );
		$items = self::items_for_post(
			$post_id,
			self::limit( $attributes ),
			(bool) ( $attributes['showExcerpt'] ?? true )
		);

		if ( is_wp_error( $items ) ) {
			return self::editor_notice( __( 'Related articles are unavailable.', 'wpvdb-blocks' ) );
		}
		if ( empty( $items ) ) {
			return self::editor_notice( __( 'No related articles found yet.', 'wpvdb-blocks' ) );
		}

		$children = '';
		foreach ( $items as $item ) {
			$children .= '<li class="' . esc_attr( self::element_class( 'item' ) ) . '">';
			$children .= '<a class="' . esc_attr( self::element_class( 'link' ) ) . '" href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['title'] ) . '</a>';
			if ( '' !== $item['date'] ) {
				$children .= '<time class="' . esc_attr( self::element_class( 'date' ) ) . '" datetime="' . esc_attr( $item['date'] ) . '">' . esc_html( $item['display_date'] ) . '</time>';
			}
			if ( '' !== $item['excerpt'] ) {
				$children .= '<p class="' . esc_attr( self::element_class( 'excerpt' ) ) . '">' . esc_html( $item['excerpt'] ) . '</p>';
			}
			$children .= '</li>';
		}

		$html = '<section ' . self::wrapper_attributes() . '>';
		if ( '' !== $title ) {
			$html .= '<h2 class="' . esc_attr( self::element_class( 'title' ) ) . '">' . esc_html( $title ) . '</h2>';
		}
		$html .= '<ul class="' . esc_attr( self::element_class( 'list' ) ) . '">' . $children . '</ul>';
		$html .= '</section>';

		return $html;
	}

	/**
	 * Return the HTML allowed by the block render template.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_html(): array {
		$global_attributes = [
			'class'      => true,
			'id'         => true,
			'style'      => true,
			'aria-label' => true,
		];

		return [
			'section' => $global_attributes,
			'h2'      => $global_attributes,
			'ul'      => $global_attributes,
			'li'      => $global_attributes,
			'a'       => array_merge(
				$global_attributes,
				[
					'href' => true,
				]
			),
			'time'    => array_merge(
				$global_attributes,
				[
					'datetime' => true,
				]
			),
			'p'       => $global_attributes,
		];
	}

	/**
	 * Fetch normalized related articles.
	 *
	 * @param int  $post_id      Source post ID.
	 * @param int  $limit        Max related items.
	 * @param bool $show_excerpt Include excerpts.
	 * @return list<array<string, mixed>>|\WP_Error
	 */
	private static function items_for_post( int $post_id, int $limit, bool $show_excerpt ): array|\WP_Error {
		$result = \WPVDB_Search\Search::related_to_post(
			$post_id,
			max( 1, min( self::MAX_LIMIT, (int) $limit ) ),
			[
				'collapse_by_post' => true,
				'fields'           => [
					'post_id',
					'title',
					'link',
					'date',
					'distance',
					'similarity',
					'matched_chunks',
				],
			]
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$rows  = $result['results'];
		$items = [];

		foreach ( $rows as $row ) {
			$item = self::normalize_item( $row, $show_excerpt );
			if ( ! empty( $item ) ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Normalize a search row for block rendering.
	 *
	 * @param array<string, mixed> $row          Search row.
	 * @param bool                 $show_excerpt Include excerpt.
	 * @return array<string, mixed>
	 */
	private static function normalize_item( array $row, bool $show_excerpt ): array {
		$post_id = absint( $row['post_id'] ?? 0 );
		$url     = (string) ( $row['link'] ?? '' );
		if ( ! $post_id || '' === $url ) {
			return [];
		}

		$date = (string) ( $row['date'] ?? '' );

		return [
			'post_id'      => $post_id,
			'title'        => (string) ( $row['title'] ?? '' ),
			'url'          => $url,
			'date'         => $date,
			'display_date' => self::format_date( $date ),
			'excerpt'      => $show_excerpt ? self::excerpt( $post_id ) : '',
		];
	}

	/**
	 * Resolve the current post ID.
	 *
	 * @param \WP_Block|null $block Block instance.
	 * @return int
	 */
	private static function current_post_id( ?\WP_Block $block ): int {
		if ( $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ) {
			return absint( $block->context['postId'] );
		}

		return absint( get_the_ID() );
	}

	/**
	 * Clamp the requested limit.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return int
	 */
	private static function limit( array $attributes ): int {
		$limit = (int) ( $attributes['limit'] ?? 5 );
		return max( 1, min( self::MAX_LIMIT, $limit ) );
	}

	/**
	 * Build a bounded text excerpt.
	 *
	 * @param int $post_id Related post ID.
	 * @return string
	 */
	private static function excerpt( int $post_id ): string {
		if ( ! $post_id || post_password_required( $post_id ) ) {
			return '';
		}

		$text = get_post_field( 'post_excerpt', $post_id );
		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			$text = get_post_field( 'post_content', $post_id );
		}

		if ( ! is_string( $text ) ) {
			return '';
		}

		return wp_trim_words( wp_strip_all_tags( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ), 28, '...' );
	}

	/**
	 * Build root wrapper attributes.
	 *
	 * @param string $extra_class Optional extra class.
	 * @return string
	 */
	private static function wrapper_attributes( string $extra_class = '' ): string {
		$args = [];
		if ( '' !== $extra_class ) {
			$args['class'] = $extra_class;
		}

		return get_block_wrapper_attributes( $args );
	}

	/**
	 * Build a child element class from the block's default class.
	 *
	 * @param string $element Element name.
	 * @return string
	 */
	private static function element_class( string $element ): string {
		return wp_get_block_default_classname( self::NAME ) . '__' . sanitize_html_class( $element );
	}

	/**
	 * Format an ISO date for display.
	 *
	 * @param string $date ISO date.
	 * @return string
	 */
	private static function format_date( string $date ): string {
		$timestamp = strtotime( $date );
		if ( ! $timestamp ) {
			return '';
		}

		$formatted = wp_date( get_option( 'date_format' ), $timestamp );
		return is_string( $formatted ) ? $formatted : '';
	}

	/**
	 * Return a notice for editor previews.
	 *
	 * @param string $message Notice message.
	 * @return string
	 */
	private static function editor_notice( string $message ): string {
		if ( ! is_admin() && ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		$notice_class = wp_get_block_default_classname( self::NAME ) . '--notice';

		return '<section ' . self::wrapper_attributes( $notice_class ) . '><p class="' . esc_attr( self::element_class( 'notice' ) ) . '">' . esc_html( $message ) . '</p></section>';
	}
}
