<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginRequest
{
    #[Assert\NotBlank(message: 'Email cannot be blank')]
    #[Assert\Email(message: 'Email must be a valid email address')]
    public string $email;

    #[Assert\NotBlank(message: 'Password cannot be blank')]
    public string $password;
}

