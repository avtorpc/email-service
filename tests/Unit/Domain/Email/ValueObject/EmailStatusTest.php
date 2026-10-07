<?php

namespace App\Tests\Unit\Domain\Email\ValueObject;

use App\Domain\Email\ValueObject\EmailStatus;
use PHPUnit\Framework\TestCase;

class EmailStatusTest extends TestCase
{
    public function testDraftStatus(): void
    {
        $status = EmailStatus::draft();
        $this->assertSame('draft', $status->getValue());
        $this->assertTrue($status->isDraft());
        $this->assertFalse($status->isQueued());
        $this->assertFalse($status->isSent());
        $this->assertFalse($status->isFailed());
    }

    public function testQueuedStatus(): void
    {
        $status = EmailStatus::queued();
        $this->assertSame('queued', $status->getValue());
        $this->assertTrue($status->isQueued());
    }

    public function testSentStatus(): void
    {
        $status = EmailStatus::sent();
        $this->assertSame('sent', $status->getValue());
        $this->assertTrue($status->isSent());
    }

    public function testFailedStatus(): void
    {
        $status = EmailStatus::failed();
        $this->assertSame('failed', $status->getValue());
        $this->assertTrue($status->isFailed());
    }

    public function testFromString(): void
    {
        $status = EmailStatus::fromString('queued');
        $this->assertTrue($status->isQueued());
    }

    public function testEqualsSameStatus(): void
    {
        $a = EmailStatus::draft();
        $b = EmailStatus::draft();
        $this->assertTrue($a->equals($b));
    }

    public function testNotEqualsDifferentStatus(): void
    {
        $a = EmailStatus::draft();
        $b = EmailStatus::sent();
        $this->assertFalse($a->equals($b));
    }
}
