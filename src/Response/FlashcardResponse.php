<?php

declare(strict_types=1);

namespace App\Response;

use Symfony\Component\Serializer\Attribute\Groups;

final class FlashcardResponse
{
    #[Groups(['api'])]
    public int $id;

    #[Groups(['api'])]
    public string $question;

    #[Groups(['api'])]
    public string $answer;

    #[Groups(['api'])]
    public string $source;

    #[Groups(['api'])]
    public \DateTimeImmutable $createdAt;

    #[Groups(['api'])]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        int $id,
        string $question,
        string $answer,
        string $source,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->question = $question;
        $this->answer = $answer;
        $this->source = $source;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
}

