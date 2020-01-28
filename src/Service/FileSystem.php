<?php
namespace Mediashare\Service;

use App\Entity\File as FileEntity;
use Mediashare\Kernel\Kernel;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem as Fs;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

Class FileSystem
{
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
            $name = \uniqid() .'.'. $file->guessExtension()
        );
        
        $FileEntity = new FileEntity();
        $FileEntity->setName($file->getClientOriginalName());
        $FileEntity->setPath($destination . '/' . $name);

        return $FileEntity;
    }
    public function move(string $path, string $destination) {
        // Create destination if not exist
        $this->mkdir->setPath(dirname($destination));
        $this->mkdir->run();
        // Move
        $this->filesystem->rename($path, $destination);
    }
}
