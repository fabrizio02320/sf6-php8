<?php

namespace App\Factory;

use App\Entity\Contract;
use DateTimeInterface;

class ContractFactory
{
    public function create(
        string $status,
        int $debitDay,
        string $debitMode,
        DateTimeInterface $effectiveDate,
        DateTimeInterface $endEffectiveDate,
        float $annualPrimeTtc,
        string $recurrence,
        ?string $externalId = null,
        ?\DateTimeImmutable $createdAt = null,
    ): Contract {
        if (false === in_array($status, Contract::ALL_STATUS, true)) {
            throw new \Exception(sprintf('Create contract with status %s is forbidden', $status));
        }

        if (null === $createdAt) {
            $createdAt = new \DateTimeImmutable();
        }

        if (null === $externalId) {
            $externalId = sprintf('%s%s', $createdAt->getTimestamp(), random_int(0, 1000));
        }

        $contract = new Contract();
        $contract
            ->setStatus($status)
            ->setDebitDay($debitDay)
            ->setDebitMode($debitMode)
            ->setEffectiveDate($effectiveDate)
            ->setEndEffectiveDate($endEffectiveDate)
            ->setAnnualPrimeTtc($annualPrimeTtc)
            ->setRecurrence($recurrence)
            ->setCreatedAt($createdAt)
            ->setExternalId($externalId);

        return $contract;
    }
}
