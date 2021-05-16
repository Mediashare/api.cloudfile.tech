<?php

namespace App\Command\Robots;

use App\Entity\File;
use App\Service\FileSystemApi;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Encryption
 * Encrypt file.
 */
Class Encryption {
    public $em;
    public $io;
    public function run() {
        $filesystem = new FileSystemApi();
        $files = $this->em->getRepository(File::class)->findAll();
        $progressBar = new ProgressBar($this->io, count($files));
        $progressBar->start();
        foreach ($files as $file):
            $update = false;
            if ($file->getEncrypt() 
                && (!$file->getConvertToMp4() 
                    || ($file->getConvertToMp4() && $file->getMimeType() !== 'video/mp4'))):
                $update = $filesystem->encrypt($file);
            else: $update = $filesystem->decrypt($file); endif;
            if ($update): $this->em->persist($update); endif;
            $progressBar->advance();
        endforeach;
        $this->em->flush();
        $progressBar->finish();
    }
}
