<?php

namespace App\Tests\Service;

use App\Factory\ReceiptFactory;
use App\Repository\ContractRepository;
use App\Service\ContractService;
use App\Service\ReceiptService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ContractServiceTest extends TestCase
{
    use ProphecyTrait;

    private ContractService $contractService;

    protected function setUp(): void
    {
        $receiptFactory = new ReceiptFactory();
        $receiptService = new ReceiptService($receiptFactory);

        $this->contractService = new ContractService(
            $this->prophesize(ContractRepository::class)->reveal(),
            $receiptService
        );
    }

    public function testGetDebitDayConcerned(): void
    {
        $debitDate = new DateTimeImmutable('2023-02-28');
        $result = $this->contractService->getDebitDaysConcerned($debitDate);
        $this->assertEquals([28, 29, 30, 31], $result);

        $debitDate = new DateTimeImmutable('2024-02-28');
        $result = $this->contractService->getDebitDaysConcerned($debitDate);
        $this->assertEquals([28], $result);

        $debitDate = new DateTimeImmutable('2024-02-29');
        $result = $this->contractService->getDebitDaysConcerned($debitDate);
        $this->assertEquals([29, 30, 31], $result);

        $debitDate = new DateTimeImmutable('2024-02-01');
        $result = $this->contractService->getDebitDaysConcerned($debitDate);
        $this->assertEquals([1], $result);

        $debitDate = new DateTimeImmutable('2024-03-30');
        $result = $this->contractService->getDebitDaysConcerned($debitDate);
        $this->assertEquals([30], $result);

        $debitDate = new DateTimeImmutable('2024-03-31');
        $result = $this->contractService->getDebitDaysConcerned($debitDate);
        $this->assertEquals([31], $result);

        $debitDate = new DateTimeImmutable('2024-04-30');
        $result = $this->contractService->getDebitDaysConcerned($debitDate);
        $this->assertEquals([30, 31], $result);
    }
}
