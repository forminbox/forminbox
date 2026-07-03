<?php
declare(strict_types=1);

namespace FormInbox\Http;

use FormInbox\Forms\FieldTypes\FieldTypeRegistry;
use FormInbox\Forms\Form;
use FormInbox\Forms\FormConfig;
use FormInbox\Forms\FormRepository;
use FormInbox\Forms\FormStatus;
use FormInbox\Setup\Capabilities;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST endpoints for managing forms: forminbox/v1/forms.
 *
 * Admin-only surface. Authentication is WordPress cookie + nonce (the admin
 * SPA sends X-WP-Nonce); authorization is the forminbox_manage_forms
 * capability, checked in an explicit permission_callback on every route.
 */
final class FormsController {

	public const REST_NAMESPACE = 'forminbox/v1';

	public function __construct(
		private readonly FormRepository $forms,
		private readonly FieldTypeRegistry $types,
	) {
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/forms',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'index' ),
					'permission_callback' => array( $this, 'canManageForms' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'canManageForms' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/forms/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => array( $this, 'canManageForms' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'canManageForms' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'archive' ),
					'permission_callback' => array( $this, 'canManageForms' ),
				),
			)
		);
	}

	public function canManageForms(): bool|WP_Error {
		if ( current_user_can( Capabilities::MANAGE_FORMS ) ) {
			return true;
		}

		return new WP_Error(
			'forminbox_forbidden',
			__( 'You are not allowed to manage FormInbox forms.', 'forminbox' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	public function index( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$param  = (string) $request->get_param( 'status' );
		$status = 'all' === $param
			? null
			: ( FormStatus::tryFrom( $param ) ?? FormStatus::Active );

		$forms = array_map(
			static fn ( Form $form ): array => $form->toArray(),
			$this->forms->all( $status )
		);

		return new WP_REST_Response( $forms );
	}

	public function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$form = $this->forms->find( (int) $request->get_param( 'id' ) );

		if ( null === $form ) {
			return $this->notFound();
		}

		return new WP_REST_Response( $form->toArray() );
	}

	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$input = $this->validateInput( $request );

		if ( $input instanceof WP_Error ) {
			return $input;
		}

		[ $name, $config ] = $input;

		$form = $this->forms->insert( $name, $config );

		return new WP_REST_Response( $form->toArray(), 201 );
	}

	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$input = $this->validateInput( $request );

		if ( $input instanceof WP_Error ) {
			return $input;
		}

		[ $name, $config ] = $input;

		$form = $this->forms->update( (int) $request->get_param( 'id' ), $name, $config );

		if ( null === $form ) {
			return $this->notFound();
		}

		return new WP_REST_Response( $form->toArray() );
	}

	public function archive( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (int) $request->get_param( 'id' );

		if ( null === $this->forms->find( $id ) ) {
			return $this->notFound();
		}

		$this->forms->archive( $id );

		return new WP_REST_Response( array( 'archived' => true ) );
	}

	/**
	 * Validate the shared create/update payload: name and config.
	 *
	 * @return array{0: string, 1: FormConfig}|WP_Error
	 */
	private function validateInput( WP_REST_Request $request ): array|WP_Error {
		$name = $request->get_param( 'name' );
		$name = is_string( $name ) ? trim( $name ) : '';

		if ( '' === $name || mb_strlen( $name ) > Form::MAX_NAME ) {
			return new WP_Error(
				'forminbox_invalid_name',
				__( 'Form name must be between 1 and 190 characters.', 'forminbox' ),
				array( 'status' => 400 )
			);
		}

		$raw_config = $request->get_param( 'config' );

		if ( ! is_array( $raw_config ) ) {
			return new WP_Error(
				'forminbox_invalid_config',
				__( 'Form config must be an object.', 'forminbox' ),
				array(
					'status' => 400,
					'errors' => array( 'config.not_an_object' ),
				)
			);
		}

		try {
			$config = FormConfig::fromArray( $raw_config, $this->types );
		} catch ( \FormInbox\Forms\InvalidFormConfig $e ) {
			return new WP_Error(
				'forminbox_invalid_config',
				__( 'Form config failed validation.', 'forminbox' ),
				array(
					'status' => 400,
					'errors' => $e->errors,
				)
			);
		}

		return array( $name, $config );
	}

	private function notFound(): WP_Error {
		return new WP_Error(
			'forminbox_not_found',
			__( 'Form not found.', 'forminbox' ),
			array( 'status' => 404 )
		);
	}
}
