<?php
declare(strict_types=1);

namespace FormInbox\Tests\Integration;

use FormInbox\Forms\FieldTypes\FieldTypeRegistry;
use FormInbox\Forms\Form;
use FormInbox\Forms\FormConfig;
use FormInbox\Forms\FormRepository;
use FormInbox\Leads\Lead;
use FormInbox\Plugin;
use FormInbox\Setup\Activator;
use FormInbox\Submissions\SubmissionToken;

final class ShortcodeTest extends FormInboxTestCase {

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
							'id'       => 'name',
							'type'     => 'text',
							'label'    => 'Your name',
							'required' => true,
						),
						array(
							'id'       => 'email',
							'type'     => 'email',
							'label'    => 'Email',
							'required' => false,
						),
					),
				),
				$types
			)
		);

		$_SERVER['REMOTE_ADDR']    = '203.0.113.' . wp_rand( 1, 254 ) . '-' . uniqid();
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	public function tear_down(): void {
		$_POST                     = array();
		$_SERVER['REQUEST_METHOD'] = 'GET';

		parent::tear_down();
	}

	private function render(): string {
		return do_shortcode( sprintf( '[forminbox id="%d"]', $this->form->id ) );
	}

	private function leadCount(): int {
		global $wpdb;

		$table = $this->tables()->leads();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	public function testRendersFormWithFieldsHoneypotAndToken(): void {
		$html = $this->render();

		$this->assertStringContainsString( '<form class="forminbox-form"', $html );
		$this->assertStringContainsString( 'data-forminbox-form="' . $this->form->id . '"', $html );
		$this->assertStringContainsString( 'Your name', $html );
		$this->assertStringContainsString( 'type="email"', $html );
		$this->assertStringContainsString( 'required aria-required="true"', $html );
		$this->assertStringContainsString( 'name="forminbox_website"', $html );
		$this->assertStringContainsString( 'name="forminbox_token"', $html );
		$this->assertStringContainsString( 'name="forminbox_issued_at"', $html );
		$this->assertStringContainsString( 'forminbox/v1/submissions', $html );
	}

	public function testMissingFormRendersNothingForVisitors(): void {
		wp_set_current_user( 0 );

		$this->assertSame( '', do_shortcode( '[forminbox id="999999"]' ) );
	}

	public function testArchivedFormRendersNothingForVisitors(): void {
		wp_set_current_user( 0 );
		$this->forms->archive( $this->form->id );

		$this->assertSame( '', $this->render() );
	}

	public function testNoJsPostStoresLeadAndRendersSuccess(): void {
		$this->preparePost(
			array(
				'name'  => 'Jane Doe',
				'email' => 'jane@example.com',
			)
		);

		$html = $this->render();

		$this->assertSame( 1, $this->leadCount() );
		$this->assertStringContainsString( 'forminbox-success', $html );
		$this->assertStringNotContainsString( '<form', $html );
	}

	public function testNoJsPostWithErrorsRendersInlineErrorsAndKeepsValues(): void {
		$this->preparePost(
			array(
				'name'  => '',
				'email' => 'not-an-email',
			)
		);

		$html = $this->render();

		$this->assertSame( 0, $this->leadCount() );
		$this->assertStringContainsString( 'This field is required.', $html );
		$this->assertStringContainsString( 'Enter a valid email address.', $html );
		$this->assertStringContainsString( 'value="not-an-email"', $html );
		$this->assertStringContainsString( 'aria-invalid="true"', $html );
	}

	public function testNoJsPostEscapesScriptPayloadOnRedisplay(): void {
		$this->preparePost(
			array(
				'name'  => '',
				'email' => 'jane@example.com"><script>alert(1)</script>',
			)
		);

		$html = $this->render();

		$this->assertSame( 0, $this->leadCount() );
		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
	}

	public function testNoJsPostStoresIpHashByDefault(): void {
		$captured = null;

		add_action(
			'forminbox_lead_created',
			static function ( Lead $lead ) use ( &$captured ) {
				$captured = $lead;
			}
		);

		$this->preparePost( array( 'name' => 'Jane Doe' ) );
		$this->render();

		$this->assertInstanceOf( Lead::class, $captured );
		$this->assertNotNull( $captured->ipHash );
	}

	public function testNoJsPostRespectsIpHashPrivacyFilter(): void {
		add_filter( 'forminbox_store_ip_hash', '__return_false' );

		$captured = null;

		$capture = static function ( Lead $lead ) use ( &$captured ) {
			$captured = $lead;
		};

		add_action( 'forminbox_lead_created', $capture );

		$this->preparePost(
			array(
				'name'  => 'Jane Doe',
				'email' => 'jane@example.com',
			)
		);

		$html = $this->render();

		// The submission still succeeds end to end…
		$this->assertSame( 1, $this->leadCount() );
		$this->assertStringContainsString( 'forminbox-success', $html );

		// …but nothing IP-derived was stored.
		$this->assertInstanceOf( Lead::class, $captured );
		$this->assertNull( $captured->ipHash );
		$this->assertSame( 'Jane Doe', $captured->data['name'] );

		// WP_UnitTestCase restores hooks between tests; remove explicitly
		// anyway so this test leaks nothing even outside that safety net.
		remove_filter( 'forminbox_store_ip_hash', '__return_false' );
		remove_action( 'forminbox_lead_created', $capture );
	}

	public function testHoneypotFilledPostRendersRejectionAndStoresNothing(): void {
		$this->preparePost(
			array( 'name' => 'Jane' ),
			array( 'forminbox_website' => 'https://spam.example' )
		);

		$html = $this->render();

		$this->assertSame( 0, $this->leadCount() );
		$this->assertStringContainsString( 'forminbox-message-error', $html );
	}

	/**
	 * @param array<string, string> $fields
	 * @param array<string, string> $extra
	 */
	private function preparePost( array $fields, array $extra = array() ): void {
		$issued_at = time() - SubmissionToken::MIN_AGE_SECONDS - 2;

		$_SERVER['REQUEST_METHOD'] = 'POST';

		$_POST = array_merge(
			array(
				'forminbox_form_id'      => (string) $this->form->id,
				'forminbox_issued_at'    => (string) $issued_at,
				'forminbox_token'        => Plugin::submissionToken()->issue( $this->form->id, $issued_at ),
				'forminbox_source_url'   => 'https://example.com/contact',
				'forminbox_source_title' => 'Contact us',
				'forminbox_fields'       => $fields,
			),
			$extra
		);
	}
}
