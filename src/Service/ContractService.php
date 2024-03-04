<?php

namespace App\Service;

use App\Entity\Contract;
use App\Repository\ContractRepository;
use DateTimeImmutable;
use Exception;

class  ContractService
{
    public function __construct(
        private ContractRepository $contractRepository,
        private ReceiptService $receiptService,
    ) {}

    /**
     * @param DateTimeImmutable $debitDate
     * @return Contract[]
     * @throws Exception
     */
    public function findContractsToBilling(DateTimeImmutable $debitDate): array
    {
        // find about SEPA and CB mode
        $debitDaysConcerned = $this->getDebitDaysConcerned($debitDate);
        $contracts = $this->contractRepository->findContractsToBilling(
            $debitDate,
            $debitDaysConcerned,
            [Contract::DEBIT_MODE_CB, Contract::DEBIT_MODE_SEPA],
        );

        // evaluate if the contract can be debited at the date
        $contracts = array_filter($contracts, function (Contract $contract) use ($debitDate) {
            $startApplyAt = $this->receiptService->evaluateStartApplyAt(
                $contract->getEffectiveDate(),
                $contract->getRecurrence(),
                $debitDate
            );

            if (null === $startApplyAt) {
                return false;
            }

            return true;
        });

        // find about nothing mode
        $debitDateWithNothing = $debitDate->modify('+30 days');
        $debitDaysConcerned = $this->getDebitDaysConcerned($debitDateWithNothing);
        $contractsWithNothing = $this->contractRepository->findContractsToBilling(
            $debitDateWithNothing,
            $debitDaysConcerned,
            [Contract::DEBIT_MODE_NOTHING],
        );
        $contractsWithNothing = array_filter(
            $contractsWithNothing, function (Contract $contract) use ($debitDateWithNothing)
            {
                $startApplyAt = $this->receiptService->evaluateStartApplyAt(
                    $contract->getEffectiveDate(),
                    $contract->getRecurrence(),
                    $debitDateWithNothing
                );

                if (null === $startApplyAt) {
                    return false;
                }

                return true;
            }
        );

        return array_merge($contracts, $contractsWithNothing);
    }

    public function getDebitDaysConcerned(DateTimeImmutable $debitDate): array
    {
        $debitDay = (int)$debitDate->format('j');
        $debitDayConcerned = [$debitDay];
        // if debitDate is the last day of the month
        if ($debitDay === (int)$debitDate->format('t')) {
            for ($i = $debitDay + 1; $i <= 31; $i++) {
                $debitDayConcerned[] = $i;
            }
        }
        return $debitDayConcerned;
    }
}
