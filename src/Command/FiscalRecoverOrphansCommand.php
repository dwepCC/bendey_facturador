<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Fiscal\FiscalOrphanRecoveryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Reencola documentos fiscal_documents en queued sin intentos de emisión (huérfanos Redis).
 */
#[AsCommand(name: 'app:fiscal:recover-orphans')]
class FiscalRecoverOrphansCommand extends Command
{
    private FiscalOrphanRecoveryService $recovery;

    public function __construct(FiscalOrphanRecoveryService $recovery)
    {
        parent::__construct();
        $this->recovery = $recovery;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Reencola documentos queued huérfanos (sin job en Redis / sin emit attempts)')
            ->addOption('min-age', null, InputOption::VALUE_REQUIRED, 'Minutos en queued antes de recuperar', '5')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Máximo de documentos por ejecución', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $minAge = (int) $input->getOption('min-age');
        $limit = (int) $input->getOption('limit');

        $result = $this->recovery->recover($minAge, $limit);

        $io->success(sprintf(
            'Recuperados: %d | Omitidos: %d',
            $result['recovered'],
            $result['skipped']
        ));

        foreach ($result['errors'] as $error) {
            $io->warning($error);
        }

        return Command::SUCCESS;
    }
}
