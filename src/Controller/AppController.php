<?php

namespace App\Controller;

use App\Entity\File;
use Mediashare\Service\FileSystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AppController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index() {
        $em = $this->getDoctrine()->getManager();
        $fileRepo = $em->getRepository(File::class)->findAll();
        $files = [];
        foreach ($fileRepo as $file) {
            $files[] = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'path' => $file->getPath()
            ];
        }
        return $this->json([
            'status' => 'success',
            'files' => $files,
        ]);
    }

    /**
     * @Route("/upload", name="upload")
     */
    public function upload(Request $request) {
        $files = $request->files;
        if (count($files) > 0):
            $em = $this->getDoctrine()->getManager();
            $stockage = $this->getParameter('kernel.project_dir').$this->getParameter('stockage');
            foreach ($files as $file) {
                $fileSystem = new FileSystem();
                $file = $fileSystem->upload($file, $stockage);
                $em->persist($file);
                $em->flush();
            }
            return $this->json([
                'status' => 'success',
                'message' => 'Your file(s) was uploaded.',
            ]);
        endif;
        return $this->json([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }
}
