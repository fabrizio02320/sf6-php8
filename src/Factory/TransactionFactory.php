<?php

namespace App\Factory;

use App\Entity\Receipt;
use App\Entity\Transaction;
use DateTimeImmutable;

class TransactionFactory
{
    public function create(Receipt $receipt, DateTimeImmutable $debitDate): Transaction
    {
        $transaction = new Transaction();
        $transaction
            ->setReceipt($receipt)
            ->setTransactionDate($debitDate)
            ->setPaidAt($debitDate)
            ->setAmount($receipt->getAmountTtc())
            ->setStatus(Transaction::STATUS_CREATED)
            ->setCreatedAt(new DateTimeImmutable())
        ;

        return $transaction;
    }
}
