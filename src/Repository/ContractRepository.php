<?php

namespace App\Repository;

use App\Entity\Contract;
use DateTime;
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

    public function findContractsToBilling(\DateTimeImmutable $debitDate, array $debitDays, array $debitModes)
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->leftJoin(
                'c.receipts',
                'r',
                Expr\Join::WITH,
                'r.startApplyAt <= :debitDate AND r.endApplyAt >= :debitDate'
            )
            ->where('c.status = :status')
            ->andWhere('c.effectiveDate <= :debitDate')
            ->andWhere('c.endEffectiveDate >= :debitDate')
            ->andWhere('c.debitMode IN (:debitModes)')
            ->andWhere('c.debitDay IN (:debitDays)')
            ->orderBy('c.id')
            ->groupBy('c.id')
            ->having('COUNT(r.id) = 0')
            ->setParameter('status', Contract::STATUS_IN_PROGRESS)
            ->setParameter('debitDate', $debitDate)
            ->setParameter('debitModes', $debitModes)
            ->setParameter('debitDays', $debitDays)
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
