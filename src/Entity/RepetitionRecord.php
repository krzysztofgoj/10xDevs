<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RepetitionRecordRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RepetitionRecordRepository::class)]
#[ORM\Table(name: 'repetition_record')]
#[ORM\UniqueConstraint(name: 'UNIQ_REPETITION_FLASHCARD_ID', columns: ['flashcard_id'])]
#[ORM\Index(columns: ['next_review_at'], name: 'IDX_REPETITION_NEXT_REVIEW_AT')]
#[ORM\Index(columns: ['last_reviewed_at'], name: 'IDX_REPETITION_LAST_REVIEWED_AT')]
#[ORM\HasLifecycleCallbacks]
class RepetitionRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Flashcard::class, inversedBy: 'repetitionRecord', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Flashcard $flashcard = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastReviewedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $nextReviewAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $easeFactor = '2.50';

    #[ORM\Column(type: Types::INTEGER)]
    private int $intervalDays = 1;

    #[ORM\Column(type: Types::INTEGER)]
    private int $repetitionCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFlashcard(): ?Flashcard
    {
        return $this->flashcard;
    }

    public function setFlashcard(?Flashcard $flashcard): self
    {
        $this->flashcard = $flashcard;

        return $this;
    }

    public function getLastReviewedAt(): ?\DateTimeImmutable
    {
        return $this->lastReviewedAt;
    }

    public function setLastReviewedAt(?\DateTimeImmutable $lastReviewedAt): self
    {
        $this->lastReviewedAt = $lastReviewedAt;

        return $this;
    }

    public function getNextReviewAt(): ?\DateTimeImmutable
    {
        return $this->nextReviewAt;
    }

    public function setNextReviewAt(?\DateTimeImmutable $nextReviewAt): self
    {
        $this->nextReviewAt = $nextReviewAt;

        return $this;
    }

    public function getEaseFactor(): string
    {
        return $this->easeFactor;
    }

    public function setEaseFactor(string $easeFactor): self
    {
        $this->easeFactor = $easeFactor;

        return $this;
    }

    public function getIntervalDays(): int
    {
        return $this->intervalDays;
    }

    public function setIntervalDays(int $intervalDays): self
    {
        $this->intervalDays = $intervalDays;

        return $this;
    }

    public function getRepetitionCount(): int
    {
        return $this->repetitionCount;
    }

    public function setRepetitionCount(int $repetitionCount): self
    {
        $this->repetitionCount = $repetitionCount;

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

