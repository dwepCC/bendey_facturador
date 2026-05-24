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
 * Simula carga concurrente multi-tenant (idempotencia + encolado, sin SUNAT).
 */
class FiscalConcurrentLoadCommand extends Command
{
    protected static $defaultName = 'app:fiscal:load-test';

    private FiscalDocumentService $documents;

    public function __construct(FiscalDocumentService $documents)
    {
        parent::__construct();
        $this->documents = $documents;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Load test multi-tenant fiscal (encolado idempotente)')
            ->addOption('tenants', null, InputOption::VALUE_REQUIRED, 'Tenants', '100')
            ->addOption('docs-per-tenant', null, InputOption::VALUE_REQUIRED, 'Docs por tenant', '2')
            ->addOption('dup-factor', null, InputOption::VALUE_REQUIRED, 'Duplicados por doc', '3');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tenants = max(1, (int) $input->getOption('tenants'));
        $perTenant = max(1, (int) $input->getOption('docs-per-tenant'));
        $dupFactor = max(1, (int) $input->getOption('dup-factor'));

        $start = microtime(true);
        $created = 0;
        $deduped = 0;
        $errors = 0;

        for ($t = 1; $t <= $tenants; $t++) {
            $slug = 'load-tenant-' . $t;
            for ($d = 1; $d <= $perTenant; $d++) {
                $saleId = 800000 + ($t * 1000) + $d;
                $payload = [
                    'tenant_id' => $t,
                    'tenant_slug' => $slug,
                    'sale_id' => $saleId,
                    'automatic_send' => false,
                    'snapshot' => [
                        'tipoDoc' => '03',
                        'serie' => 'B' . str_pad((string) ($t % 100), 3, '0', STR_PAD_LEFT),
                        'correlativo' => (string) $d,
                        'company' => ['ruc' => '20100000001'],
                        'customer' => ['email' => 'load' . $t . '@example.com'],
                    ],
                ];
                $firstUuid = null;
                for ($dup = 0; $dup < $dupFactor; $dup++) {
                    try {
                        $doc = $this->documents->enqueue($payload);
                        if ($firstUuid === null) {
                            $firstUuid = $doc->getDocumentUuid();
                            $created++;
                        } elseif ($doc->getDocumentUuid() === $firstUuid) {
                            $deduped++;
                        } else {
                            $created++;
                        }
                    } catch (\Throwable $e) {
                        $errors++;
                    }
                }
            }
        }

        $elapsed = round(microtime(true) - $start, 3);
        $io->success(sprintf(
            'Load test: tenants=%d docs=%d dup=%d created=%d deduped=%d errors=%d elapsed=%ss ops=%s/s',
            $tenants,
            $perTenant,
            $dupFactor,
            $created,
            $deduped,
            $errors,
            $elapsed,
            $elapsed > 0 ? round(($created + $deduped) / $elapsed, 1) : 0
        ));
        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
