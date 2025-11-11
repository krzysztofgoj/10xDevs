<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequest
{
    #[Assert\NotBlank(message: 'Email cannot be blank')]
    #[Assert\Email(message: 'Email must be a valid email address')]
    public string $email;

    #[Assert\NotBlank(message: 'Password cannot be blank')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Password must be at least {{ limit }} characters long'
    )]
    public string $password;

    #[Assert\NotBlank(message: 'Password confirmation cannot be blank')]
    #[Assert\IdenticalTo(
        propertyPath: 'password',
        message: 'Passwords do not match'
    )]
    public string $password_confirm;
}

