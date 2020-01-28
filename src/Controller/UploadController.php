<?php

namespace App\Controller;

use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UploadController extends AbstractController
{
    /**
     * @Route("/upload", name="upload")
     */
    public function upload(Request $request) {
        $files = $request->files;
        if (count($files) > 0):
            $em = $this->getDoctrine()->getManager();
            $stockage = $this->getParameter('kernel.project_dir').$this->getParameter('stockage');
            $fileSystem = new FileSystemApi();
            $size = 0;
            foreach ($files as $index => $file) {
                $file = $fileSystem->upload($file, $stockage);
                $em->persist($file);
                $em->flush();
                
                $results[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'path' => $file->getPath(),
                    'mimeType' => $file->getMimeType(),
                    'size' => $fileSystem->getSizeReadable($file->getSize())
                ];
                $size += $file->getSize();
            }
            // Response
            return $this->json([
                'status' => 'success',
                'message' => 'Your file(s) was uploaded.',
                'files' => [
                    'counter' => count($results),
                    'size' => $fileSystem->getSizeReadable($size),
                    'results' => $results
                ]
            ]);
        endif;
        return $this->json([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }
}
