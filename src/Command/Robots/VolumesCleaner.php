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
        $progressBarVolumes = new ProgressBar($this->io->section('Volumes'), count($volumes));
        $progressBarVolumes->start();
        foreach ($volumes as $volume):
            $progressBarFiles = new ProgressBar($this->io->section('Files'), count($volume->getFiles()));
            foreach ($volume->getFiles() as $file):
                $this->status($volume, $file);
                $progressBarFiles->advance();
            endforeach;
            $progressBarFiles->finish();
            $progressBarVolumes->advance();
        endforeach;
        $progressBarVolumes->finish();
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
