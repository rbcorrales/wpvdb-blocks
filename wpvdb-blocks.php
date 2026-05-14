<?php
/**
 * Plugin Name:      WPVDB Blocks
 * Plugin URI:       https://github.com/rbcorrales/wpvdb-blocks
 * Description:      Adds editorial blocks powered by WPVDB Search.
 * Version:          0.1.0
 * Author:           Automattic, Ramon Corrales
 * Author URI:       https://automattic.com/
 * Requires at least: 6.9
 * Requires PHP:     8.3
 * Requires Plugins: wpvdb-search
 * License:          GPL-2.0-or-later
 * License URI:      https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:      wpvdb-blocks
 * Domain Path:      /languages
 *
 * @package WPVDB_Blocks
 */

declare(strict_types=1);

namespace WPVDB_Blocks;

defined( 'ABSPATH' ) || exit;

define( 'WPVDB_BLOCKS_VERSION', '0.1.0' );
define( 'WPVDB_BLOCKS_FILE', __FILE__ );
define( 'WPVDB_BLOCKS_DIR', __DIR__ );

require_once __DIR__ . '/includes/class-related-articles-block.php';
require_once __DIR__ . '/includes/class-block-registry.php';

/**
 * Show an admin notice when wpvdb-search is missing.
 */
function dependency_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'WPVDB Blocks requires the WPVDB Search plugin to be active.', 'wpvdb-blocks' ); ?></p>
	</div>
	<?php
}

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain(
			'wpvdb-blocks',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		if ( ! class_exists( '\WPVDB_Search\Search' ) ) {
			add_action( 'admin_notices', dependency_notice( ... ) );
			return;
		}

		Block_Registry::init();
	}
);
