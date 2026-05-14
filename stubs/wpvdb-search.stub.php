<?php
/**
 * PHPStan stubs for the WPVDB Search plugin API consumed by WPVDB Blocks.
 *
 * @package WPVDB_Blocks
 */

namespace WPVDB_Search;

class Search {
	/**
	 * @return array{mode: string, post_id: int, limit: int, results: array<int, array<string, mixed>>, debug?: array<string, mixed>}|\WP_Error
	 */
	public static function related_to_post( int $post_id, int $limit = 5, array $args = [] ): array|\WP_Error {
		throw new \BadMethodCallException( 'PHPStan stub only.' );
	}
}
