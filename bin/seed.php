<?php
declare(strict_types=1);

/**
 * Development fixture seeder. Not shipped (see .distignore) and never
 * loaded by the plugin — run it manually against a dev site:
 *
 *   wp eval-file wp-content/plugins/forminbox/bin/seed.php
 *
 * Creates one "Contact us" form and 35 leads spread across statuses and
 * days, enough to exercise inbox filtering and pagination.
 */

use FormInbox\Forms\FieldTypes\FieldTypeRegistry;
use FormInbox\Forms\FormConfig;
use FormInbox\Forms\FormRepository;
use FormInbox\Database\Tables;
use FormInbox\Leads\LeadStatuses;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Run via: wp eval-file bin/seed.php' . PHP_EOL );
}

global $wpdb;

$tables = new Tables( $wpdb->prefix );
$types  = FieldTypeRegistry::withDefaults();
$forms  = new FormRepository( $wpdb, $tables, $types );

$form = $forms->insert(
	'Contact us (seeded)',
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
					'label'    => 'Email address',
					'required' => true,
				),
				array(
					'id'       => 'message',
					'type'     => 'textarea',
					'label'    => 'How can we help?',
					'required' => false,
				),
			),
		),
		$types
	)
);

$first_names = array( 'Ava', 'Noah', 'Mia', 'Liam', 'Zoe', 'Omar', 'Lena', 'Karim', 'Sara', 'Adam' );
$topics      = array(
	'Interested in a quote for our storefront.',
	'Do you offer maintenance plans?',
	"We'd like a demo next week.",
	'Question about pricing tiers.',
	'Our contact form leads go nowhere — help!',
);
$statuses    = LeadStatuses::all();

for ( $i = 1; $i <= 35; $i++ ) {
	$name  = $first_names[ $i % count( $first_names ) ] . ' Example ' . $i;
	$email = 'lead' . $i . '@example.com';

	$wpdb->insert(
		$tables->leads(),
		array(
			'form_id'      => $form->id,
			'status'       => $statuses[ $i % count( $statuses ) ],
			'data'         => (string) wp_json_encode(
				array(
					'name'    => $name,
					'email'   => $email,
					'message' => $topics[ $i % count( $topics ) ],
				)
			),
			'source_url'   => 'https://example.com/contact',
			'source_title' => 'Contact us',
			'referrer_url' => 0 === $i % 3 ? 'https://google.com/search?q=example' : null,
			'user_agent'   => 'Mozilla/5.0 (seeded)',
			'ip_hash'      => hash( 'sha256', 'seed-' . $i ),
			'submitted_at' => gmdate( 'Y-m-d H:i:s', time() - $i * 3600 ),
		)
	);
}

echo 'Seeded form #' . (int) $form->id . ' with 35 leads.' . PHP_EOL;
