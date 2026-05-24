<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FiscalDocument;
use App\Entity\OutboundEmailLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OutboundEmailLog>
 */
class OutboundEmailLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutboundEmailLog::class);
    }

    /**
     * @return OutboundEmailLog[]
     */
    public function findByDocumentUuid(string $documentUuid): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.documentUuid = :uuid')
            ->setParameter('uuid', $documentUuid)
            ->orderBy('e.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countPending(?string $tenantSlug = null): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.status = :st')
            ->setParameter('st', OutboundEmailLog::STATUS_PENDING);
        if ($tenantSlug !== null && $tenantSlug !== '') {
            $qb->innerJoin(FiscalDocument::class, 'd', 'WITH', 'd.documentUuid = e.documentUuid')
                ->andWhere('d.tenantSlug = :slug')
                ->setParameter('slug', $tenantSlug);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
