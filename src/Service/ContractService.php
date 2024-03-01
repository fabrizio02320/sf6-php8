<?php

namespace App\Service;

use App\Entity\Contract;
use App\Repository\ContractRepository;
use DateTimeImmutable;
use Exception;

class  ContractService
{
    public function __construct(private ContractRepository $contractRepository) {}

    /**
     * @param DateTimeImmutable $debitDate
     * @return Contract[]
     * @throws Exception
     */
    public function findContractsToBilling(DateTimeImmutable $debitDate): array
    {
        $contracts = [];
        // find about SEPA and CB mode
        $debitDaysConcerned = $this->getDebitDaysConcerned($debitDate);
        $contracts[] = $this->contractRepository->findContractsToBilling(
            $debitDate,
            $debitDaysConcerned,
            [Contract::DEBIT_MODE_CB, Contract::DEBIT_MODE_SEPA],
        );

        // find about nothing mode
        $debitDateWithNothing = $debitDate->modify('+30 days');
        $debitDaysConcerned = $this->getDebitDaysConcerned($debitDateWithNothing);
        $contracts[] = $this->contractRepository->findContractsToBilling(
            $debitDateWithNothing,
            $debitDaysConcerned,
            [Contract::DEBIT_MODE_NOTHING],
        );

        // gestion des relances a voir plus tard

        return $contracts;
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
