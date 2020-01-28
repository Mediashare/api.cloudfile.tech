<?php
namespace App\Service;

use App\Entity\File as FileEntity;
use Mediashare\Kernel\Kernel;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem as Fs;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

Class FileSystemApi {
    public function __construct() {
        $kernel = new Kernel();
        $kernel->run();
        $this->mkdir = $kernel->get('Mkdir');
        $this->filesystem = new Fs();
    }
    public function upload(File $file, string $stockage): FileEntity {
        // Create destination if not exist
        $destination = \rtrim($stockage, '/');
        $this->mkdir->setPath($destination);
        $this->mkdir->run();

        $file->move(
            $destination, 
            $name = \uniqid() .'.'. pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)
        );
        $FileEntity = new FileEntity();
        $FileEntity->setName($file->getClientOriginalName());
        $FileEntity->setPath($destination . '/' . $name);
        $FileEntity->setSize(\filesize($FileEntity->getPath()));
        $FileEntity->setMimeType(mime_content_type($FileEntity->getPath()));
        
        return $FileEntity;
    }
    public function move(string $path, string $destination) {
        // Create destination if not exist
        $this->mkdir->setPath(dirname($destination));
        $this->mkdir->run();
        // Move
        $this->filesystem->rename($path, $destination);
    }
    public function remove(string $path) {
        $this->filesystem->remove($path);
    }
    public function getSizeReadable(int $bytes, int $decimals = 2){
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
}
