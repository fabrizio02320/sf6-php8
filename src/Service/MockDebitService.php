<?php

namespace App\Service;

use App\Entity\Contract;
use App\Entity\Receipt;
use App\Entity\Transaction;
use DateTimeImmutable;

class MockDebitService
{
    public function debit(Transaction $transaction): void
    {
        $debitMode = $transaction->getReceipt()->getContract()->getDebitMode();
        if (Contract::DEBIT_MODE_NOTHING === $debitMode) {
            return;
        }

        if (Contract::DEBIT_MODE_SEPA === $debitMode) {
            $this->debitSepa($transaction);
        }

        if (Contract::DEBIT_MODE_CB === $debitMode) {
            $this->debitCb($transaction);
        }

        $this->evaluateReceiptAndContract($transaction);
    }

    private function debitCb(Transaction $transaction)
    {
        $now = new DateTimeImmutable();

        // 15% of chance to fail
        // 85% of chance to be done
        $rand = random_int(0, 100);
        if ($rand < 15) {
            $randRefusalReason = Transaction::ALL_CB_REFUSAL_REASONS[array_rand(Transaction::ALL_CB_REFUSAL_REASONS)];

            $transaction
                ->setStatus(Transaction::STATUS_FAILED)
                ->setFailureReason($randRefusalReason)
                ->setFailedAt($now)
            ;
        } else {
            $transaction
                ->setStatus(Transaction::STATUS_DONE)
                ->setPaidAt($now)
            ;
        }
        $transaction->setUpdatedAt(new DateTimeImmutable());
    }

    // TODO avec plus de temps, appliquer le pattern strategy pour les différents modes de débit
    private function debitSepa(Transaction $transaction)
    {
        // wait 0,1 seconde
        usleep(100000);
        $now = new DateTimeImmutable();

        // 10% of chance to fail
        // 30% of chance to be in payment
        // 60% of chance to be done
        $rand = random_int(0, 100);
        if ($rand < 10) {
            $randRefusalReason = Transaction::ALL_SEPA_REFUSAL_REASONS[
                array_rand(Transaction::ALL_SEPA_REFUSAL_REASONS)
            ];

            $transaction
                ->setStatus(Transaction::STATUS_FAILED)
                ->setFailureReason($randRefusalReason)
                ->setFailedAt($now)
            ;
        } elseif ($rand < 40) {
            $transaction->setStatus(Transaction::STATUS_IN_PAYMENT);
        } else {
            $transaction
                ->setStatus(Transaction::STATUS_DONE)
                ->setPaidAt($now)
            ;
        }

        $transaction->setUpdatedAt(new DateTimeImmutable());
    }

    private function evaluateReceiptAndContract(Transaction $transaction): void
    {
        $receipt = $transaction->getReceipt();
        $contract = $receipt->getContract();

        if (Transaction::STATUS_DONE === $transaction->getStatus()) {
            $receipt->setStatus(Receipt::STATUS_PAID);
            $contract->setStatus(Contract::STATUS_IN_PROGRESS);
        }

        if (Transaction::STATUS_FAILED === $transaction->getStatus()) {
            $receipt->setStatus(Receipt::STATUS_UNPAID);
            $contract->setStatus(Contract::STATUS_UNPAID);
        }

        if (Transaction::STATUS_IN_PAYMENT === $transaction->getStatus()) {
            $receipt->setStatus(Receipt::STATUS_IN_PAYMENT);
            $contract->setStatus(Contract::STATUS_IN_PAYMENT);
        }

        $now = new DateTimeImmutable();
        $receipt->setUpdatedAt($now);
        $contract->setUpdatedAt($now);
    }
}
