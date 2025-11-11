<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Flashcard;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FlashcardFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $flashcardsData = [
            // For user 0 (test@example.com)
            [
                'user_ref' => UserFixtures::USER_REFERENCE . '0',
                'question' => 'What is PHP?',
                'answer' => 'PHP is a popular server-side scripting language.',
                'source' => 'manual',
            ],
            [
                'user_ref' => UserFixtures::USER_REFERENCE . '0',
                'question' => 'What is Symfony?',
                'answer' => 'Symfony is a PHP framework for web applications.',
                'source' => 'manual',
            ],
            [
                'user_ref' => UserFixtures::USER_REFERENCE . '0',
                'question' => 'What is Doctrine?',
                'answer' => 'Doctrine is an ORM for PHP.',
                'source' => 'ai',
            ],
            // For user 1 (test2@example.com)
            [
                'user_ref' => UserFixtures::USER_REFERENCE . '1',
                'question' => 'What is Docker?',
                'answer' => 'Docker is a platform for containerization.',
                'source' => 'manual',
            ],
            [
                'user_ref' => UserFixtures::USER_REFERENCE . '1',
                'question' => 'What is PostgreSQL?',
                'answer' => 'PostgreSQL is a powerful open-source relational database.',
                'source' => 'ai',
            ],
        ];

        foreach ($flashcardsData as $data) {
            $flashcard = new Flashcard();
            $flashcard->setUser($this->getReference($data['user_ref']));
            $flashcard->setQuestion($data['question']);
            $flashcard->setAnswer($data['answer']);
            $flashcard->setSource($data['source']);

            $manager->persist($flashcard);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}

