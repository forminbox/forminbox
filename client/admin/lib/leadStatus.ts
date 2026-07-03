import { __ } from '@wordpress/i18n';

import type { LeadStatus } from '@/types';

/**
 * Labels and badge styling for the follow-up pipeline. The list itself is
 * owned by the server (LeadStatuses.php) and arrives with REST responses;
 * this maps known statuses to translated labels and falls back to the raw
 * value for anything unknown (forward-compatible with custom statuses).
 *
 * @param status Status value from the REST API.
 */
export function leadStatusLabel( status: string ): string {
	switch ( status ) {
		case 'new':
			return __( 'New', 'forminbox' );
		case 'contacted':
			return __( 'Contacted', 'forminbox' );
		case 'qualified':
			return __( 'Qualified', 'forminbox' );
		case 'won':
			return __( 'Won', 'forminbox' );
		case 'lost':
			return __( 'Lost', 'forminbox' );
		case 'spam':
			return __( 'Spam', 'forminbox' );
		default:
			return status;
	}
}

export function leadStatusBadgeVariant(
	status: LeadStatus
): 'default' | 'secondary' | 'outline' {
	if ( status === 'new' ) {
		return 'default';
	}

	if ( status === 'lost' || status === 'spam' ) {
		return 'outline';
	}

	return 'secondary';
}
