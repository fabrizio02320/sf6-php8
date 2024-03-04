<?php

namespace App\Tests\Service;

use App\Entity\Contract;
use App\Factory\ReceiptFactory;
use App\Service\ReceiptService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ReceiptServiceTest extends TestCase
{
    use ProphecyTrait;

    private ReceiptService $receiptService;
    private $receiptFactory;

    protected function setUp(): void
    {
        $this->receiptFactory = new ReceiptFactory();
        $this->receiptService = new ReceiptService($this->receiptFactory);
    }

    public function testEvaluateStartApplyAt()
    {
        ////////////////////
        $effectiveDate = new \DateTime('2022-12-29');
        $recurrent = Contract::RECURRENCE_MONTHLY;
        $debitDate = new DateTimeImmutable('2023-03-05');
        $result = $this->receiptService->evaluateStartApplyAt($effectiveDate, $recurrent, $debitDate);
        $this->assertEquals('2023-03-29', $result->format('Y-m-d'));

        $debitDate = new DateTimeImmutable('2023-04-05');
        $result = $this->receiptService->evaluateStartApplyAt($effectiveDate, $recurrent, $debitDate);
        $this->assertEquals('2023-04-29', $result->format('Y-m-d'));

        $recurrent = Contract::RECURRENCE_QUARTERLY;
        $debitDate = new DateTimeImmutable('2023-03-05');
        $result = $this->receiptService->evaluateStartApplyAt($effectiveDate, $recurrent, $debitDate);
        $this->assertEquals('2023-03-29', $result->format('Y-m-d'));


        ////////////////////
        $effectiveDate = new \DateTime('2024-01-01');
        $recurrent = Contract::RECURRENCE_MONTHLY;
        $debitDate = new DateTimeImmutable('2024-03-05');
        $result = $this->receiptService->evaluateStartApplyAt($effectiveDate, $recurrent, $debitDate);
        $this->assertEquals('2024-04-01', $result->format('Y-m-d'));

        $recurrent = Contract::RECURRENCE_SEMI_ANNUALly;
        $debitDate = new DateTimeImmutable('2024-03-05');
        $result = $this->receiptService->evaluateStartApplyAt($effectiveDate, $recurrent, $debitDate);
        $this->assertEquals('2024-07-01', $result->format('Y-m-d'));
    }
}
