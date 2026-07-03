/**
 * Shapes mirrored from the REST API (src/Http/FormsController.php).
 * Keep in sync with Form::toArray() and FormConfig::toArray().
 */

export type FieldType = 'text' | 'email' | 'textarea';

export interface Field {
	id: string;
	type: FieldType;
	label: string;
	required: boolean;
}

export interface FormConfig {
	version: number;
	fields: Field[];
}

export type FormStatus = 'active' | 'archived';

export interface Form {
	id: number;
	name: string;
	status: FormStatus;
	config: FormConfig;
	created_at: string;
	updated_at: string;
}

export interface FormPayload {
	name: string;
	config: FormConfig;
}

export type LeadStatus =
	| 'new'
	| 'contacted'
	| 'qualified'
	| 'won'
	| 'lost'
	| 'spam';

export interface LeadSummary {
	id: number;
	form_id: number;
	form_name: string;
	status: LeadStatus;
	primary: string;
	submitted_at: string;
}

export interface LeadsPage {
	items: LeadSummary[];
	total: number;
	page: number;
	per_page: number;
	total_pages: number;
	statuses: LeadStatus[];
}

export interface LeadField {
	id: string;
	label: string;
	value: string;
}

export interface LeadNote {
	id: number;
	note: string;
	author: string;
	created_at: string;
}

export interface LeadDetail {
	id: number;
	form_id: number;
	form_name: string;
	status: LeadStatus;
	statuses: LeadStatus[];
	fields: LeadField[];
	context: {
		source_url: string | null;
		source_title: string | null;
		referrer_url: string | null;
		user_agent: string | null;
	};
	submitted_at: string;
	notes: LeadNote[];
}
