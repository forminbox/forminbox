import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { ChevronDown, ChevronUp, Plus, Trash2 } from 'lucide-react';

import { createForm, fetchForm, isApiError, updateForm } from './api';
import { Alert, AlertDescription, AlertTitle } from './components/ui/alert';
import { Button } from './components/ui/button';
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from './components/ui/card';
import { Input } from './components/ui/input';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from './components/ui/select';
import { Switch } from './components/ui/switch';
import type { Field, FieldType } from './types';

interface Props {
	formId: number | null;
	onDone: () => void;
}

interface SaveError {
	message: string;
	details: string[];
}

const FIELD_TYPES: Array< { type: FieldType; label: () => string } > = [
	{ type: 'text', label: () => __( 'Text', 'forminbox' ) },
	{ type: 'email', label: () => __( 'Email', 'forminbox' ) },
	{ type: 'textarea', label: () => __( 'Paragraph', 'forminbox' ) },
];

/**
 * Turn a server error code like "fields.0.id_invalid" into a sentence.
 *
 * @param code Error code returned by the REST API.
 */
function describeErrorCode( code: string ): string {
	const fieldMatch = code.match( /^fields\.(\d+)\.(.+)$/ );

	if ( ! fieldMatch ) {
		return code;
	}

	const position = Number( fieldMatch[ 1 ] ) + 1;
	const reasons: Record< string, string > = {
		id_invalid: __(
			'the ID may only use letters, numbers, hyphens and underscores (max 64 characters).',
			'forminbox'
		),
		id_duplicate: __(
			'this ID is already used by another field.',
			'forminbox'
		),
		label_invalid: __(
			'a label is required (max 200 characters).',
			'forminbox'
		),
		type_unknown: __( 'this field type is not supported.', 'forminbox' ),
		required_not_boolean: __(
			'the required flag must be on or off.',
			'forminbox'
		),
		not_an_object: __( 'this field is malformed.', 'forminbox' ),
	};

	const reason = reasons[ fieldMatch[ 2 ] ] ?? fieldMatch[ 2 ];

	/* translators: 1: field position (1-based), 2: reason sentence. */
	return sprintf( __( 'Field %1$d: %2$s', 'forminbox' ), position, reason );
}

function nextFieldId( fields: Field[] ): string {
	let max = 0;

	for ( const field of fields ) {
		const match = field.id.match( /^field_(\d+)$/ );

		if ( match ) {
			max = Math.max( max, Number( match[ 1 ] ) );
		}
	}

	return `field_${ max + 1 }`;
}

export default function FormEditor( { formId, onDone }: Props ) {
	const [ name, setName ] = useState( '' );
	const [ fields, setFields ] = useState< Field[] >( [] );
	const [ loading, setLoading ] = useState( formId !== null );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState< SaveError | null >( null );

	useEffect( () => {
		if ( formId === null ) {
			return;
		}

		let cancelled = false;

		fetchForm( formId )
			.then( ( form ) => {
				if ( ! cancelled ) {
					setName( form.name );
					setFields( form.config.fields );
					setLoading( false );
				}
			} )
			.catch( ( e ) => {
				if ( ! cancelled ) {
					setError( {
						message: isApiError( e )
							? e.message
							: __( 'Could not load the form.', 'forminbox' ),
						details: [],
					} );
					setLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ formId ] );

	const updateField = ( index: number, patch: Partial< Field > ) => {
		setFields( ( current ) =>
			current.map( ( field, i ) =>
				i === index ? { ...field, ...patch } : field
			)
		);
	};

	const addField = ( type: FieldType ) => {
		setFields( ( current ) => [
			...current,
			{ id: nextFieldId( current ), type, label: '', required: false },
		] );
	};

	const removeField = ( index: number ) => {
		setFields( ( current ) => current.filter( ( _, i ) => i !== index ) );
	};

	const moveField = ( index: number, direction: -1 | 1 ) => {
		setFields( ( current ) => {
			const target = index + direction;

			if ( target < 0 || target >= current.length ) {
				return current;
			}

			const next = [ ...current ];

			[ next[ index ], next[ target ] ] = [
				next[ target ],
				next[ index ],
			];

			return next;
		} );
	};

	const onSave = async () => {
		setSaving( true );
		setError( null );

		const payload = { name, config: { version: 1, fields } };

		try {
			if ( formId === null ) {
				await createForm( payload );
			} else {
				await updateForm( formId, payload );
			}

			onDone();
		} catch ( e ) {
			setError( {
				message: isApiError( e )
					? e.message
					: __( 'Could not save the form.', 'forminbox' ),
				details:
					isApiError( e ) && e.data?.errors
						? e.data.errors.map( describeErrorCode )
						: [],
			} );
			setSaving( false );
		}
	};

	if ( loading ) {
		return (
			<p className="text-muted-foreground">
				{ __( 'Loading…', 'forminbox' ) }
			</p>
		);
	}

	return (
		<div className="flex max-w-3xl flex-col gap-6">
			<h2 className="text-lg font-semibold">
				{ formId === null
					? __( 'New form', 'forminbox' )
					: __( 'Edit form', 'forminbox' ) }
			</h2>

			{ error && (
				<Alert variant="destructive">
					<AlertTitle>{ error.message }</AlertTitle>
					{ error.details.length > 0 && (
						<AlertDescription>
							<ul>
								{ error.details.map( ( detail ) => (
									<li key={ detail }>{ detail }</li>
								) ) }
							</ul>
						</AlertDescription>
					) }
				</Alert>
			) }

			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Form details', 'forminbox' ) }</CardTitle>
				</CardHeader>
				<CardContent>
					<div className="flex max-w-sm flex-col gap-2">
						<label
							className="text-sm font-medium"
							htmlFor="forminbox-form-name"
						>
							{ __( 'Form name', 'forminbox' ) }
						</label>
						<Input
							id="forminbox-form-name"
							value={ name }
							maxLength={ 190 }
							onChange={ ( e ) => setName( e.target.value ) }
						/>
					</div>
				</CardContent>
			</Card>

			<Card>
				<CardHeader>
					<CardTitle>{ __( 'Fields', 'forminbox' ) }</CardTitle>
					<CardDescription>
						{ __(
							'Visitors fill these in. The ID names the answer in your inbox — keep it stable once the form is live.',
							'forminbox'
						) }
					</CardDescription>
				</CardHeader>
				<CardContent className="flex flex-col gap-3">
					{ fields.length === 0 && (
						<p className="text-sm text-muted-foreground">
							{ __(
								'No fields yet — add one below.',
								'forminbox'
							) }
						</p>
					) }

					{ fields.map( ( field, index ) => (
						<div
							className="flex flex-wrap items-end gap-3 rounded-lg border p-3"
							key={ index }
						>
							<div className="flex min-w-40 flex-1 flex-col gap-1.5">
								<label
									className="text-xs font-medium text-muted-foreground"
									htmlFor={ `forminbox-field-label-${ index }` }
								>
									{ __( 'Label', 'forminbox' ) }
								</label>
								<Input
									id={ `forminbox-field-label-${ index }` }
									value={ field.label }
									maxLength={ 200 }
									onChange={ ( e ) =>
										updateField( index, {
											label: e.target.value,
										} )
									}
								/>
							</div>
							<div className="flex w-36 flex-col gap-1.5">
								<label
									className="text-xs font-medium text-muted-foreground"
									htmlFor={ `forminbox-field-id-${ index }` }
								>
									{ __( 'ID', 'forminbox' ) }
								</label>
								<Input
									id={ `forminbox-field-id-${ index }` }
									className="font-mono"
									value={ field.id }
									maxLength={ 64 }
									onChange={ ( e ) =>
										updateField( index, {
											id: e.target.value,
										} )
									}
								/>
							</div>
							<div className="flex w-36 flex-col gap-1.5">
								<span className="text-xs font-medium text-muted-foreground">
									{ __( 'Type', 'forminbox' ) }
								</span>
								<Select
									value={ field.type }
									onValueChange={ ( value ) =>
										updateField( index, {
											type: value as FieldType,
										} )
									}
								>
									<SelectTrigger
										aria-label={ __(
											'Field type',
											'forminbox'
										) }
									>
										<SelectValue />
									</SelectTrigger>
									<SelectContent>
										{ FIELD_TYPES.map(
											( { type, label } ) => (
												<SelectItem
													key={ type }
													value={ type }
												>
													{ label() }
												</SelectItem>
											)
										) }
									</SelectContent>
								</Select>
							</div>
							<div className="flex h-9 items-center gap-2">
								<Switch
									id={ `forminbox-field-required-${ index }` }
									checked={ field.required }
									onCheckedChange={ ( checked ) =>
										updateField( index, {
											required: checked,
										} )
									}
								/>
								<label
									className="text-sm"
									htmlFor={ `forminbox-field-required-${ index }` }
								>
									{ __( 'Required', 'forminbox' ) }
								</label>
							</div>
							<div className="flex gap-1">
								<Button
									variant="ghost"
									size="icon"
									disabled={ index === 0 }
									aria-label={ __(
										'Move field up',
										'forminbox'
									) }
									onClick={ () => moveField( index, -1 ) }
								>
									<ChevronUp />
								</Button>
								<Button
									variant="ghost"
									size="icon"
									disabled={ index === fields.length - 1 }
									aria-label={ __(
										'Move field down',
										'forminbox'
									) }
									onClick={ () => moveField( index, 1 ) }
								>
									<ChevronDown />
								</Button>
								<Button
									variant="ghost"
									size="icon"
									aria-label={ __(
										'Remove field',
										'forminbox'
									) }
									onClick={ () => removeField( index ) }
								>
									<Trash2 />
								</Button>
							</div>
						</div>
					) ) }

					<div className="flex gap-2">
						{ FIELD_TYPES.map( ( { type, label } ) => (
							<Button
								key={ type }
								variant="outline"
								size="sm"
								onClick={ () => addField( type ) }
							>
								<Plus />
								{ sprintf(
									/* translators: %s: field type name. */
									__( 'Add %s field', 'forminbox' ),
									label()
								) }
							</Button>
						) ) }
					</div>
				</CardContent>
			</Card>

			<div className="flex gap-2">
				<Button disabled={ saving } onClick={ onSave }>
					{ saving
						? __( 'Saving…', 'forminbox' )
						: __( 'Save form', 'forminbox' ) }
				</Button>
				<Button
					variant="outline"
					disabled={ saving }
					onClick={ onDone }
				>
					{ __( 'Cancel', 'forminbox' ) }
				</Button>
			</div>
		</div>
	);
}
