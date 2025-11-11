<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FlashcardRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlashcardRepository::class)]
#[ORM\Table(name: 'flashcard')]
#[ORM\Index(columns: ['user_id'], name: 'IDX_FLASHCARD_USER_ID')]
#[ORM\Index(columns: ['created_at'], name: 'IDX_FLASHCARD_CREATED_AT')]
#[ORM\Index(columns: ['generation_id'], name: 'IDX_FLASHCARD_GENERATION_ID')]
#[ORM\HasLifecycleCallbacks]
class Flashcard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'flashcards')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $question = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $answer = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $source = 'manual';

    #[ORM\ManyToOne(targetEntity: FlashcardGeneration::class, inversedBy: 'flashcards')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?FlashcardGeneration $generation = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(targetEntity: RepetitionRecord::class, mappedBy: 'flashcard', cascade: ['remove'])]
    private ?RepetitionRecord $repetitionRecord = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getGeneration(): ?FlashcardGeneration
    {
        return $this->generation;
    }

    public function setGeneration(?FlashcardGeneration $generation): self
    {
        $this->generation = $generation;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getRepetitionRecord(): ?RepetitionRecord
    {
        return $this->repetitionRecord;
    }

    public function setRepetitionRecord(?RepetitionRecord $repetitionRecord): self
    {
        $this->repetitionRecord = $repetitionRecord;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

