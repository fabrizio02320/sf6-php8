<?php

namespace App\Repository;

use App\Entity\Contract;
use DateInterval;
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
        $monthlyDebitDate = $debitDate->add(new DateInterval('P1M'));
        $quarterlyDebitDate = $debitDate->add(new DateInterval('P3M'));
        $semiAnnualDebitDate = $debitDate->add(new DateInterval('P6M'));

        $qb = $this->createQueryBuilder('c');
        $qb
            ->leftJoin(
                'c.receipts',
                'r',
                Expr\Join::WITH,
                '
                (c.recurrence = :monthly AND r.startApplyAt <= :monthlyDebitDate AND r.endApplyAt >= :monthlyDebitDate)
                OR (c.recurrence = :quarterly AND r.startApplyAt <= :quarterlyDebitDate AND r.endApplyAt >= :quarterlyDebitDate)
                OR (c.recurrence = :semiAnnually AND r.startApplyAt <= :semiAnnualDebitDate AND r.endApplyAt >= :semiAnnualDebitDate)
                '
            )
            ->where('c.status = :status')
            ->andWhere('c.effectiveDate <= :monthlyDebitDate')
            ->andWhere('c.endEffectiveDate >= :monthlyDebitDate')
            ->andWhere('c.debitMode IN (:debitModes)')
            ->andWhere('c.debitDay IN (:debitDays)')
            ->andWhere('c.recurrence != :annually')
            ->andWhere('r.id IS NULL')
            ->orderBy('c.id')
            ->groupBy('c.id')
            ->setParameter('status', Contract::STATUS_IN_PROGRESS)
            ->setParameter('debitModes', $debitModes)
            ->setParameter('debitDays', $debitDays)
            ->setParameter('monthly', Contract::RECURRENCE_MONTHLY)
            ->setParameter('quarterly', Contract::RECURRENCE_QUARTERLY)
            ->setParameter('semiAnnually', Contract::RECURRENCE_SEMI_ANNUALLY)
            ->setParameter('annually', Contract::RECURRENCE_ANNUALLY)
            ->setParameter('monthlyDebitDate', $monthlyDebitDate)
            ->setParameter('quarterlyDebitDate', $quarterlyDebitDate)
            ->setParameter('semiAnnualDebitDate', $semiAnnualDebitDate)
        ;

        return $qb->getQuery()->getResult();
    }

    public function findByFiltersAndSort(array $filters, array $sort, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('c');

        foreach ($filters as $field => $value) {
            if (null !== $value && '' !== $value) {
                $qb->andWhere('c.' . $field . ' = :' . $field)
                    ->setParameter($field, $value);
            }
        }

        foreach ($sort as $field => $order) {
            $qb->addOrderBy('c.' . $field, $order);
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        $qb->addOrderBy('c.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findById(int $id): ?Contract
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.receipts', 'r')
            ->addSelect('r')
            ->leftJoin('r.transactions', 't')
            ->addSelect('t')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
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
