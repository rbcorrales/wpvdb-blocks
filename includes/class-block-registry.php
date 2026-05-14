<?php
/**
 * Block registry.
 *
 * @package WPVDB_Blocks
 */

declare(strict_types=1);

namespace WPVDB_Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Registers available WPVDB blocks.
 */
class Block_Registry {
	private const array BLOCKS = [
		Related_Articles_Block::class,
	];

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'init', self::register( ... ) );
		foreach ( self::blocks() as $block_class ) {
			if ( is_string( $block_class ) && method_exists( $block_class, 'init' ) ) {
				$block_class::init();
			}
		}
	}

	/**
	 * Register block metadata.
	 */
	public static function register(): void {
		$build_dir = WPVDB_BLOCKS_DIR . '/build';
		$manifest  = $build_dir . '/blocks-manifest.php';

		if ( file_exists( $manifest ) && function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
			wp_register_block_types_from_metadata_collection( $build_dir, $manifest );
			return;
		}

		if ( file_exists( $manifest ) && function_exists( 'wp_register_block_metadata_collection' ) ) {
			wp_register_block_metadata_collection( $build_dir, $manifest );
		}

		foreach ( self::blocks() as $block_class ) {
			if ( is_string( $block_class ) && method_exists( $block_class, 'register' ) ) {
				$block_class::register();
			}
		}
	}

	/**
	 * Return registered block classes.
	 *
	 * @return array<int|string, mixed>
	 */
	private static function blocks(): array {
		/**
		 * Filters the block classes registered by WPVDB Blocks.
		 *
		 * @param array<int, class-string> $blocks Block class names.
		 */
		return (array) apply_filters( 'wpvdb_blocks_registered_blocks', self::BLOCKS );
	}
}
