<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FiscalTenantMetric;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FiscalTenantMetric>
 */
class FiscalTenantMetricRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FiscalTenantMetric::class);
    }

    public function findForPeriod(
        int $tenantId,
        \DateTimeImmutable $date,
        string $periodType,
        ?string $provider
    ): ?FiscalTenantMetric {
        $qb = $this->createQueryBuilder('m')
            ->where('m.tenantId = :tid')
            ->andWhere('m.periodDate = :d')
            ->andWhere('m.periodType = :pt')
            ->setParameter('tid', $tenantId)
            ->setParameter('d', $date->format('Y-m-d'))
            ->setParameter('pt', $periodType);

        if ($provider === null || $provider === '') {
            $qb->andWhere('m.provider IS NULL');
        } else {
            $qb->andWhere('m.provider = :p')->setParameter('p', $provider);
        }

        return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }
}
