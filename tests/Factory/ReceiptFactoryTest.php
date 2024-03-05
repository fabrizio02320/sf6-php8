<?php

namespace App\Tests\Factory;

use App\Entity\Contract;
use App\Entity\Receipt;
use App\Factory\ReceiptFactory;
use DateTime;
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

    // TODO a revoir et completer
    public function estCreate(): void
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
            paymentMode: $paymentMode,
            createdAt: $createdAt,
        );

        $this->assertEquals($status, $receipt->getStatus());
        $this->assertEquals($startApplyAt, $receipt->getStartApplyAt());
        $this->assertEquals($amountTtc, $receipt->getAmountTtc());
        $this->assertEquals($paymentMode, $receipt->getPaymentMode());
        $this->assertEquals($createdAt, $receipt->getCreatedAt());
    }

    public function testEvaluateEndApplyAt(): void
    {
        ////////////////////
        $effectiveDate = new DateTimeImmutable('2023-05-15');
        $startApplyAt = new DateTimeImmutable('2023-05-15');
        $recurrence = Contract::RECURRENCE_MONTHLY;
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);
        $this->assertEquals('2023-06-14', $endApplyAt->format('Y-m-d'));

        $recurrence = Contract::RECURRENCE_QUARTERLY;
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);
        $this->assertEquals('2023-08-14', $endApplyAt->format('Y-m-d'));

        $recurrence = Contract::RECURRENCE_SEMI_ANNUALLY;
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);
        $this->assertEquals('2023-11-14', $endApplyAt->format('Y-m-d'));

        $recurrence = Contract::RECURRENCE_ANNUALLY;
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);
        $this->assertEquals('2024-05-14', $endApplyAt->format('Y-m-d'));


        ////////////////////
        $effectiveDate = new DateTimeImmutable('2023-01-31');
        $startApplyAt = new DateTimeImmutable('2023-01-31');
        $recurrence = Contract::RECURRENCE_MONTHLY;
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);
        $this->assertEquals('2023-02-27', $endApplyAt->format('Y-m-d'));

        $recurrence = Contract::RECURRENCE_QUARTERLY;
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);
        $this->assertEquals('2023-04-29', $endApplyAt->format('Y-m-d'));

        $recurrence = Contract::RECURRENCE_SEMI_ANNUALLY;
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);
        $this->assertEquals('2023-07-30', $endApplyAt->format('Y-m-d'));

        $recurrence = Contract::RECURRENCE_ANNUALLY;
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);
        $this->assertEquals('2024-01-30', $endApplyAt->format('Y-m-d'));


        ////////////////////
        $effectiveDate = new DateTimeImmutable('2024-01-31');
        $startApplyAt = new DateTimeImmutable('2024-01-31');
        $recurrence = Contract::RECURRENCE_MONTHLY;
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);
        $this->assertEquals('2024-02-28', $endApplyAt->format('Y-m-d'));


        ////////////////////
        $effectiveDate = new DateTimeImmutable('2023-12-29');
        $startApplyAt = new DateTimeImmutable('2023-12-29');
        $recurrence = Contract::RECURRENCE_MONTHLY;
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt($recurrence, $startApplyAt, $effectiveDate);
        $this->assertEquals('2024-01-28', $endApplyAt->format('Y-m-d'));


        ////////////////////
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt(
            Contract::RECURRENCE_MONTHLY,
            new DateTimeImmutable('2023-12-01'),
            new DateTimeImmutable('2023-12-01')
        );
        $this->assertEquals('2023-12-31', $endApplyAt->format('Y-m-d'));

        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt(
            Contract::RECURRENCE_QUARTERLY,
            new DateTimeImmutable('2023-12-01'),
            new DateTimeImmutable('2023-12-01')
        );
        $this->assertEquals('2024-02-29', $endApplyAt->format('Y-m-d'));

        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt(
            Contract::RECURRENCE_SEMI_ANNUALLY,
            new DateTimeImmutable('2023-12-01'),
            new DateTimeImmutable('2023-12-01')
        );
        $this->assertEquals('2024-05-31', $endApplyAt->format('Y-m-d'));

        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt(
            recurrence: Contract::RECURRENCE_ANNUALLY,
            startApplyAt: new DateTime('2023-12-01'),
            effectiveDate: new DateTime('2023-12-01'),
        );
        $this->assertEquals('2024-11-30', $endApplyAt->format('Y-m-d'));


        ////////////////////
        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt(
            recurrence: Contract::RECURRENCE_ANNUALLY,
            startApplyAt: new DateTime('2023-02-28'),
            effectiveDate: new DateTime('2023-02-28'),
        );
        $this->assertEquals('2024-02-27', $endApplyAt->format('Y-m-d'));

        $endApplyAt = $this->receiptFactory->evaluateEndApplyAt(
            recurrence: Contract::RECURRENCE_ANNUALLY,
            startApplyAt: new DateTime('2024-03-01'),
            effectiveDate: new DateTime('2024-03-01'),
        );
        $this->assertEquals('2025-02-28', $endApplyAt->format('Y-m-d'));
    }

    public function testEvaluateAmountTtc(): void
    {
        ////////////////////
        $amountTtc = $this->receiptFactory->evaluateAmountTtc(
            endEffectiveDate: new DateTimeImmutable('2023-05-14'),
            endApplyAt: new DateTimeImmutable('2023-04-14'),
            recurrence: Contract::RECURRENCE_MONTHLY,
            annualPrimeTtc: 1000,
        );
        $this->assertEquals(83.33, $amountTtc);

        $amountTtc = $this->receiptFactory->evaluateAmountTtc(
            endEffectiveDate: new DateTimeImmutable('2023-05-14'),
            endApplyAt: new DateTimeImmutable('2023-05-14'),
            recurrence: Contract::RECURRENCE_MONTHLY,
            annualPrimeTtc: 1000,
        );
        $this->assertEquals(83.37, $amountTtc);


        ////////////////////
        $amountTtc = $this->receiptFactory->evaluateAmountTtc(
            endEffectiveDate: new DateTimeImmutable('2023-05-14'),
            endApplyAt: new DateTimeImmutable('2023-02-14'),
            recurrence: Contract::RECURRENCE_QUARTERLY,
            annualPrimeTtc: 1000,
        );
        $this->assertEquals(250, $amountTtc);

        $amountTtc = $this->receiptFactory->evaluateAmountTtc(
            endEffectiveDate: new DateTimeImmutable('2023-05-14'),
            endApplyAt: new DateTimeImmutable('2023-05-14'),
            recurrence: Contract::RECURRENCE_QUARTERLY,
            annualPrimeTtc: 1000,
        );
        $this->assertEquals(250, $amountTtc);
    }
}
