<?php

namespace App\Tests\Factory;

use App\Entity\Contract;
use App\Entity\Receipt;
use App\Factory\ReceiptFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ReceiptFactoryTest extends TestCase
{
    private ReceiptFactory $receiptFactory;

    protected function setUp(): void
    {
        $this->receiptFactory = new ReceiptFactory();
    }

    public function testEvaluateDueDate(): void
    {
        ////////////////////
        $startApplyAt = new DateTimeImmutable('2023-05-15');

        $debitDay = 10;
        $dueDate = $this->receiptFactory->evaluateDueDate($startApplyAt, $debitDay);
        $this->assertEquals('2023-05-10', $dueDate->format('Y-m-d'));

        $debitDay = 15;
        $dueDate = $this->receiptFactory->evaluateDueDate($startApplyAt, $debitDay);
        $this->assertEquals('2023-05-15', $dueDate->format('Y-m-d'));

        $debitDay = 16;
        $dueDate = $this->receiptFactory->evaluateDueDate($startApplyAt, $debitDay);
        $this->assertEquals('2023-04-16', $dueDate->format('Y-m-d'));

        ////////////////////
        $startApplyAt = new DateTimeImmutable('2024-02-29');

        $debitDay = 1;
        $dueDate = $this->receiptFactory->evaluateDueDate($startApplyAt, $debitDay);
        $this->assertEquals('2024-02-01', $dueDate->format('Y-m-d'));

        $debitDay = 28;
        $dueDate = $this->receiptFactory->evaluateDueDate($startApplyAt, $debitDay);
        $this->assertEquals('2024-02-28', $dueDate->format('Y-m-d'));

        $debitDay = 31;
        $dueDate = $this->receiptFactory->evaluateDueDate($startApplyAt, $debitDay);
        $this->assertEquals('2024-01-31', $dueDate->format('Y-m-d'));

        $debitDay = 29;
        $dueDate = $this->receiptFactory->evaluateDueDate($startApplyAt, $debitDay);
        $this->assertEquals('2024-02-29', $dueDate->format('Y-m-d'));

        ////////////////////
        $startApplyAt = new DateTimeImmutable('2024-03-30');

        $debitDay = 31;
        $dueDate = $this->receiptFactory->evaluateDueDate($startApplyAt, $debitDay);
        $this->assertEquals('2024-02-29', $dueDate->format('Y-m-d'));
    }

    public function testCreate(): void
    {
        $contract = new Contract();
        $contract->setExternalId('test-external-id');
        $contract->setRecurrence(Contract::RECURRENCE_MONTHLY);
        $contract->setDebitDay(10);
        $contract->setEffectiveDate(new DateTimeImmutable('2023-05-15'));

        $status = Receipt::STATUS_IN_PAYMENT;
        $startApplyAt = new DateTimeImmutable('2023-06-15');
        $amountTtc = 100.0;
        $paymentMode = Receipt::PAYMENT_MODE_CB;
        $createdAt = new DateTimeImmutable('2023-05-15');

        $receipt = $this->receiptFactory->create(
            contract: $contract,
            status: $status,
            startApplyAt: $startApplyAt,
            amountTtc: $amountTtc,
            paymentMode: $paymentMode,
            createdAt: $createdAt,
        );

        $this->assertEquals($status, $receipt->getStatus());
        $this->assertEquals($startApplyAt, $receipt->getStartApplyAt());
        $this->assertEquals($amountTtc, $receipt->getAmountTtc());
        $this->assertEquals($paymentMode, $receipt->getPaymentMode());
        $this->assertEquals($createdAt, $receipt->getCreatedAt());
    }
}
