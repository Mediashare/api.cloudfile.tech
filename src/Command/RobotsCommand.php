<?php

namespace App\Command;

use App\Entity\File;
use App\Entity\Volume;
use Mediashare\PingIt\PingIt;
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
        $this->pingIt = new PingIt($this->container->getParameter('pingit_robots'));
        $this->pingIt->send('[API] The robots start the job!', null, 'feather icon-radio', 'primary');
        $this->io = new SymfonyStyle($input, $output);
        $this->input = $input;
        $this->output = $output;
        $this->robots();
        $this->io->success('All robots have finish their jobs.');
        $this->pingIt->send('[API] The robots finish the job!', null, 'feather icon-radio', 'success');
        return 0;
    }

    protected function robots() {
        $robots = $this->getRobots();
        foreach ($robots->getModules() as $module):
            if ($module->name !== 'BackUp'):
                $robot = $robots->get($module->name);
                $robot->container = $this->container;
                $robot->em = $this->container->get('doctrine')->getManager();
                $robot->io = $this->io;
                $robot->output = $this->output;
                $robot->pingIt = $this->pingIt;
                $this->io->section('[Start] '.$module->name);
                $result = $robot->run();
                if ($result):
                    $this->io->success($result);
                endif;
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
