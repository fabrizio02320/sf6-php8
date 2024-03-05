<?php

namespace App\Repository;

use App\Entity\Contract;
use App\Entity\Receipt;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contract>
 *
 * @method Contract|null find($id, $lockMode = null, $lockVersion = null)
 * @method Contract|null findOneBy(array $criteria, array $orderBy = null)
 * @method Contract[]    findAll()
 * @method Contract[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    public function findContractsToBilling(
        DateTimeImmutable $debitDate,
        array $debitDays,
        array $debitModes,
    ) {
        $quarterlyDebitDate = $debitDate->add(new DateInterval('P3M'));
        $semiAnnualDebitDate = $debitDate->add(new DateInterval('P6M'));

        $qb = $this->createQueryBuilder('c');
        $qb
            ->leftJoin(
                'c.receipts',
                'r',
                Expr\Join::WITH,
                '
                (c.recurrence = :monthly AND r.startApplyAt <= :debitDate AND r.endApplyAt >= :debitDate)
                OR (c.recurrence = :quarterly AND r.startApplyAt <= :quarterlyDebitDate AND r.endApplyAt >= :quarterlyDebitDate)
                OR (c.recurrence = :semiAnnually AND r.startApplyAt <= :semiAnnualDebitDate AND r.endApplyAt >= :semiAnnualDebitDate)
                '
            )
            ->where('c.status = :status')
            ->andWhere('c.effectiveDate <= :debitDate')
            ->andWhere('c.endEffectiveDate >= :debitDate')
            ->andWhere('c.debitMode IN (:debitModes)')
            ->andWhere('c.debitDay IN (:debitDays)')
            ->andWhere('c.recurrence != :annually')
            ->andWhere('r.id IS NULL')
            ->orderBy('c.id')
            ->groupBy('c.id')
            ->setParameter('status', Contract::STATUS_IN_PROGRESS)
            ->setParameter('debitDate', $debitDate)
            ->setParameter('debitModes', $debitModes)
            ->setParameter('debitDays', $debitDays)
            ->setParameter('monthly', Contract::RECURRENCE_MONTHLY)
            ->setParameter('quarterly', Contract::RECURRENCE_QUARTERLY)
            ->setParameter('semiAnnually', Contract::RECURRENCE_SEMI_ANNUALLY)
            ->setParameter('annually', Contract::RECURRENCE_ANNUALLY)
            ->setParameter('quarterlyDebitDate', $quarterlyDebitDate)
            ->setParameter('semiAnnualDebitDate', $semiAnnualDebitDate)
        ;

        return $qb->getQuery()->getResult();
    }

    public function clear(): void
    {
        $this->_em->clear();
    }

    public function save(Contract $contract): void
    {
        $this->_em->persist($contract);
    }

    public function flush(): void
    {
        $this->_em->flush();
    }
}
