<?php

namespace App\Factory;

use App\Entity\Receipt;
use App\Entity\Transaction;
use DateTime;
use DateTimeImmutable;

class TransactionFactory
{
    public function create(Receipt $receipt): Transaction
    {
        $transaction = new Transaction();
        $transaction
            ->setReceipt($receipt)
            ->setTransactionDate(new DateTime())
            ->setAmount($receipt->getAmountTtc())
            ->setStatus(Transaction::STATUS_CREATED)
            ->setCreatedAt(new DateTimeImmutable())
        ;

        return $transaction;
    }
}
