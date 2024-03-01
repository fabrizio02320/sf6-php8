<?php

namespace App\Entity;

use App\Repository\ContractRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
class Contract
{
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_IN_PAYMENT = 'in_payment';
    const STATUS_UNPAID = 'unpaid';
    const STATUS_TERMINATED = 'terminated';
    const ALL_STATUS = [
        self::STATUS_IN_PROGRESS,
        self::STATUS_IN_PAYMENT,
        self::STATUS_UNPAID,
        self::STATUS_TERMINATED,
    ];

    const DEBIT_MODE_CB = 'cb';
    const DEBIT_MODE_SEPA = 'sepa';
    const DEBIT_MODE_NOTHING = 'nothing';
    const ALL_DEBIT_MODE = [
        self::DEBIT_MODE_CB,
        self::DEBIT_MODE_SEPA,
        self::DEBIT_MODE_NOTHING,
    ];

    const RECURRENCE_MONTHLY = 'monthly';
    const RECURRENCE_QUARTERLY = 'quarterly';
    const RECURRENCE_SEMI_ANNUAL = 'semi-annual';
    const RECURRENCE_ANNUAL = 'annual';
    const ALL_RECURRENCE = [
        self::RECURRENCE_MONTHLY,
        self::RECURRENCE_QUARTERLY,
        self::RECURRENCE_SEMI_ANNUAL,
        self::RECURRENCE_ANNUAL,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private string $externalId;

    #[ORM\Column(length: 255)]
    private string $status;

    #[ORM\Column]
    private int $debitDay;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private DateTimeInterface $effectiveDate;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private DateTimeInterface $endEffectiveDate;

    #[ORM\Column]
    private float $annualPrimeTtc;

    #[ORM\Column(length: 255)]
    private string $debitMode;

    #[ORM\Column(length: 255)]
    private string $recurrence;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'contractId', targetEntity: Receipt::class, orphanRemoval: true)]
    private Collection $receipts;

    public function __construct()
    {
        $this->receipts = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDebitDay(): int
    {
        return $this->debitDay;
    }

    public function setDebitDay(int $debitDay): static
    {
        $this->debitDay = $debitDay;

        return $this;
    }

    public function getEffectiveDate(): DateTimeInterface
    {
        return $this->effectiveDate;
    }

    public function setEffectiveDate(DateTimeInterface $effectiveDate): static
    {
        $this->effectiveDate = $effectiveDate;

        return $this;
    }

    public function getEndEffectiveDate(): DateTimeInterface
    {
        return $this->endEffectiveDate;
    }

    public function setEndEffectiveDate(DateTimeInterface $endEffectiveDate): static
    {
        $this->endEffectiveDate = $endEffectiveDate;

        return $this;
    }

    public function getAnnualPrimeTtc(): float
    {
        return $this->annualPrimeTtc;
    }

    public function setAnnualPrimeTtc(float $annualPrimeTtc): static
    {
        $this->annualPrimeTtc = $annualPrimeTtc;

        return $this;
    }

    public function getDebitMode(): string
    {
        return $this->debitMode;
    }

    public function setDebitMode(string $debitMode): static
    {
        $this->debitMode = $debitMode;

        return $this;
    }

    public function getRecurrence(): string
    {
        return $this->recurrence;
    }

    public function setRecurrence(string $recurrence): static
    {
        $this->recurrence = $recurrence;

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

    public function getReceiptOnDate(DateTimeInterface $date): ?Receipt
    {
        $receipts = $this->receipts->filter(function (Receipt $receipt) use ($date) {
            return $receipt->getStartApplyAt() <= $date && $receipt->getEndApplyAt() >= $date;
        });

        return $receipts->first() ?: null;
    }

    /**
     * @return Collection<int, Receipt>
     */
    public function getReceipts(): Collection
    {
        return $this->receipts;
    }

    public function addReceipt(Receipt $receipt): static
    {
        if (!$this->receipts->contains($receipt)) {
            $this->receipts->add($receipt);
            $receipt->setContract($this);
        }

        return $this;
    }

    public function removeReceipt(Receipt $receipt): static
    {
        if ($this->receipts->removeElement($receipt)) {
            // set the owning side to null (unless already changed)
            if ($receipt->getContract() === $this) {
                $receipt->setContract(null);
            }
        }

        return $this;
    }
}
