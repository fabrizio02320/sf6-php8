<?php

namespace App\Entity;

use App\Repository\ReceiptRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReceiptRepository::class)]
class Receipt
{
    public const STATUS_IN_PAYMENT = 'in_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_TO_PAY = 'to_pay';
    public const ALL_STATUS = [
        self::STATUS_IN_PAYMENT,
        self::STATUS_PAID,
        self::STATUS_UNPAID,
        self::STATUS_TO_PAY,
    ];

    public const PAYMENT_MODE_CB = 'cb';
    public const PAYMENT_MODE_SEPA = 'sepa';
    public const PAYMENT_MODE_NULL = null;
    public const ALL_PAYMENT_MODE = [
        self::PAYMENT_MODE_CB,
        self::PAYMENT_MODE_SEPA,
        self::PAYMENT_MODE_NULL,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'receipts')]
    #[ORM\JoinColumn(nullable: false)]
    private Contract $contract;

    #[ORM\Column(length: 255)]
    private ?string $externalId = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $startApplyAt = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $endApplyAt = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column]
    private ?float $amountTtc = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentMode = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'receipt', targetEntity: Transaction::class, orphanRemoval: true)]
    private Collection $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContract(): Contract
    {
        return $this->contract;
    }

    public function setContract(Contract $contract): static
    {
        $this->contract = $contract;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getStartApplyAt(): ?\DateTimeInterface
    {
        return $this->startApplyAt;
    }

    public function setStartApplyAt(\DateTimeInterface $startApplyAt): static
    {
        $this->startApplyAt = $startApplyAt;

        return $this;
    }

    public function getEndApplyAt(): ?\DateTimeInterface
    {
        return $this->endApplyAt;
    }

    public function setEndApplyAt(\DateTimeInterface $endApplyAt): static
    {
        $this->endApplyAt = $endApplyAt;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getAmountTtc(): ?float
    {
        return $this->amountTtc;
    }

    public function setAmountTtc(float $amountTtc): static
    {
        $this->amountTtc = $amountTtc;

        return $this;
    }

    public function getPaymentMode(): ?string
    {
        return $this->paymentMode;
    }

    public function setPaymentMode(?string $paymentMode = null): static
    {
        $this->paymentMode = $paymentMode;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setReceipt($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getReceipt() === $this) {
                $transaction->setReceipt(null);
            }
        }

        return $this;
    }
}
