<?php
declare(strict_types=1);

namespace FormInbox\Admin;

use FormInbox\Plugin;
use FormInbox\Setup\Capabilities;

/**
 * Registers the FormInbox admin menu page and its assets.
 *
 * The page renders a single mount node; the React admin app (Milestone 1)
 * takes over from there. For Milestone 0 the bundle is a placeholder that
 * proves the build pipeline end to end.
 */
final class Menu {

	public const PAGE_SLUG   = 'forminbox';
	public const HOOK_SUFFIX = 'toplevel_page_forminbox';

	public function __construct( private readonly Plugin $plugin ) {
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenuPage' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	public function addMenuPage(): void {
		add_menu_page(
			__( 'FormInbox', 'forminbox' ),
			__( 'FormInbox', 'forminbox' ),
			Capabilities::MANAGE_FORMS,
			self::PAGE_SLUG,
			array( $this, 'renderPage' ),
			'dashicons-email-alt2',
			26
		);
	}

	public function renderPage(): void {
		echo '<div class="wrap" id="forminbox-wrap">';
		echo '<h1>' . esc_html__( 'FormInbox', 'forminbox' ) . '</h1>';
		echo '<div id="forminbox-admin">' . esc_html__( 'Loading FormInbox…', 'forminbox' ) . '</div>';
		echo '</div>';
	}

	public function enqueueAssets( string $hook_suffix ): void {
		if ( self::HOOK_SUFFIX !== $hook_suffix ) {
			return;
		}

		$asset_file = $this->plugin->dir() . 'build/admin.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'forminbox-admin',
			$this->plugin->url() . 'build/admin.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		if ( file_exists( $this->plugin->dir() . 'build/admin.css' ) ) {
			wp_enqueue_style(
				'forminbox-admin',
				$this->plugin->url() . 'build/admin.css',
				array(),
				$asset['version']
			);
			wp_style_add_data( 'forminbox-admin', 'rtl', 'replace' );
		}
	}
}
