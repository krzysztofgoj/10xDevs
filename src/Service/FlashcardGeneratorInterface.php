<?php

declare(strict_types=1);

namespace App\Service;

use App\Response\GeneratedFlashcardResponse;

interface FlashcardGeneratorInterface
{
    /**
     * Generate flashcards from source text.
     * 
     * @param string $sourceText The text to generate flashcards from
     * @return GeneratedFlashcardResponse[] Array of 3-10 generated flashcards
     */
    public function generate(string $sourceText): array;
}



