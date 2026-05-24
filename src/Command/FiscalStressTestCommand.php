<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Fiscal\FiscalDocumentService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prueba de carga/idempotencia: N tenants × M emisiones con misma fingerprint (deduplica).
 */
class FiscalStressTestCommand extends Command
{
    protected static $defaultName = 'app:fiscal:stress-test';

    private FiscalDocumentService $documents;

    public function __construct(FiscalDocumentService $documents)
    {
        parent::__construct();
        $this->documents = $documents;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Stress test idempotencia fiscal (sin enviar a SUNAT)')
            ->addOption('tenants', null, InputOption::VALUE_REQUIRED, 'Tenants simulados', '10')
            ->addOption('per-tenant', null, InputOption::VALUE_REQUIRED, 'Emisiones duplicadas por tenant', '5');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tenants = max(1, (int) $input->getOption('tenants'));
        $perTenant = max(1, (int) $input->getOption('per-tenant'));

        $created = 0;
        $deduped = 0;

        for ($t = 1; $t <= $tenants; $t++) {
            $slug = 'stress-tenant-' . $t;
            $saleId = 900000 + $t;
            $payload = [
                'tenant_id' => $t,
                'tenant_slug' => $slug,
                'sale_id' => $saleId,
                'automatic_send' => false,
                'snapshot' => [
                    'tipoDoc' => '03',
                    'serie' => 'B001',
                    'correlativo' => (string) $t,
                    'company' => ['ruc' => '2010000000' . $t],
                    'customer' => ['email' => 'stress@example.com'],
                ],
            ];

            $firstUuid = null;
            for ($i = 0; $i < $perTenant; $i++) {
                $doc = $this->documents->enqueue($payload);
                if ($firstUuid === null) {
                    $firstUuid = $doc->getDocumentUuid();
                    $created++;
                } elseif ($doc->getDocumentUuid() === $firstUuid) {
                    $deduped++;
                } else {
                    $created++;
                }
            }
        }

        $io->success(sprintf(
            'Stress test: tenants=%d dup_per_tenant=%d created=%d deduped=%d',
            $tenants,
            $perTenant,
            $created,
            $deduped
        ));
        return Command::SUCCESS;
    }
}
