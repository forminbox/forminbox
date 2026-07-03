<?php
/**
 * Plugin Name:       FormInbox
 * Plugin URI:        https://github.com/forminbox/forminbox
 * Description:       Standalone form and lead management for WordPress — create forms, capture leads with source context, and track follow-up status.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            FormInbox
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       forminbox
 *
 * @package FormInbox
 */

// This file must stay parseable on old PHP versions so the guards below can
// run and fail gracefully; keep modern syntax out of it.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html(
				sprintf(
					/* translators: %s: current PHP version. */
					__( 'FormInbox requires PHP 8.1 or newer. Your site is running PHP %s, so the plugin is inactive.', 'forminbox' ),
					PHP_VERSION
				)
			);
			echo '</p></div>';
		}
	);

	return;
}

define( 'FORMINBOX_VERSION', '0.1.0' );
define( 'FORMINBOX_FILE', __FILE__ );

$forminbox_autoload = __DIR__ . '/vendor/autoload.php';

if ( ! file_exists( $forminbox_autoload ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'FormInbox is missing its autoloader. If you are running from source, run "composer install" in the plugin directory.', 'forminbox' );
			echo '</p></div>';
		}
	);

	return;
}

require $forminbox_autoload;

register_activation_hook( __FILE__, array( \FormInbox\Setup\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \FormInbox\Setup\Deactivator::class, 'deactivate' ) );

\FormInbox\Plugin::boot( __FILE__ );
