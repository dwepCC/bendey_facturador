<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Empresa;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Empresa>
 */
class EmpresaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Empresa::class);
    }

    /**
     * Devuelve todas las empresas como array [ ruc => [ SOL_USER => ..., ... ] ]
     * para compatibilidad con el formato esperado por SeeFactory y FileConfigProvider.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getCompaniesArray(): array
    {
        $all = $this->findBy([], ['ruc' => 'ASC']);
        $result = [];
        foreach ($all as $e) {
            $result[$e->getRuc()] = [
                'SOL_USER' => $e->getSolUser(),
                'SOL_PASS' => $e->getSolPass(),
                'certificate' => $e->getCertificate(),
                'logo' => $e->getLogo(),
                'ambiente' => $e->getAmbiente(),
                'tenant_id' => $e->getTenantId(),
                'tenant_slug' => $e->getTenantSlug(),
                'send_mode' => $e->getSendMode(),
                'provider' => $e->getProvider(),
                'connection_type' => $e->getConnectionType(),
                'pse_base_url' => $e->getPseBaseUrl(),
                'pse_user' => $e->getPseUser(),
                'connection_status' => $e->getConnectionStatus(),
                'connection_error' => $e->getConnectionError(),
                'last_connection_check' => $e->getLastConnectionCheck()
                    ? $e->getLastConnectionCheck()->format(DATE_ATOM) : null,
                'automatic_send' => $e->isAutomaticSend(),
                'email_enabled' => $e->isEmailEnabled(),
                'retry_enabled' => $e->isRetryEnabled(),
                'enabled' => $e->isEnabled(),
            ];
        }
        return $result;
    }

    public function findByRuc(string $ruc): ?Empresa
    {
        return $this->findOneBy(['ruc' => $ruc]);
    }

    /**
     * @param string[] $slugs
     * @return array<string, Empresa>
     */
    public function findByTenantSlugs(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter(array_map('strval', $slugs))));
        if ($slugs === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('e')
            ->andWhere('e.tenantSlug IN (:slugs)')
            ->setParameter('slugs', $slugs)
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $empresa) {
            if (!$empresa instanceof Empresa || $empresa->getTenantSlug() === null) {
                continue;
            }
            $out[$empresa->getTenantSlug()] = $empresa;
        }

        return $out;
    }

    /**
     * Listado admin paginado (panel fiscal).
     *
     * @param array<string, mixed> $filters
     * @return Empresa[]
     */
    public function findForAdminList(array $filters, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('e');
        $this->applyAdminListFilters($qb, $filters);
        $qb->orderBy('e.lastConnectionCheck', 'DESC')
            ->addOrderBy('e.ruc', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countForAdminList(array $filters): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.ruc)');
        $this->applyAdminListFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyAdminListFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['from']) && $filters['from'] instanceof \DateTimeInterface) {
            $qb->andWhere('e.lastConnectionCheck >= :from')->setParameter('from', $filters['from']);
        }
        if (!empty($filters['to']) && $filters['to'] instanceof \DateTimeInterface) {
            $qb->andWhere('e.lastConnectionCheck <= :to')->setParameter('to', $filters['to']);
        }
        if (!empty($filters['ruc'])) {
            $ruc = (string) preg_replace('/\D/', '', (string) $filters['ruc']);
            if ($ruc !== '') {
                $qb->andWhere('e.ruc LIKE :ruc')->setParameter('ruc', $ruc . '%');
            }
        }
        if (!empty($filters['tenant_slug'])) {
            $qb->andWhere('e.tenantSlug = :slug')->setParameter('slug', (string) $filters['tenant_slug']);
        }
        if (!empty($filters['send_mode'])) {
            $qb->andWhere('e.sendMode = :sendMode')->setParameter('sendMode', (string) $filters['send_mode']);
        }
        if (!empty($filters['connection_status'])) {
            $qb->andWhere('e.connectionStatus = :connStatus')
                ->setParameter('connStatus', (string) $filters['connection_status']);
        }
        if (array_key_exists('enabled', $filters) && $filters['enabled'] !== null) {
            $qb->andWhere('e.enabled = :enabled')->setParameter('enabled', (bool) $filters['enabled']);
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . addcslashes(mb_strtolower($q), '%_') . '%';
            $qb->andWhere(
                'LOWER(e.ruc) LIKE :q OR LOWER(e.tenantSlug) LIKE :q OR LOWER(e.provider) LIKE :q OR LOWER(e.solUser) LIKE :q'
            )->setParameter('q', $like);
        }
    }
}
