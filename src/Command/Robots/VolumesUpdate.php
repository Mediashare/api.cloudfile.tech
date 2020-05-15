<?php

namespace App\Command\Robots;

use App\Entity\File;
use App\Entity\Volume;
use App\Service\FileSystemApi;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * VolumesUpdate
 * - Check Volume Status
 */
Class VolumesUpdate {
    public $em;
    public $io;
    public $output;
    public $pingIt;
    public function run() {
        $volumes = $this->getVolumes();
        $progressBarVolumes = new ProgressBar($this->output->section('Volumes'), count($volumes));
        $progressBarVolumes->start();
        foreach ($volumes as $volume):
            $progressBarFiles = new ProgressBar($this->output->section('Files'), count($volume->getFiles()));
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
     *  - Same private status
     *  - Same ApiKey
     * 
     * @param Volume $volume
     * @param File $file
     * @return true
     */
    private function status(Volume $volume, File $file) {
        $update = false;
        
        if ($file->getPrivate() !== $volume->getPrivate()):
            $file->setPrivate($volume->getPrivate());
            $update = true;
        endif;
        
        if ($file->getApiKey() !== $volume->getApikey()):
            $file->setApiKey($volume->getApikey());
            $update = true;
        endif;

        if ($update):
            $this->em->persist($file);
            $this->em->flush();
        endif;
        return true;
    }
}
