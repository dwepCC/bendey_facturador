<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Empresa;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
