<?php

declare(strict_types=1);

namespace App\Response;

use Symfony\Component\Serializer\Attribute\Groups;

final class AuthResponse
{
    #[Groups(['api'])]
    public string $token;

    #[Groups(['api'])]
    public int $userId;

    #[Groups(['api'])]
    public string $email;

    public function __construct(
        string $token,
        int $userId,
        string $email
    ) {
        $this->token = $token;
        $this->userId = $userId;
        $this->email = $email;
    }
}

