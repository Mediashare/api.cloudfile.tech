<?php
namespace App\Service;

use App\Entity\File;
use Mediashare\Kernel\Kernel;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;

Class Indexer {
    public function __construct() {
        $kernel = new Kernel();
        $kernel->run();
        $this->mkdir = $kernel->get('Mkdir');
        $this->filesystem = new Filesystem();
        $this->index_dir = rtrim(__DIR__, '/').'/../../var/index';
    }
    
    public function addFile(File $file) {
        $index_content = \json_decode($this->getIndex($file), true);
        array_push($index_content, $file->getInfo());
        $this->filesystem->dumpFile($this->getPath($file), \json_encode($index_content));
    }
    
    public function removeFile(File $file) {
        $files = \json_decode($this->getIndex($file), true);
        foreach ($files as $key => $index_file) {
            if ($index_file['id'] === $file->getId()):
                unset($files[$key]);
            endif;
        }
        $files = array_values($files);
        $this->filesystem->dumpFile($this->getPath($file), \json_encode($files));
    }

    private function getPath(File $file): string {
        $this->mkdir->setPath($this->index_dir);
        $this->mkdir->run();
        if ($file->getPrivate()): $index = $this->$index_dir.'/'.$file->getApiKey().'.json';
        else: $index = $this->index_dir.'/public.json'; endif;
        
        // Generate if not exist
        if (!file_exists($index)): 
            $this->filesystem->touch($index); 
            $this->filesystem->dumpFile($index, \json_encode([]));
        endif;

        return $index;
    }

    /**
     * Get index file
     *
     * @param File $file
     */
    public function getIndex(File $file) {
        $index = $this->getPath($file);
        return \file_get_contents($index);
    }
}