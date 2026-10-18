<?php

declare(strict_types=1);

namespace App\Command;

use App\Security\SecretCipher;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cifra las credenciales de empresa que siguen en texto plano (guardadas antes de
 * EncryptedStringType). Por defecto SOLO cuenta; con --apply escribe. Nunca imprime valores.
 */
#[AsCommand(name: 'app:fiscal:encrypt-secrets', description: 'Cifra clave SOL y secretos GRE / API de validez que siguen en texto plano')]
class FiscalEncryptSecretsCommand extends Command
{
    /** Columnas de `empresa` con EncryptedStringType. */
    public const COLUMNS = ['sol_pass', 'gre_client_secret', 'cpe_api_client_secret'];

    public function __construct(private readonly Connection $db)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Escribir (sin esto solo se cuenta)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!SecretCipher::isConfigured()) {
            $io->error('FISCAL_SECRETS_KEY no está configurada: no se puede cifrar.');

            return Command::FAILURE;
        }
        $apply = (bool) $input->getOption('apply');

        $rows = $this->db->fetchAllAssociative('SELECT ruc, ' . implode(', ', self::COLUMNS) . ' FROM empresa');
        $pending = 0;
        $done = 0;
        foreach ($rows as $row) {
            $updates = [];
            foreach (self::COLUMNS as $col) {
                $v = $row[$col] ?? null;
                if (is_string($v) && $v !== '' && !SecretCipher::isEncrypted($v)) {
                    $updates[$col] = SecretCipher::encrypt($v);
                }
            }
            if ($updates === []) {
                continue;
            }
            $pending += count($updates);
            $io->writeln(sprintf('%s: %s', $row['ruc'], implode(', ', array_keys($updates))));
            if ($apply) {
                $this->db->update('empresa', $updates, ['ruc' => $row['ruc']]);
                $done += count($updates);
            }
        }

        if ($apply) {
            $io->success(sprintf('%d credencial(es) cifrada(s).', $done));
        } else {
            $io->note(sprintf('%d credencial(es) en texto plano. Ejecute con --apply para cifrarlas.', $pending));
        }

        return Command::SUCCESS;
    }
}
