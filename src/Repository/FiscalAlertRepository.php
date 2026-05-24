<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FiscalAlert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FiscalAlert>
 */
class FiscalAlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FiscalAlert::class);
    }

    /**
     * @return FiscalAlert[]
     */
    public function findOpen(int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.resolvedAt IS NULL')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countOpen(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.resolvedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
