<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FiscalDocument;
use App\Entity\FiscalEmitAttempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FiscalDocument>
 */
class FiscalDocumentRepository extends ServiceEntityRepository
{
    /** Tipos no fiscales (notas de venta, etc.) */
    private const NON_FISCAL_TYPES = ['00', 'NV'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FiscalDocument::class);
    }

    /**
     * @param array<string, mixed> $filters
     * @return FiscalDocument[]
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createFilteredQuery($filters);
        $limit = (int) ($filters['limit'] ?? 50);
        $limit = min(200, max(1, $limit));

        if (!empty($filters['cursor'])) {
            $this->applyCursor($qb, (string) $filters['cursor']);
            $qb->orderBy('d.createdAt', 'DESC')->addOrderBy('d.id', 'DESC');
            $qb->setMaxResults($limit + 1);

            return $qb->getQuery()->getResult();
        }

        $offset = max(0, (int) ($filters['offset'] ?? 0));
        return $qb->orderBy('d.createdAt', 'DESC')->addOrderBy('d.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countByFilters(array $filters): int
    {
        $qb = $this->createFilteredQuery($filters);
        return (int) $qb->select('COUNT(d.id)')->getQuery()->getSingleScalarResult();
    }

    /**
     * @return FiscalDocument[]
     */
    public function findFiltered(
        ?string $tenantSlug,
        ?string $status,
        ?string $documentType,
        ?\DateTimeInterface $from,
        ?\DateTimeInterface $to,
        int $limit = 50,
        int $offset = 0,
        ?string $provider = null,
        ?string $sendMode = null,
        ?string $series = null,
        ?string $number = null,
        bool $errorsOnly = false
    ): array {
        return $this->findByFilters([
            'tenant_slug' => $tenantSlug,
            'status' => $status,
            'document_type' => $documentType,
            'from' => $from,
            'to' => $to,
            'limit' => $limit,
            'offset' => $offset,
            'provider' => $provider,
            'send_mode' => $sendMode,
            'series' => $series,
            'number' => $number,
            'errors_only' => $errorsOnly,
            'electronic_only' => true,
        ]);
    }

    public function countFiltered(
        ?string $tenantSlug,
        ?string $status,
        ?string $documentType,
        ?\DateTimeInterface $from,
        ?\DateTimeInterface $to,
        ?string $provider = null,
        ?string $sendMode = null,
        ?string $series = null,
        ?string $number = null,
        bool $errorsOnly = false
    ): int {
        return $this->countByFilters([
            'tenant_slug' => $tenantSlug,
            'status' => $status,
            'document_type' => $documentType,
            'from' => $from,
            'to' => $to,
            'provider' => $provider,
            'send_mode' => $sendMode,
            'series' => $series,
            'number' => $number,
            'errors_only' => $errorsOnly,
            'electronic_only' => true,
        ]);
    }

    /**
     * @return array<string, int>
     */
    public function countByStatus(?string $tenantSlug = null, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.status AS status, COUNT(d.id) AS cnt')
            ->groupBy('d.status');
        $this->applyElectronicOnly($qb, true);
        if ($tenantSlug !== null && $tenantSlug !== '') {
            $qb->andWhere('d.tenantSlug = :slug')->setParameter('slug', $tenantSlug);
        }
        if ($from !== null) {
            $qb->andWhere('d.createdAt >= :from')->setParameter('from', $from);
        }
        if ($to !== null) {
            $qb->andWhere('d.createdAt <= :to')->setParameter('to', $to);
        }

        $rows = $qb->getQuery()->getArrayResult();
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['status']] = (int) $row['cnt'];
        }

        return $out;
    }

    /**
     * @return array<int, array{tenant_slug: string, total: int}>
     */
    public function countByTenant(?string $tenantSlug = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.tenantSlug AS tenant_slug, COUNT(d.id) AS total')
            ->groupBy('d.tenantSlug')
            ->orderBy('total', 'DESC')
            ->setMaxResults(min(200, max(1, $limit)));
        $this->applyElectronicOnly($qb, true);
        if ($tenantSlug !== null && $tenantSlug !== '') {
            $qb->andWhere('d.tenantSlug = :slug')->setParameter('slug', $tenantSlug);
        }
        $rows = $qb->getQuery()->getArrayResult();
        $out = [];
        foreach ($rows as $row) {
            $out[] = ['tenant_slug' => (string) $row['tenant_slug'], 'total' => (int) $row['total']];
        }

        return $out;
    }

    public function findByTenantSale(int $tenantId, int $saleId): ?FiscalDocument
    {
        return $this->findOneBy(['tenantId' => $tenantId, 'saleId' => $saleId], ['id' => 'DESC']);
    }

    /**
     * @param string[] $statuses
     * @return FiscalDocument[]
     */
    public function findByStatuses(array $statuses, int $limit = 50): array
    {
        if ($statuses === []) {
            return [];
        }
        return $this->createQueryBuilder('d')
            ->where('d.status IN (:st)')
            ->andWhere('d.documentType NOT IN (:nonFiscal)')
            ->setParameter('st', $statuses)
            ->setParameter('nonFiscal', self::NON_FISCAL_TYPES)
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByStatuses(array $statuses): int
    {
        if ($statuses === []) {
            return 0;
        }
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.status IN (:st)')
            ->andWhere('d.documentType NOT IN (:nonFiscal)')
            ->setParameter('st', $statuses)
            ->setParameter('nonFiscal', self::NON_FISCAL_TYPES)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function createFilteredQuery(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d');
        $electronicOnly = !isset($filters['electronic_only']) || $filters['electronic_only'] !== false;
        $this->applyElectronicOnly($qb, $electronicOnly);

        if (!empty($filters['tenant_slug'])) {
            $qb->andWhere('d.tenantSlug = :slug')->setParameter('slug', (string) $filters['tenant_slug']);
        }
        if (!empty($filters['tenant_id'])) {
            $qb->andWhere('d.tenantId = :tid')->setParameter('tid', (int) $filters['tenant_id']);
        }
        if (!empty($filters['sale_id'])) {
            $qb->andWhere('d.saleId = :saleId')->setParameter('saleId', (int) $filters['sale_id']);
        }
        if (!empty($filters['status'])) {
            $qb->andWhere('d.status = :status')->setParameter('status', (string) $filters['status']);
        }
        if (!empty($filters['document_type'])) {
            $qb->andWhere('d.documentType = :type')->setParameter('type', (string) $filters['document_type']);
        }
        if (!empty($filters['provider'])) {
            $qb->andWhere('d.provider = :provider')->setParameter('provider', (string) $filters['provider']);
        }
        if (!empty($filters['send_mode'])) {
            $qb->andWhere('d.sendMode = :sendMode')->setParameter('sendMode', (string) $filters['send_mode']);
        }
        if (!empty($filters['series'])) {
            $qb->andWhere('d.series = :series')->setParameter('series', (string) $filters['series']);
        }
        if (!empty($filters['number'])) {
            $qb->andWhere('d.number = :number')->setParameter('number', (string) $filters['number']);
        }
        if (!empty($filters['customer_email'])) {
            $qb->andWhere('d.customerEmail LIKE :email')->setParameter('email', '%' . (string) $filters['customer_email'] . '%');
        }
        if (!empty($filters['customer_name'])) {
            $qb->andWhere('d.snapshotJson LIKE :custName')
                ->setParameter('custName', '%' . addcslashes((string) $filters['customer_name'], '%_') . '%');
        }
        if (!empty($filters['company_ruc'])) {
            $ruc = preg_replace('/\D/', '', (string) $filters['company_ruc']);
            if ($ruc !== '') {
                $qb->andWhere('(d.snapshotJson LIKE :ruc1 OR d.snapshotJson LIKE :ruc2)')
                    ->setParameter('ruc1', '%"company_ruc":"' . $ruc . '"%')
                    ->setParameter('ruc2', '%"ruc":"' . $ruc . '"%');
            }
        }
        if (!empty($filters['email_sent'])) {
            $sent = filter_var($filters['email_sent'], FILTER_VALIDATE_BOOLEAN);
            if ($sent) {
                $qb->andWhere('d.emailStatus = :esSent')->setParameter('esSent', 'sent');
            } else {
                $qb->andWhere('d.emailStatus IS NULL OR d.emailStatus != :esSent')
                    ->setParameter('esSent', 'sent');
            }
        }
        if (!empty($filters['from']) && $filters['from'] instanceof \DateTimeInterface) {
            $qb->andWhere('d.createdAt >= :from')->setParameter('from', $filters['from']);
        }
        if (!empty($filters['to']) && $filters['to'] instanceof \DateTimeInterface) {
            $qb->andWhere('d.createdAt <= :to')->setParameter('to', $filters['to']);
        }
        if (!empty($filters['errors_only'])) {
            $qb->andWhere('d.status IN (:err)')->setParameter('err', ['error', 'rejected', 'retrying']);
        }
        if (!empty($filters['pending_only'])) {
            $qb->andWhere('d.status IN (:pend)')->setParameter('pend', ['pending', 'queued', 'sending']);
        }
        if (!empty($filters['retry_only'])) {
            $qb->andWhere('d.status IN (:retry)')->setParameter('retry', ['retrying', 'error']);
        }
        if (!empty($filters['email_pending'])) {
            $qb->andWhere('d.emailStatus IN (:em) OR (d.emailStatus IS NULL AND d.status = :acc)')
                ->setParameter('em', ['pending', 'failed'])
                ->setParameter('acc', FiscalDocument::STATUS_ACCEPTED);
        }

        return $qb;
    }

    /**
     * Documentos queued sin ningún intento de emisión (probable huérfano de cola Redis).
     *
     * @return FiscalDocument[]
     */
    public function findOrphanedQueued(\DateTimeInterface $queuedBefore, int $limit = 50): array
    {
        $limit = min(500, max(1, $limit));

        return $this->createQueryBuilder('d')
            ->leftJoin(FiscalEmitAttempt::class, 'a', 'WITH', 'a.documentUuid = d.documentUuid')
            ->where('d.status = :queued')
            ->andWhere('a.id IS NULL')
            ->andWhere('(d.queuedAt IS NULL OR d.queuedAt < :before)')
            ->setParameter('queued', FiscalDocument::STATUS_QUEUED)
            ->setParameter('before', $queuedBefore)
            ->orderBy('d.queuedAt', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    private function applyElectronicOnly(QueryBuilder $qb, bool $enabled): void
    {
        if (!$enabled) {
            return;
        }
        $qb->andWhere('d.documentType NOT IN (:nonFiscal)')->setParameter('nonFiscal', self::NON_FISCAL_TYPES);
    }

    private function applyCursor(QueryBuilder $qb, string $cursor): void
    {
        $decoded = json_decode(base64_decode($cursor, true) ?: '', true);
        if (!is_array($decoded) || empty($decoded['id']) || empty($decoded['created_at'])) {
            return;
        }
        try {
            $createdAt = new \DateTimeImmutable((string) $decoded['created_at']);
        } catch (\Exception $e) {
            return;
        }
        $id = (int) $decoded['id'];
        $qb->andWhere('(d.createdAt < :cAt) OR (d.createdAt = :cAt AND d.id < :cId)')
            ->setParameter('cAt', $createdAt)
            ->setParameter('cId', $id);
    }

    /**
     * @param FiscalDocument[] $docs
     */
    public function buildNextCursor(array $docs, int $limit): ?string
    {
        if (count($docs) <= $limit) {
            return null;
        }
        array_pop($docs);
        $last = end($docs);
        if (!$last instanceof FiscalDocument) {
            return null;
        }

        return base64_encode(json_encode([
            'id' => $last->getId(),
            'created_at' => $last->getCreatedAt()->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR));
    }
}
