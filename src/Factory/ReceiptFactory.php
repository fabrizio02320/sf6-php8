<?php

namespace App\Factory;

use App\Entity\Contract;
use App\Entity\Receipt;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

class ReceiptFactory
{
    public function create(
        Contract           $contract,
        string             $status,
        DateTimeInterface  $startApplyAt,
        float              $amountTtc,
        ?string            $paymentMode = null,
        ?DateTimeImmutable $createdAt = null,
    ): Receipt
    {
//        $debitMode = $contract->getDebitMode();
//        if (Contract::DEBIT_MODE_NOTHING === $debitMode) {
//            $paymentMode = Receipt::PAYMENT_MODE_NULL;
//        } else {
//            $paymentMode = $debitMode;
//        }

        $paymentMode = $this->evaluatePaymentMode($contract, $paymentMode);

        if (false === in_array($status, Receipt::ALL_STATUS, true)) {
            throw new Exception(sprintf('Create contract with status %s is forbidden', $status));
        }

        if (null === $createdAt) {
            $createdAt = new DateTimeImmutable();
        }

        $externalId = sprintf('%s-%s', $contract->getExternalId(), $startApplyAt->format('Ym'));

        $recurrence = match ($contract->getRecurrence()) {
            Contract::RECURRENCE_QUARTERLY => '+3 month',
            Contract::RECURRENCE_SEMI_ANNUAL => '+6 month',
            Contract::RECURRENCE_ANNUAL => '+1 year',
            default => '+1 month',
        };
        $recurrence = sprintf('%s -1 day', $recurrence);
        $endApplyAt = (clone $startApplyAt)->modify($recurrence);

        $dueDate = $this->evaluateDueDate($startApplyAt, $contract->getDebitDay());

        $receipt = new Receipt();
        $receipt
            ->setContract($contract)
            ->setExternalId($externalId)
            ->setStatus($status)
            ->setStartApplyAt($startApplyAt)
            ->setEndApplyAt($endApplyAt)
            ->setCreatedAt($createdAt)
            ->setDueDate($dueDate)
            ->setAmountTtc($amountTtc)
            ->setPaymentMode($paymentMode)
        ;

        return $receipt;
    }

    public function createFirst(
        Contract           $contract,
        float              $amountTtc,
        ?DateTimeImmutable $createdAt = null,
    ): Receipt
    {
        $startApplyAt = $contract->getEffectiveDate();
        $status = Receipt::STATUS_PAID;
        $paymentMode = Receipt::PAYMENT_MODE_CB;

        return $this->create(
            contract: $contract,
            status: $status,
            startApplyAt: $startApplyAt,
            amountTtc: $amountTtc,
            paymentMode: $paymentMode,
            createdAt: $createdAt,
        );
    }

    /**
     * @throws Exception
     */
    public function evaluateDueDate(DateTimeInterface $startApplyAt, int $debitDay): DateTimeInterface
    {
        $dueDateMonth = (int)$startApplyAt->format('n');
        $dueDateYear = (int)$startApplyAt->format('Y');

        // if the debit day is lower or equal than the current day, we keep the current month
        // else we take the previous month and evaluate debitDay if necessary
        if ($debitDay > (int)$startApplyAt->format('j')) {
            if ($dueDateMonth === 1) {
                $dueDateMonth = 12;
                $dueDateYear--;
            } else {
                $dueDateMonth--;
            }

            $dueDate = new DateTime(sprintf('%d-%d-%d', $dueDateYear, $dueDateMonth, 1));
            $lastDay = (int)$dueDate->format('t');
            if ($debitDay > $lastDay) {
                $debitDay = $lastDay;
            }
        }

        return new DateTime(sprintf('%d-%d-%d', $dueDateYear, $dueDateMonth, $debitDay));
    }

    private function evaluatePaymentMode(Contract $contract, ?string $paymentModeRequested = null): ?string
    {
        $paymentMode = $paymentModeRequested;
        if (null !== $paymentMode && !in_array($paymentMode, Receipt::ALL_PAYMENT_MODE, true)) {
            $paymentMode = null;
        }

        if (null === $paymentMode) {
            $paymentMode = match ($contract->getDebitMode()) {
                Contract::DEBIT_MODE_CB => Receipt::PAYMENT_MODE_CB,
                Contract::DEBIT_MODE_SEPA => Receipt::PAYMENT_MODE_SEPA,
                default => Receipt::PAYMENT_MODE_NULL,
            };
        }

        return $paymentMode;
    }
}
