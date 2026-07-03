<?php
/**
 * Integration test bootstrap: boots the WordPress test suite (wp-phpunit)
 * and loads FormInbox as a must-use plugin.
 *
 * Runs inside the wp-env tests container:
 *   npm run test:php
 *
 * @package FormInbox
 */

declare(strict_types=1);

$forminbox_root = dirname( __DIR__, 2 );

require_once $forminbox_root . '/vendor/autoload.php';

$forminbox_wp_phpunit = getenv( 'WP_PHPUNIT__DIR' );

if ( false === $forminbox_wp_phpunit ) {
	$forminbox_wp_phpunit = $forminbox_root . '/vendor/wp-phpunit/wp-phpunit';
}

require_once $forminbox_wp_phpunit . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $forminbox_root ): void {
		require $forminbox_root . '/forminbox.php';
	}
);

require $forminbox_wp_phpunit . '/includes/bootstrap.php';
