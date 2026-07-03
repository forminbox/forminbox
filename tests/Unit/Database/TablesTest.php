<?php
declare(strict_types=1);

namespace FormInbox\Tests\Unit\Database;

use FormInbox\Database\Tables;
use PHPUnit\Framework\TestCase;

final class TablesTest extends TestCase {

	public function testTableNamesUseSitePrefixAndPluginPrefix(): void {
		$tables = new Tables( 'wp_' );

		$this->assertSame( 'wp_forminbox_forms', $tables->forms() );
		$this->assertSame( 'wp_forminbox_leads', $tables->leads() );
		$this->assertSame( 'wp_forminbox_lead_notes', $tables->leadNotes() );
	}

	public function testMultisiteStylePrefixIsRespected(): void {
		$tables = new Tables( 'wp_3_' );

		$this->assertSame( 'wp_3_forminbox_forms', $tables->forms() );
	}

	public function testAllListsEveryTableChildrenFirst(): void {
		$tables = new Tables( 'wp_' );

		$this->assertSame(
			array( 'wp_forminbox_lead_notes', 'wp_forminbox_leads', 'wp_forminbox_forms' ),
			$tables->all()
		);
	}
}
