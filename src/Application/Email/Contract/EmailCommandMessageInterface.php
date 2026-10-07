<?php

declare(strict_types=1);

namespace App\Application\Email\Contract;

use DateTimeImmutable;

interface EmailCommandMessageInterface
{
    /**
     * Получаем уникальный номер события
     * @return string
     */
    public function getEventId(): string;

    /**
     * Email получателя
     */
    public function getEmail(): string;

    /**
     * Имя получателя (для Address)
     */
    public function getName(): string;

    /**
     * Тема письма
     */
    public function getSubject(): string;

    /**
     * Ключ/алиас шаблона (до резолва в Twig template path)
     */
    public function getTemplate(): string;

    /**
     * Данные для рендера Twig шаблона
     *
     * @return array<string, mixed>
     */
    public function getTemplateData(): array;


    /**
     * @return DateTimeImmutable|null
     */
    public function getExpiresAt(): ?DateTimeImmutable;
}
