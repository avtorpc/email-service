<?php

namespace App\Tests\Unit\Application\DTO;

use App\Application\DTO\SendEmailCommand;
use PHPUnit\Framework\TestCase;

class SendEmailCommandTest extends TestCase
{
    public function testFromArrayCreatesCorrectly(): void
    {
        $data = [
            'from' => 'sender@example.com',
            'fromName' => 'Sender',
            'to' => 'recipient@example.com',
            'toName' => 'Recipient',
            'subject' => 'Hello',
            'replyTo' => 'reply@example.com',
            'replyToName' => 'Reply',
            'client' => 'auth-service',
            'template' => 'welcome',
            'context' => ['key' => 'value'],
        ];

        $cmd = SendEmailCommand::fromArray($data);

        $this->assertSame('sender@example.com', $cmd->from);
        $this->assertSame('Sender', $cmd->fromName);
        $this->assertSame('recipient@example.com', $cmd->to);
        $this->assertSame('Hello', $cmd->subject);
        $this->assertSame('welcome', $cmd->template);
        $this->assertSame(['key' => 'value'], $cmd->context);
    }

    public function testFromArrayWithDefaults(): void
    {
        $data = [
            'to' => 'user@example.com',
            'template' => 'verification_code',
            'client' => 'verification-service',
            'context' => ['code' => '12345'],
        ];

        $cmd = SendEmailCommand::fromArray($data);

        $this->assertSame('', $cmd->from);
        $this->assertSame('', $cmd->subject);
        $this->assertSame('', $cmd->replyTo);
        $this->assertSame([], $cmd->context['expiry_minutes'] ?? []);
    }

    public function testValidateEmptyTo(): void
    {
        $cmd = new SendEmailCommand('', '', '', '', '', '', '', '', '', []);
        $errors = $cmd->validate();

        $this->assertContains('Некорректный email в поле to', $errors);
        $this->assertContains('template обязателен', $errors);
    }

    public function testValidateInvalidTo(): void
    {
        $cmd = new SendEmailCommand('', '', 'not-email', '', '', '', '', '', 'tpl', []);
        $errors = $cmd->validate();

        $this->assertContains('Некорректный email в поле to', $errors);
    }

    public function testValidateInvalidFrom(): void
    {
        $cmd = new SendEmailCommand('bad-email', '', 'to@example.com', '', 'subj', '', '', '', 'tpl', []);
        $errors = $cmd->validate();

        $this->assertContains('Некорректный email в поле from', $errors);
    }

    public function testValidateOptionalFromEmptyIsAllowed(): void
    {
        $cmd = new SendEmailCommand('', '', 'to@example.com', '', 'subj', '', '', '', 'tpl', []);
        $errors = $cmd->validate();

        $this->assertNotContains('Некорректный email в поле from', $errors);
        $this->assertEmpty($errors);
    }

    public function testValidateSuccess(): void
    {
        $cmd = new SendEmailCommand(
            'from@example.com', 'From', 'to@example.com', 'To',
            'Subject', '', '', 'client', 'template', []
        );

        $this->assertEmpty($cmd->validate());
    }
}
