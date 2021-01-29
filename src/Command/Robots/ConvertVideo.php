<?php

namespace App\Command\Robots;

use FFMpeg\FFMpeg;
use App\Entity\File;
use FFMpeg\Format\Video\X264;
use App\Service\FileSystemApi;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * ConvertVideo
 * Video format convertion to mp4.
 * @uses ffmpeg command line tool
 */
Class ConvertVideo {
    public $em;
    public $io;
    public function run() {
        $files = $this->em->getRepository(File::class)->findBy(['convertToMp4' => true]);
        $ffmpeg = FFMpeg::create();
        $filesystem = new FileSystemApi();
        $progressBar = new ProgressBar($this->io, count($files));
        $progressBar->start();
        foreach ($files as $file):
            if (strpos($file->getMimeType(), 'video/') !== false 
                && $file->getMimeType() !== "video/mp4"):
                $video = $ffmpeg->open($old_filepath = $file->getPath());
                $file->setFilename(rtrim($file->getFilename(), $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION)) . 'mp4');
                $file->setName(str_replace($extension, 'mp4', $file->getName()));
                $video->save(new X264('aac'), $file->getPath());
                $file->setMimeType(mime_content_type($file->getPath()));
                $filesystem->remove($old_filepath);
            endif;
            $progressBar->advance();
        endforeach;
        $progressBar->finish();
    }
}
