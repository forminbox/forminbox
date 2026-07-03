<?php
declare(strict_types=1);

namespace FormInbox\Rendering;

use FormInbox\Forms\Field;
use FormInbox\Forms\Form;
use FormInbox\Submissions\ErrorMessages;
use FormInbox\Submissions\SubmissionHandler;
use FormInbox\Submissions\SubmissionToken;

/**
 * Renders a form's public HTML from its stored config.
 *
 * Rules that must hold forever:
 *  - Every dynamic value is escaped here, at output time. Stored data is
 *    raw visitor input and must never be trusted.
 *  - No framework, no JS requirement: the markup is a plain <form> that
 *    POSTs back to the page it is on. The enhancement script upgrades it
 *    to fetch-based submission when JS is available.
 */
final class FormRenderer {

	public function __construct( private readonly SubmissionToken $token ) {
	}

	public function render( Form $form, RenderState $state, string $action_url, string $endpoint_url ): string {
		if ( $state->success ) {
			return sprintf(
				'<div class="forminbox-form forminbox-success" role="status">%s</div>',
				esc_html__( 'Thanks! Your message has been received.', 'forminbox' )
			);
		}

		$issued_at = time();

		$html  = sprintf(
			'<form class="forminbox-form" method="post" action="%s" data-forminbox-form="%d" data-forminbox-endpoint="%s" data-forminbox-success-text="%s" data-forminbox-error-text="%s">',
			esc_url( $action_url ),
			(int) $form->id,
			esc_url( $endpoint_url ),
			esc_attr__( 'Thanks! Your message has been received.', 'forminbox' ),
			esc_attr__( 'Something went wrong. Please try again.', 'forminbox' )
		);
		$html .= $this->hiddenInputs( $form, $issued_at );
		$html .= $this->honeypot();

		if ( $state->rejected ) {
			$html .= sprintf(
				'<p class="forminbox-message forminbox-message-error" role="alert">%s</p>',
				esc_html__( 'Your submission could not be processed. Please try again.', 'forminbox' )
			);
		}

		foreach ( $form->config->fields as $field ) {
			$html .= $this->field( $form, $field, $state );
		}

		$html .= sprintf(
			'<p class="forminbox-actions"><button type="submit" class="forminbox-submit">%s</button></p>',
			esc_html__( 'Submit', 'forminbox' )
		);
		$html .= '<p class="forminbox-message" data-forminbox-message role="status" hidden></p>';
		$html .= '</form>';

		return $html;
	}

	private function hiddenInputs( Form $form, int $issued_at ): string {
		global $post;

		$source_url   = is_singular() ? (string) get_permalink() : home_url( add_query_arg( array() ) );
		$source_title = $post instanceof \WP_Post ? get_the_title( $post ) : '';

		return sprintf(
			'<input type="hidden" name="forminbox_form_id" value="%d">' .
			'<input type="hidden" name="forminbox_issued_at" value="%d">' .
			'<input type="hidden" name="forminbox_token" value="%s">' .
			'<input type="hidden" name="forminbox_source_url" value="%s">' .
			'<input type="hidden" name="forminbox_source_title" value="%s">',
			(int) $form->id,
			$issued_at,
			esc_attr( $this->token->issue( $form->id, $issued_at ) ),
			esc_attr( $source_url ),
			esc_attr( $source_title )
		);
	}

	private function honeypot(): string {
		// Off-screen, not display:none — some bots skip fields they can
		// detect as hidden. Real assistive tech is kept away via
		// aria-hidden, tabindex and autocomplete.
		return sprintf(
			'<div class="forminbox-hp" aria-hidden="true" style="position:absolute !important;left:-9999px !important;width:1px;height:1px;overflow:hidden;">' .
			'<label>%s<input type="text" name="%s" tabindex="-1" autocomplete="off" value=""></label>' .
			'</div>',
			esc_html__( 'Website', 'forminbox' ),
			esc_attr( SubmissionHandler::HONEYPOT_FIELD )
		);
	}

	private function field( Form $form, Field $field, RenderState $state ): string {
		$input_id = sprintf( 'forminbox-%d-%s', $form->id, $field->id );
		$name     = sprintf( 'forminbox_fields[%s]', $field->id );
		$value    = $state->values[ $field->id ] ?? '';
		$error    = $state->fieldErrors[ $field->id ] ?? null;
		$required = $field->required ? ' required aria-required="true"' : '';
		$invalid  = null !== $error ? ' aria-invalid="true"' : '';

		$label = sprintf(
			'<label for="%s">%s%s</label>',
			esc_attr( $input_id ),
			esc_html( $field->label ),
			$field->required ? ' <span class="forminbox-required" aria-hidden="true">*</span>' : ''
		);

		if ( 'textarea' === $field->type ) {
			$control = sprintf(
				'<textarea id="%s" name="%s" rows="5"%s%s>%s</textarea>',
				esc_attr( $input_id ),
				esc_attr( $name ),
				$required,
				$invalid,
				esc_textarea( $value )
			);
		} else {
			$control = sprintf(
				'<input type="%s" id="%s" name="%s" value="%s"%s%s>',
				'email' === $field->type ? 'email' : 'text',
				esc_attr( $input_id ),
				esc_attr( $name ),
				esc_attr( $value ),
				$required,
				$invalid
			);
		}

		$error_html = sprintf(
			'<p class="forminbox-field-error" data-forminbox-error-for="%s" role="alert"%s>%s</p>',
			esc_attr( $field->id ),
			null === $error ? ' hidden' : '',
			null === $error ? '' : esc_html( ErrorMessages::forCode( $error ) )
		);

		return sprintf(
			'<div class="forminbox-field" data-forminbox-field="%s">%s%s%s</div>',
			esc_attr( $field->id ),
			$label,
			$control,
			$error_html
		);
	}
}
