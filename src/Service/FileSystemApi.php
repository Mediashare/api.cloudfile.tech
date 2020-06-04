<?php
namespace App\Service;

use App\Entity\Disk;
use App\Entity\Volume;
use Mediashare\Kernel\Kernel;
use Doctrine\ORM\EntityManager;
use App\Entity\File as FileEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem as Fs;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

Class FileSystemApi {
    public function __construct() {
        $kernel = new Kernel();
        $kernel->run();
        $this->mkdir = $kernel->get('Mkdir');
        $this->filesystem = new Fs();
    }
    public function upload(string $id, File $file, Disk $disk, Volume $volume): FileEntity {
        // Create destination if not exist
        $destination = rtrim($disk->getPath(), '/').'/'.$volume->getId().'/'.$id;
        $this->mkdir($destination);

        $filename = \uniqid() .'.'. pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        try {
            $file->move($destination, $filename);
        } catch (FileException $e) {
            dd($e, $file);
        }

        // \copy($file->getPathName(), $destination . '/' . $filename);
        // \unlink($file->getPathName());

        $FileEntity = new FileEntity();
        $FileEntity->setId($id);
        $FileEntity->setName($file->getClientOriginalName());
        $FileEntity->setDisk($disk);
        $FileEntity->setVolume($volume);
        $FileEntity->setFilename($filename);
        $FileEntity->setSize(\filesize($FileEntity->getPath()));
        $FileEntity->setMimeType(mime_content_type($FileEntity->getPath()));
        $FileEntity->setChecksum();

        return $FileEntity;
    }
    public function mkdir(string $path) {
        if (!\file_exists($path)):
            $this->mkdir->setPath($path);
            $this->mkdir->run();
            \chmod($path, 0777);
        endif;
    }
    public function move(string $path, string $destination) {
        // Create destination if not exist
        $this->mkdir(dirname($destination));
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
