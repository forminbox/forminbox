<?php
declare(strict_types=1);

namespace FormInbox\Setup;

use FormInbox\Database\Migrator;
use FormInbox\Database\Tables;

/**
 * Runs on plugin uninstall (from uninstall.php).
 *
 * Destructive by design, so it is double-gated: WordPress only calls it on
 * explicit deletion, and it still refuses to touch data unless the site
 * owner opted in via the delete-data option.
 */
final class Uninstaller {

	public const DELETE_DATA_OPTION = 'forminbox_delete_data_on_uninstall';

	public static function uninstall(): void {
		if ( ! get_option( self::DELETE_DATA_OPTION ) ) {
			return;
		}

		global $wpdb;

		$tables = new Tables( $wpdb->prefix );

		foreach ( $tables->all() as $table ) {
			// Table names come from the internal registry, not user input,
			// and DDL cannot be parameterized.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		delete_option( Migrator::OPTION );
		delete_option( self::DELETE_DATA_OPTION );

		Capabilities::revoke();
	}
}
