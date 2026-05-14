<?php
/**
 * Related articles render template.
 *
 * @package WPVDB_Blocks
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Variables provided by WordPress block rendering.
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Saved block content.
 * @var \WP_Block $block      Block instance.
 */
$markup = \WPVDB_Blocks\Related_Articles_Block::render( $attributes, $content, $block );

echo wp_kses( $markup, \WPVDB_Blocks\Related_Articles_Block::allowed_html() );
