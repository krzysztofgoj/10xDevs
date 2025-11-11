<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class GenerateFlashcardsRequest
{
    #[Assert\NotBlank(message: 'Source text cannot be blank')]
    public string $sourceText;

    #[Assert\Callback]
    public function validateWordCount(ExecutionContextInterface $context): void
    {
        $wordCount = str_word_count($this->sourceText);
        
        if ($wordCount < 5) {
            $context->buildViolation('Source text must contain at least 5 words')
                ->atPath('sourceText')
                ->addViolation();
        }
        
        if ($wordCount > 1000) {
            $context->buildViolation('Source text cannot contain more than 1000 words')
                ->atPath('sourceText')
                ->addViolation();
        }
    }
}

