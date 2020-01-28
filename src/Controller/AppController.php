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
        $files = $em->getRepository(File::class)->findAll();
        $results = [];
        $size = 0;
        foreach ($files as $file) {
            $results[] = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'path' => $file->getPath(),
                'mimeType' => $file->getMimeType(),
                'size' => $fileSystem->getSizeReadable($file->getSize()),
                'metadata' => $file->getMetadata(),
            ];
            $size += $file->getSize();
        }
        return $this->json([
            'status' => 'success',
            'files' => [
                'counter' => count($results),
                'size' => $fileSystem->getSizeReadable($size),
                'results' => $results
            ],
        ]);
    }
}
