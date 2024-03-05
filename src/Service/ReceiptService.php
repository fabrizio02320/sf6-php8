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

        // TODO continue here
        $receipt = $contract->getReceiptOnDate($debitDate->add(new DateInterval('P1M')));
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
        if (Contract::RECURRENCE_ANNUALLY === $recurrence) {
            return null;
        }

        // skip the first debit because it's already paid
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $effectiveDate, $effectiveDate);
        $startApplyAt = (clone $endApplyAt)->add(new DateInterval('P1D'));
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);

        // go to the next applyAt date until the debitDate is just before
        while ($startApplyAt < $debitDate) {
            $startApplyAt = (clone $endApplyAt)->add(new DateInterval('P1D'));
            $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);
        }

        if ($startApplyAt > $debitDate
            && $startApplyAt <= $debitDate->add(new DateInterval('P1M'))
            && $endApplyAt >= $debitDate->add(new DateInterval('P1M'))
        ) {
            return $startApplyAt;
        }

        return null;
    }
}
