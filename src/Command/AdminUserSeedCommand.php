<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Crea el usuario administrador inicial del dashboard fiscal.
 */
class AdminUserSeedCommand extends Command
{
    protected static $defaultName = 'app:admin:seed';

    private AdminUserRepository $users;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    public function __construct(
        AdminUserRepository $users,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
        $this->users = $users;
        $this->em = $em;
        $this->hasher = $hasher;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Crea usuario admin del dashboard (contraseña por defecto — debe cambiarse al primer login)')
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'Usuario admin', 'admin')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Contraseña inicial', 'ChangeMeNow2026!')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Recrear si el usuario ya existe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = trim((string) $input->getOption('username'));
        $password = (string) $input->getOption('password');
        $force = (bool) $input->getOption('force');

        if ($username === '') {
            $io->error('El username no puede estar vacío.');
            return Command::FAILURE;
        }

        $existing = $this->users->findOneByUsername($username);
        if ($existing !== null && !$force) {
            $io->warning(sprintf('El usuario "%s" ya existe. Use --force para resetear contraseña.', $username));
            return Command::SUCCESS;
        }

        if ($existing !== null && $force) {
            $user = $existing;
        } else {
            $user = new AdminUser($username);
            $this->em->persist($user);
        }

        $user->setUsername($username);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setMustChangePassword(true);
        $user->setRoles(['ROLE_ADMIN']);
        $this->em->flush();

        $io->success('Usuario administrador listo.');
        $io->table(
            ['Campo', 'Valor'],
            [
                ['Usuario', $username],
                ['Contraseña inicial', $password],
                ['must_change_password', 'true (obligatorio cambiar en primer login)'],
                ['Login', '/login'],
                ['Dashboard', '/dashboard'],
            ]
        );
        $io->note('Cambie la contraseña por defecto inmediatamente en producción.');

        return Command::SUCCESS;
    }
}
