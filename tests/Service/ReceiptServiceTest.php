<?php

namespace App\Tests\Service;

use App\Entity\Contract;
use App\Factory\ReceiptFactory;
use App\Service\ReceiptService;
use DateTime;
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
        $result = $this->receiptService->evaluateStartApplyAt(
            effectiveDate: new DateTime('2022-12-29'),
            recurrence: Contract::RECURRENCE_MONTHLY,
            debitDate: new DateTimeImmutable('2023-03-05')
        );
        $this->assertEquals('2023-03-29', $result->format('Y-m-d'));

        $result = $this->receiptService->evaluateStartApplyAt(
            effectiveDate: new DateTime('2022-12-29'),
            recurrence: Contract::RECURRENCE_MONTHLY,
            debitDate: new DateTimeImmutable('2023-04-05')
        );
        $this->assertEquals('2023-04-29', $result->format('Y-m-d'));

        $result = $this->receiptService->evaluateStartApplyAt(
            effectiveDate: new DateTime('2022-12-29'),
            recurrence: Contract::RECURRENCE_QUARTERLY,
            debitDate: new DateTimeImmutable('2023-03-05')
        );
        $this->assertEquals('2023-03-29', $result->format('Y-m-d'));


        ////////////////////
        $result = $this->receiptService->evaluateStartApplyAt(
            effectiveDate: new DateTime('2024-01-01'),
            recurrence: Contract::RECURRENCE_MONTHLY,
            debitDate: new DateTimeImmutable('2024-03-05')
        );
        $this->assertEquals('2024-04-01', $result->format('Y-m-d'));

        $result = $this->receiptService->evaluateStartApplyAt(
            effectiveDate: new DateTime('2024-01-01'),
            recurrence: Contract::RECURRENCE_SEMI_ANNUALLY,
            debitDate: new DateTimeImmutable('2024-03-05')
        );
        $this->assertEquals(null, $result);

        $result = $this->receiptService->evaluateStartApplyAt(
            effectiveDate: new DateTime('2024-01-01'),
            recurrence: Contract::RECURRENCE_SEMI_ANNUALLY,
            debitDate: new DateTimeImmutable('2024-06-05')
        );
        $this->assertEquals('2024-07-01', $result->format('Y-m-d'));


        ////////////////////
        $result = $this->receiptService->evaluateStartApplyAt(
            effectiveDate: new DateTime('2023-11-10'),
            recurrence: Contract::RECURRENCE_QUARTERLY,
            debitDate: new DateTimeImmutable('2024-03-25'),
        );
        $this->assertEquals(null, $result);

        $result = $this->receiptService->evaluateStartApplyAt(
            effectiveDate: new DateTime('2023-11-10'),
            recurrence: Contract::RECURRENCE_QUARTERLY,
            debitDate: new DateTimeImmutable('2024-04-25'),
        );
        $this->assertEquals('2024-05-10', $result->format('Y-m-d'));
    }
}
