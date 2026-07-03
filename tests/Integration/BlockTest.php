<?php
declare(strict_types=1);

namespace FormInbox\Tests\Integration;

use FormInbox\Forms\FieldTypes\FieldTypeRegistry;
use FormInbox\Forms\Form;
use FormInbox\Forms\FormConfig;
use FormInbox\Forms\FormRepository;
use FormInbox\Setup\Activator;
use WP_Block_Type_Registry;

final class BlockTest extends FormInboxTestCase {

	private FormRepository $forms;

	private Form $form;

	public function set_up(): void {
		parent::set_up();

		Activator::activate();

		global $wpdb;

		$types       = FieldTypeRegistry::withDefaults();
		$this->forms = new FormRepository( $wpdb, $this->tables(), $types );

		$this->form = $this->forms->insert(
			'Contact',
			FormConfig::fromArray(
				array(
					'fields' => array(
						array(
							'id'       => 'email',
							'type'     => 'email',
							'label'    => 'Email',
							'required' => true,
						),
					),
				),
				$types
			)
		);

		wp_set_current_user( 0 );
	}

	private function renderBlock( int $form_id ): string {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( 'forminbox/form' );

		$this->assertNotNull( $block );

		return (string) call_user_func( $block->render_callback, array( 'formId' => $form_id ) );
	}

	/**
	 * The signed token and its timestamp are the only render-to-render
	 * variation; mask them so outputs can be compared.
	 */
	private function normalize( string $html ): string {
		$html = (string) preg_replace( '/name="forminbox_token" value="[^"]*"/', 'name="forminbox_token" value="TOKEN"', $html );

		return (string) preg_replace( '/name="forminbox_issued_at" value="\d+"/', 'name="forminbox_issued_at" value="TIME"', $html );
	}

	public function testBlockIsRegisteredWithFormIdAttribute(): void {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( 'forminbox/form' );

		$this->assertNotNull( $block );
		$this->assertArrayHasKey( 'formId', $block->attributes );
	}

	public function testBlockRendersIdenticallyToShortcode(): void {
		$block_html     = $this->renderBlock( $this->form->id );
		$shortcode_html = do_shortcode( sprintf( '[forminbox id="%d"]', $this->form->id ) );

		$this->assertStringContainsString( '<form class="forminbox-form"', $block_html );
		$this->assertSame( $this->normalize( $shortcode_html ), $this->normalize( $block_html ) );
	}

	public function testMissingFormRendersNothingForVisitors(): void {
		$this->assertSame( '', $this->renderBlock( 999999 ) );
		$this->assertSame( '', $this->renderBlock( 0 ) );
	}

	public function testMissingFormShowsPlaceholderForEditors(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->forms->archive( $this->form->id );

		$html = $this->renderBlock( $this->form->id );

		$this->assertStringContainsString( 'forminbox-placeholder', $html );
		$this->assertStringNotContainsString( '<form', $html );
	}
}
