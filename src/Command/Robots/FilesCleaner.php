<?php

namespace App\Command\Robots;

use App\Entity\File;
use App\Service\FileSystemApi;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * FilesCleaner
 * - Remove file if without volume
 * - Remove file if not exist
 */
Class FilesCleaner {
    public $em;
    public $io;
    public function run() {
        $files = $this->getFiles();
        $progressBar = new ProgressBar($this->io, count($files));
        $progressBar->start();
        foreach ($files as $file):
            if (!$file->getVolume()):
                $this->removeFile($file);
            endif;
            if (file_exists($file->getDisk()->getPath()) && !\file_exists($file->getPath())):
                $this->removeFile($file);
            endif;
            $progressBar->advance();
        endforeach;
        $this->em->flush();
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
        $filesystem->remove(\dirname($file->getPath()));
        $this->em->remove($file);
        return true;
    }
}
