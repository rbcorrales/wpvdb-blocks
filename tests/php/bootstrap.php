<?php
declare(strict_types=1);

namespace {
	define( 'ABSPATH', __DIR__ . '/' );
	define( 'WPVDB_BLOCKS_DIR', dirname( __DIR__, 2 ) );

	$GLOBALS['wpvdb_blocks_test'] = [
		'current_post_id' => 0,
		'is_admin'        => true,
		'can_edit_posts'  => true,
		'post_fields'     => [],
		'date_format'     => 'F j, Y',
	];

	if ( ! class_exists( 'WP_Error' ) ) {
		class WP_Error {
			public function __construct( private readonly string $code = '' ) {}

			public function get_error_code(): string {
				return $this->code;
			}
		}
	}

	if ( ! class_exists( 'WP_Block' ) ) {
		class WP_Block {
			/**
			 * @param array<string, mixed> $context Block context.
			 */
			public function __construct( public array $context = [] ) {}
		}
	}

	function __( string $text, string $domain = 'default' ): string {
		unset( $domain );
		return $text;
	}

	function is_wp_error( mixed $value ): bool {
		return $value instanceof \WP_Error;
	}

	function absint( mixed $value ): int {
		return max( 0, (int) $value );
	}

	function get_the_ID(): int {
		return (int) $GLOBALS['wpvdb_blocks_test']['current_post_id'];
	}

	function is_admin(): bool {
		return (bool) $GLOBALS['wpvdb_blocks_test']['is_admin'];
	}

	function current_user_can( string $capability, mixed ...$args ): bool {
		unset( $capability, $args );
		return (bool) $GLOBALS['wpvdb_blocks_test']['can_edit_posts'];
	}

	function get_block_wrapper_attributes( array $attributes = [] ): string {
		$class = wp_get_block_default_classname( \WPVDB_Blocks\Related_Articles_Block::NAME );
		if ( ! empty( $attributes['class'] ) ) {
			$class .= ' ' . (string) $attributes['class'];
		}

		return 'class="' . esc_attr( trim( $class ) ) . '"';
	}

	function wp_get_block_default_classname( string $name ): string {
		return 'wp-block-' . sanitize_html_class( str_replace( '/', '-', $name ) );
	}

	function sanitize_html_class( string $class ): string {
		return preg_replace( '/[^A-Za-z0-9_-]/', '', $class ) ?? '';
	}

	function esc_attr( mixed $value ): string {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}

	function esc_url( mixed $value ): string {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}

	function esc_html( mixed $value ): string {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}

	function post_password_required( int $post_id ): bool {
		unset( $post_id );
		return false;
	}

	function get_post_field( string $field, int $post_id ): mixed {
		return $GLOBALS['wpvdb_blocks_test']['post_fields'][ $post_id ][ $field ] ?? '';
	}

	function wp_strip_all_tags( string $text ): string {
		return strip_tags( $text );
	}

	function wp_trim_words( string $text, int $num_words = 55, ?string $more = null ): string {
		$words = preg_split( '/\s+/', trim( $text ) );
		$words = false === $words ? [] : array_values( array_filter( $words, static fn ( string $word ): bool => '' !== $word ) );

		if ( count( $words ) <= $num_words ) {
			return implode( ' ', $words );
		}

		return implode( ' ', array_slice( $words, 0, $num_words ) ) . ( $more ?? '...' );
	}

	function wp_date( string $format, int $timestamp ): string {
		return ( new \DateTimeImmutable( '@' . $timestamp ) )
			->setTimezone( new \DateTimeZone( 'UTC' ) )
			->format( $format );
	}

	function get_option( string $name ): mixed {
		if ( 'date_format' === $name ) {
			return $GLOBALS['wpvdb_blocks_test']['date_format'];
		}

		return null;
	}
}

namespace WPVDB_Search {
	class Search {
		public static mixed $next_related = [
			'results' => [],
		];

		/**
		 * @var array<string, mixed>
		 */
		public static array $last_call = [];

		/**
		 * @param array<string, mixed> $args Related args.
		 * @return array<string, mixed>|\WP_Error
		 */
		public static function related_to_post( int $post_id, int $limit = 5, array $args = [] ): array|\WP_Error {
			self::$last_call = [
				'post_id' => $post_id,
				'limit'   => $limit,
				'args'    => $args,
			];

			return self::$next_related;
		}
	}
}

namespace {
	require_once dirname( __DIR__, 2 ) . '/includes/class-related-articles-block.php';
}
