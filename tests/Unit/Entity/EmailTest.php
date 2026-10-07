<?php

namespace App\Tests\Unit\Entity;

use App\Domain\Email\ValueObject\EmailAddress;
use App\Domain\Email\ValueObject\EmailBody;
use App\Domain\Email\ValueObject\EmailStatus;
use App\Domain\Email\ValueObject\EmailSubject;
use App\Entity\Email;
use DomainException;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    private function makeEmail(): Email
    {
        return Email::create(
            id: '550e8400-e29b-41d4-a716-446655440000',
            client: 'verification-service',
            template: 'verification_code',
            from: new EmailAddress('noreply@onmi.dev', 'ONMI'),
            to: new EmailAddress('user@example.com', 'User'),
            subject: new EmailSubject('Ваш код'),
            body: new EmailBody('Код 12345', '<p>Код 12345</p>'),
        );
    }

    public function testCreateSetsDraftStatus(): void
    {
        $email = $this->makeEmail();

        $this->assertTrue($email->getStatus()->isDraft());
        $this->assertNull($email->getSentAt());
        $this->assertNull($email->getFailureReason());
    }

    public function testQueueChangesStatusToQueued(): void
    {
        $email = $this->makeEmail();
        $email->queue();

        $this->assertTrue($email->getStatus()->isQueued());
    }

    public function testQueueFromNonDraftThrows(): void
    {
        $this->expectException(DomainException::class);

        $email = $this->makeEmail();
        $email->queue();
        $email->queue(); // второй раз из queued
    }

    public function testMarkAsSentChangesStatusAndSetsSentAt(): void
    {
        $email = $this->makeEmail();
        $email->queue();
        $email->markAsSent();

        $this->assertTrue($email->getStatus()->isSent());
        $this->assertNotNull($email->getSentAt());
    }

    public function testMarkAsSentFromNonQueuedThrows(): void
    {
        $this->expectException(DomainException::class);

        $email = $this->makeEmail();
        $email->markAsSent(); // draft → sent нельзя
    }

    public function testMarkAsFailedChangesStatusAndSetsReason(): void
    {
        $email = $this->makeEmail();
        $email->queue();
        $email->markAsFailed('SMTP timeout');

        $this->assertTrue($email->getStatus()->isFailed());
        $this->assertSame('SMTP timeout', $email->getFailureReason());
    }

    public function testRequeueForRetryFromFailedResetsStatus(): void
    {
        $email = $this->makeEmail();
        $email->queue();
        $email->markAsFailed('error');
        $email->requeueForRetry();

        $this->assertTrue($email->getStatus()->isQueued());
        $this->assertNull($email->getFailureReason());
    }

    public function testRequeueFromNonFailedThrows(): void
    {
        $this->expectException(DomainException::class);

        $email = $this->makeEmail();
        $email->requeueForRetry();
    }

    public function testCannotSendToSelf(): void
    {
        $this->expectException(DomainException::class);

        $addr = new EmailAddress('same@example.com');
        Email::create(
            id: '550e8400-e29b-41d4-a716-446655440001',
            client: 'test',
            template: 'test',
            from: $addr,
            to: $addr,
            subject: new EmailSubject('Test'),
            body: new EmailBody('test', '<p>test</p>'),
        );
    }

    public function testGettersReturnCorrectValues(): void
    {
        $email = $this->makeEmail();

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $email->getId());
        $this->assertSame('verification-service', $email->getClient());
        $this->assertSame('verification_code', $email->getTemplate());
        $this->assertSame('noreply@onmi.dev', $email->getFrom()->getAddress());
        $this->assertSame('user@example.com', $email->getTo()->getAddress());
        $this->assertSame('Ваш код', $email->getSubject()->getValue());
    }
}
