<?php
declare(strict_types=1);

namespace FormInbox\Tests\Unit\Leads;

use FormInbox\Leads\LeadStatuses;
use PHPUnit\Framework\TestCase;

final class LeadStatusesTest extends TestCase {

	public function testPipelineMatchesThePlannedLifecycle(): void {
		$this->assertSame(
			array( 'new', 'contacted', 'qualified', 'won', 'lost', 'spam' ),
			LeadStatuses::all()
		);
	}

	public function testIsValid(): void {
		$this->assertTrue( LeadStatuses::isValid( 'spam' ) );
		$this->assertTrue( LeadStatuses::isValid( 'new' ) );
		$this->assertFalse( LeadStatuses::isValid( 'bogus' ) );
		$this->assertFalse( LeadStatuses::isValid( '' ) );
	}
}
