<?php

namespace App\Command\Robots;

use App\Entity\File;
use App\Service\FileSystemApi;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * FilesCleaner
 * - Remove file if without volume
 */
Class FilesCleaner {
    public $em;
    public $io;
    public function run() {
        $files = $this->getFiles();
        $progressBar = new ProgressBar($this->io, count($files));
        $progressBar->start();
        foreach ($files as $file):
            $progressBar->advance();
            if (!$file->getVolume()):
                $this->io->warning($file->getName() . ' is deleted because have not volume');
                $this->removeFile($file);
            endif;
        endforeach;
        $progressBar->finish();
    }

    public function getFiles() {
        $files = $this->em->getRepository(File::class)->findAll();
        return $files;
    }

    /**
     * Remove file from database & filesystem
     *
     * @param File $file
     * @return true
     */
    public function removeFile(File $file) {
        $filesystem = new FileSystemApi();
        $filesystem->remove($file->getStockage());
        $this->em->remove($file);
        $this->em->flush();
        return true;
    }
}
