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
        ?string            $paymentMode = null,
        ?DateTimeImmutable $createdAt = null,
    ): Receipt
    {
        $paymentMode = $this->evaluatePaymentMode($contract, $paymentMode);

        if (false === in_array($status, Receipt::ALL_STATUS, true)) {
            throw new Exception(sprintf('Create contract with status %s is forbidden', $status));
        }

        if (null === $createdAt) {
            $createdAt = new DateTimeImmutable();
        }

        $externalId = sprintf('%s-%s', $contract->getExternalId(), $startApplyAt->format('Ym'));
        $endApplyAt = $this->evaluateEndApplyAt(
            $contract->getRecurrence(),
            $startApplyAt,
            $contract->getEffectiveDate()
        );
        $dueDate = $this->evaluateDueDate($startApplyAt, $contract->getDebitDay());

        if ($endApplyAt > $contract->getEndEffectiveDate()) {
            dump($endApplyAt);
            dump($contract->getEndEffectiveDate());
            dump($contract->getRecurrence());
            dump($contract->getDebitDay());
            dump($startApplyAt);
            dump($dueDate);
            dd($contract->getAnnualPrimeTtc());
        }
        $amountTtc = $this->evaluateAmountTtc(
            $contract->getEndEffectiveDate(),
            $endApplyAt,
            $contract->getRecurrence(),
            $contract->getAnnualPrimeTtc(),
        );

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

    public function evaluateEndApplyAt(
        string $recurrence,
        DateTimeInterface $startApplyAt,
        DateTimeInterface $effectiveDate
    ): DateTimeInterface
    {
        $effectiveDay = (int)$effectiveDate->format('j');
        $startApplyDay = (int)$startApplyAt->format('j');
        $endApplyMonth = (int)$startApplyAt->format('n');
        $endApplyYear = (int)$startApplyAt->format('Y');

        $nbMonth = match ($recurrence) {
            Contract::RECURRENCE_QUARTERLY => 3,
            Contract::RECURRENCE_SEMI_ANNUALLY => 6,
            Contract::RECURRENCE_ANNUALLY => 12,
            default => 1,
        };

        if ($startApplyDay === 1) {
            $nbMonth--;
        }

        if ($endApplyMonth + $nbMonth > 12) {
            $endApplyYear++;
            $endApplyMonth = ($endApplyMonth + $nbMonth) - 12;
        } else {
            $endApplyMonth += $nbMonth;
        }

        $endApplyAt = new DateTime(sprintf('%d-%d-%d', $endApplyYear, $endApplyMonth, 1));
        $lastDayOfMonth = (int)$endApplyAt->format('t');

        $endApplyDay = $effectiveDay - 1;
        // if startApplyDay is the last day of the month and $effectiveDate->format('t') is the last day of the month,
        // endApplyDay must be the last day of the month - 1 day
        if ($startApplyDay === (int)$startApplyAt->format('t')
            && (int)$effectiveDate->format('j') === (int)$effectiveDate->format('t')
        ) {
            $endApplyDay = $lastDayOfMonth - 1;
        }

        // if startApplyDay is the first day of the month, endApplyDay must be the last day of the month
        if ($startApplyDay === 1 && (int)$effectiveDate->format('j') === 1) {
            $endApplyDay = $lastDayOfMonth;
        }

        // if endApplyDay is estimated to be the last day of the month and startApplyDay
        // is not the first day of the month, we decrement it by 1
        if ($endApplyDay === $lastDayOfMonth && $startApplyDay !== 1) {
            $endApplyDay--;
        }

        if (Contract::RECURRENCE_ANNUALLY === $recurrence) {
            $endApplyDay = (clone $effectiveDate)
                ->modify('+1 year')
                ->modify('-1 day')
                ->format('j');
        }

        return new DateTime(sprintf('%d-%d-%d', $endApplyYear, $endApplyMonth, $endApplyDay));
    }

    public function evaluateAmountTtc(
        DateTimeInterface $endEffectiveDate,
        DateTimeInterface $endApplyAt,
        string $recurrence,
        float $annualPrimeTtc,
    ): float
    {
        if ($endApplyAt > $endEffectiveDate) {
            throw new Exception('endApplyAt must be lower or equal to endEffectiveDate');
        }

        $divisor = match ($recurrence) {
            Contract::RECURRENCE_QUARTERLY => 4,
            Contract::RECURRENCE_SEMI_ANNUALLY => 2,
            Contract::RECURRENCE_ANNUALLY => 1,
            default => 12,
        };

        // determine the monthly ttc amount
        $amountTtc = (int)($annualPrimeTtc * 100);
        $amountTtc = floor($amountTtc / $divisor) / 100;

        // for the last receipt, we need to adjust the amountTtc
        if ($endEffectiveDate->format('Y-m-d') === $endApplyAt->format('Y-m-d')) {
            $amountTtc = $annualPrimeTtc - ($amountTtc * ($divisor - 1));
        }

        return $amountTtc;
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
