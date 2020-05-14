<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Volume;
use App\Service\FileSystemApi;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;

class AdminController extends EasyAdminController
{
    public function removeEntity($entity) {
        $em = $this->getDoctrine()->getManager();
        
        if (get_class($entity) === Volume::class):
            $this->removeVolume($entity);
            foreach ($entity->getFiles() as $file):    
                $em->remove($file);
                $em->flush();
            endforeach;
        elseif (get_class($entity) === File::class):
            $this->removeFile($entity);
        endif;
        
        $em->remove($entity);
        $em->flush();

        return $this->redirectToRoute('easyadmin', [
            'action' => 'list',
            'entity' => $entity
        ]);
    }

    public function UpdateEntity($entity) {
        $em = $this->getDoctrine()->getManager();
        if (get_class($entity) === Volume::class):
            $entity->setUpdateDate(new \DateTime());
        endif;
        $em->persist($entity);
        $em->flush();
        return $this->redirectToRoute('easyadmin', [
            'action' => 'list',
            'entity' => $entity
        ]);

    }

    private function removeVolume(Volume $volume) {
        $fileSystem = new FileSystemApi();
        $fileSystem->remove($volume->getStockage());
    }
    private function removeFile(File $file) {
        // Remove file stockage
        $fileSystem = new FileSystemApi(); 
        $fileSystem->remove($file->getStockage());
    }
}
