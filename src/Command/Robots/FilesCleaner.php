<?php

namespace App\Command\Robots;

use App\Entity\File;
use App\Service\FileSystemApi;
use Symfony\Component\Console\Helper\ProgressBar;

Class FilesCleaner {
    public $em;
    public $io;
    public function run() {
        $files = $this->getFiles();
        $progressBar = new ProgressBar($this->io, count($files));
        $progressBar->start();
        foreach ($files as $file):
            $progressBar->advance();
            $volume = $this->em->getRepository(Volume::class)->find($volume);
            if (!$volume):
                $this->removeFile($file);
            endif;
        endforeach;
        $progressBar->finish();
    }

    public function getFiles() {
        $files = $this->em->getRepository(File::class)->findAll();
        return $files;
    }

    public function removeFile(File $file) {
        $filesystem = new FileSystemApi();
        $filesystem->remove($file->getStockage());
        $this->em->remove($file);
        $this->em->flush();
    }
}
