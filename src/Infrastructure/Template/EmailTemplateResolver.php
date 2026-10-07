<?php

declare(strict_types=1);

namespace App\Infrastructure\Template;

use RuntimeException;

final class EmailTemplateResolver
{
    private const MAP = [
        'registration_verification'
        => 'email/verification_code.html.twig',

        'password_reset'
        => 'email/password/password_reset.html.twig',
    ];

    public function resolve(string $template): string
    {
        $temp = 'email/' . $template . '.html.twig';

        return $temp;
    }
}
