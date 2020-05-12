<?php

namespace App\Command;

use App\Entity\File;
use App\Entity\Proxy;
use App\Entity\Volume;
use Mediashare\ModulesProvider\Config;
use Mediashare\ModulesProvider\Modules;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RobotsCommand extends Command
{
    protected static $defaultName = 'robots';
    protected function configure() {
        $this->setDescription('Start all robots.');
    }

    private $container;
    public function __construct(ContainerInterface $container) {
        parent::__construct();
        $this->container = $container;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $this->io = new SymfonyStyle($input, $output);
        $this->input = $input;
        $this->output = $output;
        $this->robots();
        $this->io->success('All robots have finish.');

        return 0;
    }

    protected function robots() {
        $robots = $this->getRobots();
        foreach ($robots->getModules() as $module):
            $robot = $robots->get($module->name);
            $robot->em =  $this->container->get('doctrine')->getManager();
            $robot->io = $this->io;
            $robot->output = $this->output;
            $this->io->section('[Start] '.$module->name);
            $result = $robot->run();
            if ($result):
                $this->io->success($result);
            endif;
        endforeach;
    }

    protected function getRobots(): Modules {
        $config = new Config();
        $config->setModulesDir(__DIR__.'/Robots/');
        $config->setNamespace("App\\Command\\Robots\\");

        // Get all modules instancied.
        $modules = new Modules($config);
        return $modules;
    }
}
