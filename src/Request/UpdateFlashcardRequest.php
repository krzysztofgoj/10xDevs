<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateFlashcardRequest
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
}

