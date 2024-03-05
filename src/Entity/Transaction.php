<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    const STATUS_CREATED = 'created';
    const STATUS_DONE = 'done';
    const STATUS_IN_PAYMENT = 'in_payment';
    const STATUS_FAILED = 'failed';

    const REFUSAL_REASON_INSUFFICIENT_FUNDS = 'insufficient_funds';
    const REFUSAL_REASON_CANCELED_MANDATE = 'canceled_mandate';
    const REFUSAL_REASON_DEBTOR_DECEASED = 'debtor_deceased';
    const REFUSAL_REASON_CB_OPPOSITION = 'cb_opposition';
    const REFUSAL_REASON_OTHER = 'other';
    const ALL_SEPA_REFUSAL_REASONS = [
        self::REFUSAL_REASON_INSUFFICIENT_FUNDS,
        self::REFUSAL_REASON_CANCELED_MANDATE,
        self::REFUSAL_REASON_DEBTOR_DECEASED,
        self::REFUSAL_REASON_OTHER,
    ];
    const ALL_CB_REFUSAL_REASONS = [
        self::REFUSAL_REASON_INSUFFICIENT_FUNDS,
        self::REFUSAL_REASON_CB_OPPOSITION,
        self::REFUSAL_REASON_OTHER,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private Receipt $receipt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $transactionDate;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $paidAt = null;

    #[ORM\Column]
    private ?float $amount;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $failedAt = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReceipt(): Receipt
    {
        return $this->receipt;
    }

    public function setReceipt(Receipt $receipt): static
    {
        $this->receipt = $receipt;

        return $this;
    }

    public function getTransactionDate(): DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(DateTimeInterface $transactionDate): static
    {
        $this->transactionDate = $transactionDate;

        return $this;
    }

    public function getPaidAt(): ?DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(DateTimeInterface $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): static
    {
        $this->failureReason = $failureReason;

        return $this;
    }

    public function getFailedAt(): ?DateTimeInterface
    {
        return $this->failedAt;
    }

    public function setFailedAt(?DateTimeInterface $failedAt): static
    {
        $this->failedAt = $failedAt;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
