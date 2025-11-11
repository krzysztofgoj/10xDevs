<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateFlashcardRequest
{
    #[Assert\NotBlank(message: 'Question cannot be blank')]
    #[Assert\Length(
        min: 1,
        max: 10000,
        minMessage: 'Question must be at least {{ limit }} characters',
        maxMessage: 'Question cannot exceed {{ limit }} characters'
    )]
    public string $question;

    #[Assert\NotBlank(message: 'Answer cannot be blank')]
    #[Assert\Length(
        min: 1,
        max: 10000,
        minMessage: 'Answer must be at least {{ limit }} characters',
        maxMessage: 'Answer cannot exceed {{ limit }} characters'
    )]
    public string $answer;

    #[Assert\Choice(choices: ['ai', 'manual'], message: 'Source must be either "ai" or "manual"')]
    public string $source = 'manual';
}

