<?php

declare(strict_types=1);

namespace App\Response;

use Symfony\Component\Serializer\Attribute\Groups;

final class GeneratedFlashcardResponse
{
    #[Groups(['api'])]
    public string $question;

    #[Groups(['api'])]
    public string $answer;

    public function __construct(
        string $question,
        string $answer
    ) {
        $this->question = $question;
        $this->answer = $answer;
    }
}



