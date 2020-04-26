<?php
namespace App\Service;

use Mediashare\Kernel\Kernel;
use Doctrine\ORM\EntityManager;
use App\Entity\File as FileEntity;
use Symfony\Component\HttpFoundation\Request;
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
    public function upload(string $id, File $file, string $stockage): FileEntity {
        // Create destination if not exist
        $destination = \rtrim($stockage, '/') . '/' . $id;
        $this->mkdir->setPath($destination);
        $this->mkdir->run();

        $file->move(
            $destination, 
            $name = \uniqid() .'.'. pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)
        );
        $FileEntity = new FileEntity();
        $FileEntity->setId($id);
        $FileEntity->setName($file->getClientOriginalName());
        $FileEntity->setStockage($destination);
        $FileEntity->setPath($destination . '/' . $name);
        $FileEntity->setSize(\filesize($FileEntity->getPath()));
        $FileEntity->setMimeType(mime_content_type($FileEntity->getPath()));
        $FileEntity->setChecksum();

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
    // Bytes => Mb | Bytes => Gb ...
    public function getSizeReadable($bytes, int $decimals = 2){
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
    // Mb => bytes | Gb => bytes ...
    public function human2byte($value) {
        return preg_replace_callback('/^\s*(\d+)\s*(?:([kmgt]?)b?)?\s*$/i', function ($m) {
        switch (strtolower($m[2])) {
            case 't': $m[1] *= 1024;
            case 'g': $m[1] *= 1024;
            case 'm': $m[1] *= 1024;
            case 'k': $m[1] *= 1024;
        }
        return $m[1];
        }, $value);
    }
}
