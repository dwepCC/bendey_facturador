<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FiscalEmitAttempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FiscalEmitAttempt>
 */
class FiscalEmitAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FiscalEmitAttempt::class);
    }

    /**
     * @return FiscalEmitAttempt[]
     */
    public function findByDocumentUuid(string $documentUuid): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.documentUuid = :uuid')
            ->setParameter('uuid', $documentUuid)
            ->orderBy('a.attemptNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
