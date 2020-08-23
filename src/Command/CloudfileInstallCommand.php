<?php

namespace App\Command;

use App\Entity\Disk;
use App\Entity\User;
use App\Entity\Config;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CloudfileInstallCommand extends Command
{
    protected static $defaultName = 'cloudfile:install';

    private $params;
    private $passwordEncoder;
    private $container;
    private $em;
    private $input;
    private $output;
    private $io;

    public function __construct(ParameterBagInterface $params, UserPasswordEncoderInterface $passwordEncoder, ContainerInterface $container) {
        $this->params = $params;
        $this->passwordEncoder = $passwordEncoder;
        parent::__construct();
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager();
    }

    protected function configure()
    {
        $this
            ->setDescription('Install Cloudfile API server.')
            ->addOption('username', 'u', InputOption::VALUE_REQUIRED, 'Account username')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Account password')
            ->addOption('disk-name', 'dn', InputOption::VALUE_REQUIRED, 'Disk name for new disk')
            ->addOption('disk-path', 'dp', InputOption::VALUE_REQUIRED, 'Disk path for new disk')
            ->addOption('backup-host', 'bh', InputOption::VALUE_REQUIRED, 'Host server used to backup Cloudfile instances')
            ->addOption('backup-apikey', 'bp', InputOption::VALUE_REQUIRED, 'ApiKey to the volume used for backup Cloudfile instance')
            ->addOption('cloudfile-password', 'cp', InputOption::VALUE_REQUIRED, 'Cloudfile password enable admin rules for volumes creation')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;

        $this->io->section('Database');
        $this->schemaUpdate();
        
        $this->io->section('Cache clear');
        $this->cacheClear();
        
        $username = $input->getOption('username') ?? $this->params->get('username');
        $password = $input->getOption('password') ?? $this->params->get('password');
        if ($username && $password):
            $this->createUser($username, $password);
        endif;

        $diskname = $input->getOption('disk-name') ?? $this->params->get('disk_name');
        $diskpath = $input->getOption('disk-path') ?? $this->params->get('disk_path');
        if ($diskname && $diskpath):
            $this->addDisk($diskname, $diskpath);
        endif;

        $backup_host = $input->getOption('backup-host') ?? $this->params->get('backup_host');
        $backup_apikey = $input->getOption('backup-apikey') ?? $this->params->get('backup_apikey');
        $cloudfile_password = $input->getOption('cloudfile-password') ?? $this->params->get('cloudfile_password');
        if ($backup_host || $cloudfile_password):
            $this->setConfig($backup_host, $backup_apikey, $cloudfile_password);
        endif;

        foreach ($this->tables ?? [] as $section => $table):
            $table->setHeaderTitle($section);
            $table->render();
        endforeach;

        $this->io->success('Intallation success...');
        return 0;
    }

    private function schemaUpdate() {
        $command = $this->getApplication()->find('doctrine:database:create');
        $arguments = [];
        $input = new ArrayInput($arguments);
        $result = $command->run($input, $this->output);
        $command = $this->getApplication()->find('doctrine:schema:update');
        $arguments = ['--force'  => true];
        $input = new ArrayInput($arguments);
        $result = $command->run($input, $this->output);
    }

    private function cacheClear() {
        $command = $this->getApplication()->find('cache:clear');
        $arguments = [];
        $input = new ArrayInput($arguments);
        $result = $command->run($input, $this->output);
    }

    private function createUser(string $username, string $password) {
        $this->io->section($section = 'Add account admin');
        if (is_null($this->em->getRepository(User::class)->findOneBy(['username' => $username]))):
            $user = new User();
            $user->setUsername($username);
            $user->setPassword(
                $this->passwordEncoder->encodePassword(
                    $user,
                    $password
                )
            );
            $user->setRoles(['ROLE_ADMIN']);
            $this->em->persist($user);
            $this->em->flush();
            $this->tables[$section] = $this->table(['Username', 'Password', 'Roles'], [[$user->getUsername(), $password, \implode(',', $user->getRoles())]]);
            $this->io->success('Account has been regitered.');
        else:
            $this->io->error('Username already exist.');
        endif;    
    }

    private function addDisk(string $name, string $path) {
        $this->io->section($section = 'Add disk');
        if (is_null($this->em->getRepository(Disk::class)->findOneBy(['path' => $path]))):
            $disk = new Disk();
            $disk->setName($name);
            $disk->setPath($path);
            $this->em->persist($disk);
            $this->em->flush();
            $this->tables[$section] = $this->table(['Name', 'Path'], [[$disk->getName(), $disk->getPath()]]);
            $this->io->success('Disk has been regitered.');
        else:
            $this->io->error('This path is already used in another disk.');
        endif;
    }

    private function setConfig(?string $host, ?string $apikey, ?string $cloudfile_password) {
        $this->io->section($section = 'Configurations');
        $config = new Config();
        if ($host):
            $config = $this->addBackup($config, $host, $apikey);
        endif;
        if ($cloudfile_password):
            $config = $this->setCloudfilePassword($config, $cloudfile_password);
        endif;
    }

    private function setCloudfilePassword(Config $config, string $cloudfile_password) {
        $this->io->section($section = 'Cloudfile password');
        if (is_null($this->em->getRepository(Config::class)->findOneBy(['cloudfile_password' => $cloudfile_password]))):
            $config->setCloudfilePassword($cloudfile_password);
            $this->em->persist($config);
            $this->em->flush($config);
            $this->tables[$section] = $this->table(['Cloudfile Password'], [[$cloudfile_password]]);
            $this->io->success('This cloudfile password (enable admin rules for volumes creation.) has been registered.');
        else:
            $this->io->error('This password already used.');
        endif;
    }

    private function addBackup(Config $config, string $host, ?string $apikey) {
        $this->io->section($section = 'Backup');
        if (is_null($this->em->getRepository(Config::class)->findOneBy(['backup_host' => $host]))):
            $config->setBackupHost($host);
            $config->setBackupApikey($apikey);
            $this->em->persist($config);
            $this->em->flush($config);
            $this->tables[$section] = $this->table(['Host', 'Password'], [[$config->getBackupHost(), $config->getBackupApikey()]]);
            $this->io->success('Host server for backup has been registered.');
        else:
            $this->io->error('This host already used for backup.');
        endif;
        return $config;
    }

    private function table(array $header, array $rows) {
        $table = new Table($this->output);
        $table
            ->setHeaders($header)
            ->setRows($rows)
            ->render();
        return $table;
    }
}
