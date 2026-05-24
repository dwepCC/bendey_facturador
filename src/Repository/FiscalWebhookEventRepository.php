<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FiscalWebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FiscalWebhookEvent>
 */
class FiscalWebhookEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FiscalWebhookEvent::class);
    }

    /**
     * @return FiscalWebhookEvent[]
     */
    public function findByDocumentUuid(string $documentUuid): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.documentUuid = :uuid')
            ->setParameter('uuid', $documentUuid)
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
