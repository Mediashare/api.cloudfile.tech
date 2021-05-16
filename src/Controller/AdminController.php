<?php

namespace App\Controller;

use App\Entity\Disk;
use App\Entity\File;
use App\Entity\Volume;
use Mediashare\Kernel\Kernel;
use App\Service\FileSystemApi;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;

class AdminController extends EasyAdminController
{
    public function removeEntity($entity) {
        if (get_class($entity) === Volume::class):
            $this->removeVolume($entity);
            foreach ($entity->getFiles() as $file):    
                $this->em->remove($file);
            endforeach;
        elseif (get_class($entity) === File::class):
            $this->removeFile($entity);
        endif;
        
        $this->em->remove($entity);
        $this->em->flush();

        return $this->redirectToRoute('easyadmin', [
            'action' => 'list',
            'entity' => $entity
        ]);
    }

    public function UpdateEntity($entity) {
        if (get_class($entity) === Volume::class):
            $entity->setUpdateDate(new \DateTime());
        elseif (get_class($entity) === Disk::class):
            $kernel = new Kernel();
            $mkdir = $kernel->get('Mkdir');
            $mkdir->setPath($entity->getPath());
            $mkdir->run();
        endif;
        $this->em->persist($entity);
        $this->em->flush();
        return $this->redirectToRoute('easyadmin', [
            'action' => 'list',
            'entity' => $entity
        ]);

    }
    public function persistEntity($entity) {
        if (get_class($entity) === Disk::class):
            $kernel = new Kernel();
            $mkdir = $kernel->get('Mkdir');
            $mkdir->setPath($entity->getPath());
            $mkdir->run();
        endif;
        $this->em->persist($entity);
        $this->em->flush();
        return $this->redirectToRoute('easyadmin', [
            'action' => 'list',
            'entity' => $entity
        ]);

    }

    private function removeVolume(Volume $volume) {
        $fileSystem = new FileSystemApi();
        $disks = $this->em->getRepository(Disk::class)->findAll();
        foreach ($disks as $disk):
            $fileSystem->remove(rtrim($disk->getPath(), '/').'/'.$volume->getId());
        endforeach;
    }
    private function removeFile(File $file) {
        // Remove file stockage
        $fileSystem = new FileSystemApi(); 
        $fileSystem->remove(dirname($file->getPath()));
    }
}
