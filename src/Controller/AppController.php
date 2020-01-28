<?php

namespace App\Controller;

use App\Entity\File;
use App\Service\FileSystemApi;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AppController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index() {
        $em = $this->getDoctrine()->getManager();
        $fileSystem = new FileSystemApi();
        $fileRepo = $em->getRepository(File::class)->findAll();
        $files = [];
        $size = 0;
        foreach ($fileRepo as $file) {
            $files[] = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'path' => $file->getPath(),
                'size' => $fileSystem->getSizeReadable($file->getSize())
            ];
            $size += $file->getSize();
        }
        return $this->json([
            'status' => 'success',
            'files' => [
                'counter' => count($files),
                'size' => $fileSystem->getSizeReadable($size),
                'results' => $files
            ],
        ]);
    }
}
