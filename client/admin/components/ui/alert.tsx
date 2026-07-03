import { cva, type VariantProps } from 'class-variance-authority';
import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

const alertVariants = cva(
	'relative w-full rounded-lg border px-4 py-3 text-sm [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-3.5 [&>svg]:size-4 [&>svg+div]:pl-7',
	{
		variants: {
			variant: {
				default: 'bg-background text-foreground',
				destructive:
					'border-destructive/50 text-destructive [&>svg]:text-destructive',
			},
		},
		defaultVariants: {
			variant: 'default',
		},
	}
);

interface AlertProps
	extends ComponentProps< 'div' >,
		VariantProps< typeof alertVariants > {}

function Alert( { className, variant, ...props }: AlertProps ) {
	return (
		<div
			data-slot="alert"
			role="alert"
			className={ cn( alertVariants( { variant } ), className ) }
			{ ...props }
		/>
	);
}

function AlertTitle( { className, ...props }: ComponentProps< 'div' > ) {
	return (
		<div
			data-slot="alert-title"
			className={ cn( 'mb-1 font-medium leading-none', className ) }
			{ ...props }
		/>
	);
}

function AlertDescription( { className, ...props }: ComponentProps< 'div' > ) {
	return (
		<div
			data-slot="alert-description"
			className={ cn(
				'text-sm [&_ul]:mt-2 [&_ul]:space-y-1',
				className
			) }
			{ ...props }
		/>
	);
}

export { Alert, AlertTitle, AlertDescription };
