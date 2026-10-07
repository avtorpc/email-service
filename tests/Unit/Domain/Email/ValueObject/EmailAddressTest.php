<?php

namespace App\Tests\Unit\Domain\Email\ValueObject;

use App\Domain\Email\EmailSendingException;
use App\Domain\Email\ValueObject\EmailAddress;
use PHPUnit\Framework\TestCase;

class EmailAddressTest extends TestCase
{
    public function testValidEmailCreatesSuccessfully(): void
    {
        $address = new EmailAddress('test@example.com', 'Test User');

        $this->assertSame('test@example.com', $address->getAddress());
        $this->assertSame('Test User', $address->getName());
    }

    public function testValidEmailWithoutName(): void
    {
        $address = new EmailAddress('noreply@onmi.dev');

        $this->assertSame('noreply@onmi.dev', $address->getAddress());
        $this->assertSame('', $address->getName());
    }

    public function testInvalidEmailThrowsException(): void
    {
        $this->expectException(EmailSendingException::class);
        new EmailAddress('not-an-email');
    }

    public function testEqualsSameAddress(): void
    {
        $a = new EmailAddress('a@example.com', 'A');
        $b = new EmailAddress('a@example.com', 'B');

        $this->assertTrue($a->equals($b));
    }

    public function testNotEqualsDifferentAddress(): void
    {
        $a = new EmailAddress('a@example.com');
        $b = new EmailAddress('b@example.com');

        $this->assertFalse($a->equals($b));
    }

    public function testToStringWithName(): void
    {
        $address = new EmailAddress('test@example.com', 'Test');
        $this->assertSame('Test <test@example.com>', (string) $address);
    }

    public function testToStringWithoutName(): void
    {
        $address = new EmailAddress('test@example.com');
        $this->assertSame('test@example.com', (string) $address);
    }
}
