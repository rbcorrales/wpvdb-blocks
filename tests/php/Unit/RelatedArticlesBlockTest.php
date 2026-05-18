<?php
/**
 * Related articles block tests.
 *
 * @package WPVDB_Blocks
 */

declare(strict_types=1);

namespace WPVDB_Blocks\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_Block;
use WPVDB_Blocks\Related_Articles_Block;
use WPVDB_Search\Search;

/**
 * Tests related articles block rendering behavior.
 *
 * @covers \WPVDB_Blocks\Related_Articles_Block
 */
final class RelatedArticlesBlockTest extends TestCase {
	/**
	 * Reset shared test state.
	 */
	protected function setUp(): void {
		$GLOBALS['wpvdb_blocks_test']['current_post_id'] = 0;
		$GLOBALS['wpvdb_blocks_test']['is_admin']        = true;
		$GLOBALS['wpvdb_blocks_test']['can_edit_posts']  = true;
		$GLOBALS['wpvdb_blocks_test']['post_fields']     = [];
		Search::$next_related                            = [ 'results' => [] ];
		Search::$last_call                               = [];
	}

	/**
	 * Test rendering uses context, clamps limits, and escapes markup.
	 *
	 * @covers \WPVDB_Blocks\Related_Articles_Block::render
	 */
	public function test_render_uses_context_post_clamps_limit_and_escapes_markup(): void {
		$GLOBALS['wpvdb_blocks_test']['post_fields'][42] = [
			'post_excerpt' => '<strong>Useful context</strong> for the related article.',
			'post_content' => '',
		];
		Search::$next_related                            = [
			'results' => [
				[
					'post_id' => 42,
					'title'   => '<script>Bad</script> Related title',
					'link'    => 'https://example.test/article?x=<bad>',
					'date'    => '2026-05-17T00:00:00+00:00',
				],
			],
		];

		$html = Related_Articles_Block::render(
			[
				'title'       => '<em>Read next</em>',
				'limit'       => 99,
				'showExcerpt' => true,
			],
			'',
			new WP_Block( [ 'postId' => 7 ] )
		);

		self::assertSame( 7, Search::$last_call['post_id'], 'Render should use the block context post ID.' );
		self::assertSame( Related_Articles_Block::MAX_LIMIT, Search::$last_call['limit'], 'Render should clamp the configured limit.' );
		self::assertSame(
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
			],
			Search::$last_call['args'],
			'Render should request the expected related article fields.'
		);
		self::assertStringContainsString( '&lt;em&gt;Read next&lt;/em&gt;', $html, 'Block titles should be escaped.' );
		self::assertStringContainsString( '&lt;script&gt;Bad&lt;/script&gt; Related title', $html, 'Related article titles should be escaped.' );
		self::assertStringContainsString( 'href="https://example.test/article?x=bad"', $html, 'Related article URLs should be sanitized.' );
		self::assertStringContainsString( 'Useful context for the related article.', $html, 'Related article excerpts should be rendered.' );
		self::assertStringContainsString( 'May 17, 2026', $html, 'Related article dates should be formatted.' );
		self::assertStringNotContainsString( '<script>Bad</script>', $html, 'Raw script tags should not be rendered.' );
	}

	/**
	 * Test excerpt visibility can be disabled.
	 *
	 * @covers \WPVDB_Blocks\Related_Articles_Block::render
	 */
	public function test_render_can_hide_excerpt(): void {
		$GLOBALS['wpvdb_blocks_test']['post_fields'][42] = [
			'post_excerpt' => 'Visible only when excerpts are enabled.',
			'post_content' => '',
		];
		Search::$next_related                            = [
			'results' => [
				[
					'post_id' => 42,
					'title'   => 'Related title',
					'link'    => 'https://example.test/article',
					'date'    => '',
				],
			],
		];

		$html = Related_Articles_Block::render(
			[
				'showExcerpt' => false,
			],
			'',
			new WP_Block( [ 'postId' => 7 ] )
		);

		self::assertStringContainsString( 'href="https://example.test/article"', $html, 'Related articles should still render when excerpts are disabled.' );
		self::assertStringContainsString( 'Related title', $html, 'Related article titles should still render when excerpts are disabled.' );
		self::assertStringNotContainsString( 'Visible only when excerpts are enabled.', $html, 'Disabled excerpts should not render.' );
	}

	/**
	 * Test missing context shows an editor notice.
	 *
	 * @covers \WPVDB_Blocks\Related_Articles_Block::render
	 */
	public function test_render_returns_admin_notice_for_missing_post_context(): void {
		$html = Related_Articles_Block::render( [] );

		self::assertStringContainsString( 'Select a post to preview related articles.', $html, 'Missing post context should render an editor notice.' );
		self::assertStringContainsString( 'wp-block-wpvdb-blocks-related-articles--notice', $html, 'Missing post context should render notice markup.' );
	}

	/**
	 * Test anonymous frontend viewers do not see editor notices.
	 *
	 * @covers \WPVDB_Blocks\Related_Articles_Block::render
	 */
	public function test_render_suppresses_notice_for_anonymous_frontend_viewers(): void {
		$GLOBALS['wpvdb_blocks_test']['is_admin']       = false;
		$GLOBALS['wpvdb_blocks_test']['can_edit_posts'] = false;

		self::assertSame( '', Related_Articles_Block::render( [] ), 'Anonymous frontend viewers should not see editor notices.' );
	}
}
