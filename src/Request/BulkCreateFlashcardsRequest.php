<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class BulkCreateFlashcardsRequest
{
    /**
     * @var array
     */
    #[Assert\NotBlank(message: 'Flashcards array cannot be empty')]
    #[Assert\Count(
        min: 1,
        max: 100,
        minMessage: 'At least one flashcard is required',
        maxMessage: 'Cannot create more than {{ limit }} flashcards at once'
    )]
    #[Assert\All([
        new Assert\Collection(
            fields: [
                'question' => [
                    new Assert\NotBlank(message: 'Question cannot be blank'),
                    new Assert\Length(
                        min: 1,
                        max: 10000,
                        minMessage: 'Question must be at least {{ limit }} characters',
                        maxMessage: 'Question cannot exceed {{ limit }} characters'
                    ),
                ],
                'answer' => [
                    new Assert\NotBlank(message: 'Answer cannot be blank'),
                    new Assert\Length(
                        min: 1,
                        max: 10000,
                        minMessage: 'Answer must be at least {{ limit }} characters',
                        maxMessage: 'Answer cannot exceed {{ limit }} characters'
                    ),
                ],
                'source' => [
                    new Assert\Choice(
                        choices: ['ai', 'manual'],
                        message: 'Source must be either "ai" or "manual"'
                    ),
                ],
            ],
            allowExtraFields: true,
            allowMissingFields: true
        ),
    ])]
    public array $flashcards;
}

