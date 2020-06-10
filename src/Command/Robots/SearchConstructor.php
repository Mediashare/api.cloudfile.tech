<?php

namespace App\Command\Robots;

use App\Entity\File;
use App\Entity\Search;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * SearchConstructor
 */
Class SearchConstructor {
    public $em;
    public $io;
    public function run() {
        $files = $this->getFiles();
        $progressBar = new ProgressBar($this->io, count($files));
        $progressBar->start();
        foreach ($files as $file):
            if (count($file->getSearches()) === 0):
                $file = $this->processFile($file);
                $this->em->persist($file);
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

    private function processFile(File $file): File {
        foreach ($file->getInfo() as $field => $value):
            $file = $this->generateSearch($file, $field, $value);
        endforeach;
        return $file;
    }

    private function generateSearch(File $file, string $field, $value): File {
        $search = new Search();
        if ($field === 'createDate'):
            $search->setField('date');
            $search->setValue($value['date']);
            $search->setVolume($file->getVolume());
            $file->addSearch($search);
        elseif (is_array($value)):
            foreach ($value as $array_field => $field_value):
                $file = $this->generateSearch($file, $array_field, $field_value);
            endforeach;
        else:
            $search->setField($field);
            if (is_bool($value)):
                if ($value):
                    $value = "true";
                else: $value = "false"; endif;
            endif;
            $search->setValue($value);
            $search->setVolume($file->getVolume());
            $file->addSearch($search);
        endif;
        return $file;
    }
}
