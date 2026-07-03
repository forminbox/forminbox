<?php
/**
 * FormInbox uninstall handler.
 *
 * Data is preserved unless the site owner explicitly opted in to deletion
 * (the "delete data on uninstall" setting ships with the settings UI in a
 * later milestone; the option is honored from day one).
 *
 * @package FormInbox
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$forminbox_autoload = __DIR__ . '/vendor/autoload.php';

if ( ! file_exists( $forminbox_autoload ) ) {
	return;
}

require_once $forminbox_autoload;

\FormInbox\Setup\Uninstaller::uninstall();
