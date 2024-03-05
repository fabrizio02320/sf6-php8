<?php

namespace App\Service;

use App\Entity\Contract;
use App\Entity\Receipt;
use App\Factory\ReceiptFactory;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

class ReceiptService
{
    public function __construct(private ReceiptFactory $receiptFactory)
    {}

    public function getOrCreateReceipt(Contract $contract, DateTimeImmutable $debitDate)
    {
        if (Contract::DEBIT_MODE_NOTHING === $contract->getDebitMode()) {
            $debitDate = (clone $debitDate)->add(new DateInterval('P1M'));
        }

        $receipt = $contract->getReceiptOnDate($debitDate);
        if ($receipt) {
            return $receipt;
        }

        $startApplyAt = $this->evaluateStartApplyAt(
            $contract->getEffectiveDate(),
            $contract->getRecurrence(),
            $debitDate
        );

        if (!$startApplyAt) {
            throw new Exception('No startApplyAt found');
        }

        return $this->receiptFactory->create(
            contract: $contract,
            status: Receipt::STATUS_TO_PAY,
            startApplyAt: $startApplyAt,
        );
    }

    public function evaluateStartApplyAt(
        DateTimeInterface $effectiveDate,
        string $recurrence,
        DateTimeImmutable $debitDate
    ): ?DateTimeInterface
    {
        $startApplyAt = clone $effectiveDate;
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);

        $previousStartApplyAt = clone $startApplyAt;
        $nextApplyAt = (clone $endApplyAt)->add(new DateInterval('P1D'));

        // go to the next applyAt date until the debitDate is just before
        while ($nextApplyAt < $debitDate) {
            $previousStartApplyAt = clone $nextApplyAt;
            $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $nextApplyAt, $effectiveDate);
            $nextApplyAt = (clone $endApplyAt)->add(new DateInterval('P1D'));
            $startApplyAt = clone $nextApplyAt;
        }

        // if the previous startApplyAt is the same as the effectiveDate and the next applyAt is after the debitDate
        // then the startApplyAt is the next applyAt
        if ($previousStartApplyAt->format('Y-m-d') === $effectiveDate->format('Y-m-d')
            && $nextApplyAt > $debitDate
        ) {
            return $nextApplyAt;
        }

        $nbMonth = match ($recurrence) {
            Contract::RECURRENCE_MONTHLY => 1,
            Contract::RECURRENCE_QUARTERLY => 3,
            Contract::RECURRENCE_SEMI_ANNUALLY => 6,
            Contract::RECURRENCE_ANNUALLY => 12,
        };

        if ($previousStartApplyAt < $debitDate
            && $endApplyAt > $debitDate
            && $startApplyAt->diff($debitDate)->m < $nbMonth
            && $startApplyAt->diff($effectiveDate)->m > 0
        ) {
            return $startApplyAt;
        }

        return null;
    }
}
