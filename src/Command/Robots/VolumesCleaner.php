<?php

namespace App\Command\Robots;

use App\Entity\File;
use App\Entity\Volume;
use App\Service\FileSystemApi;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * VolumesCleaner
 * - Check Volume Status
 */
Class VolumesCleaner {
    public $em;
    public $io;
    public function run() {
        $volumes = $this->getVolumes();
        $progressBar = new ProgressBar($this->io, count($volumes));
        $progressBar->start();
        foreach ($volumes as $volume):
            $progressBar->advance();
            foreach ($volume->getFiles() as $file):
                $this->status($volume, $file);
            endforeach;
        endforeach;
        $progressBar->finish();
    }

    public function getVolumes() {
        $volumes = $this->em->getRepository(Volume::class)->findAll();
        return $volumes;
    }

    /**
     * Verify if volume & file have same status (public/private)
     *
     * @param Volume $volume
     * @param File $file
     * @return true
     */
    private function status(Volume $volume, File $file) {
        if ($file->getPrivate() !== $volume->getPrivate()):
            $file->setPrivate($volume->getPrivate());
            $this->em->persist($file);
            $this->em->flush();
        endif;
        return true;
    }
}
