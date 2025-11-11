<?php

declare(strict_types=1);

namespace App\Response;

use Symfony\Component\Serializer\Attribute\Groups;

final class ErrorResponse
{
    #[Groups(['api'])]
    public string $error;

    #[Groups(['api'])]
    public ?string $message;

    #[Groups(['api'])]
    public ?array $details;

    public function __construct(
        string $error,
        ?string $message = null,
        ?array $details = null
    ) {
        $this->error = $error;
        $this->message = $message;
        $this->details = $details;
    }
}

