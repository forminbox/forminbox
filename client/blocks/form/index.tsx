/**
 * Editor side of the forminbox/form block. Rendering is server-side
 * (the block and the shortcode share one PHP renderer), so this file only
 * provides the form picker and a live preview.
 *
 * Unlike the admin SPA, this runs inside the block editor — matching
 * Gutenberg with @wordpress/components is correct here (ARCHITECTURE §4).
 */
import apiFetch from '@wordpress/api-fetch';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
	Notice,
	PanelBody,
	Placeholder,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

interface FormOption {
	id: number;
	name: string;
}

interface EditProps {
	attributes: { formId: number };
	setAttributes: ( attributes: { formId: number } ) => void;
}

function useForms(): { forms: FormOption[] | null; failed: boolean } {
	const [ forms, setForms ] = useState< FormOption[] | null >( null );
	const [ failed, setFailed ] = useState( false );

	useEffect( () => {
		apiFetch< FormOption[] >( {
			path: '/forminbox/v1/forms?status=active',
		} )
			.then( ( items ) =>
				setForms( items.map( ( { id, name } ) => ( { id, name } ) ) )
			)
			.catch( () => {
				setFailed( true );
				setForms( [] );
			} );
	}, [] );

	return { forms, failed };
}

function formOptions( forms: FormOption[] ) {
	return [
		{ label: __( 'Select a form…', 'forminbox' ), value: '0' },
		...forms.map( ( form ) => ( {
			label: form.name,
			value: String( form.id ),
		} ) ),
	];
}

function Edit( { attributes, setAttributes }: EditProps ) {
	const blockProps = useBlockProps();
	const { forms, failed } = useForms();
	const { formId } = attributes;

	const picker =
		forms === null ? (
			<Spinner />
		) : (
			<SelectControl
				label={ __( 'Form', 'forminbox' ) }
				value={ String( formId ) }
				options={ formOptions( forms ) }
				onChange={ ( value ) =>
					setAttributes( { formId: Number( value ) } )
				}
				__nextHasNoMarginBottom
			/>
		);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Form', 'forminbox' ) }>
					{ picker }
				</PanelBody>
			</InspectorControls>

			{ failed && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Could not load the forms list. You may not have permission to manage FormInbox forms.',
						'forminbox'
					) }
				</Notice>
			) }

			{ formId === 0 ? (
				<Placeholder
					icon="email-alt2"
					label={ __( 'FormInbox Form', 'forminbox' ) }
					instructions={ __(
						'Choose which form to show here.',
						'forminbox'
					) }
				>
					{ picker }
				</Placeholder>
			) : (
				<ServerSideRender
					block="forminbox/form"
					attributes={ { formId } }
				/>
			) }
		</div>
	);
}

// Settings mirror blocks/form/block.json (the server-side source of
// truth); the client copy exists because registerBlockType's types
// require them and older editors do not merge server metadata.
registerBlockType< { formId: number } >( 'forminbox/form', {
	title: __( 'FormInbox Form', 'forminbox' ),
	category: 'widgets',
	attributes: {
		formId: { type: 'number', default: 0 },
	},
	edit: Edit,
	save: () => null,
} );
