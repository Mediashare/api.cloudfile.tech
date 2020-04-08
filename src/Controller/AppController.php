<?php

namespace App\Controller;

use App\Entity\File;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AppController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(Request $request) {
        $fileSystem = new FileSystemApi();
        $em = $this->getDoctrine()->getManager();
        
        $apiKey = $request->headers->get('apikey');
        if ($apiKey):
            $files = $em->getRepository(File::class)->findBy(['apiKey' => $apiKey], ['createDate' => 'DESC']);
        else:
            $files = $em->getRepository(File::class)->findBy(['private' => false], ['createDate' => 'DESC']);
        endif;

        $results = [];
        $size = 0;
        foreach ($files as $file) {
            $results[] = $file->getInfo();;
            $size += $file->getSize();
        }
        $response = new JsonResponse([
            'status' => 'success',
            'files' => [
                'counter' => count($results),
                'size' => $fileSystem->getSizeReadable($size),
                'results' => $results
            ],
        ], 200);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }

    /**
     * @Route("/stats", name="stats")
     */
    public function stats() {
        $fileSystem = new FileSystemApi();
        $em = $this->getDoctrine()->getManager();
        $files = $em->getRepository(File::class)->findAll();
        
        $results = [];
        $size = 0;
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        return new JsonResponse([
            'status' => 'success',
            'stats' => [
                'counter' => count($files),
                'size' => $fileSystem->getSizeReadable($size),
            ],
        ]);
    }
}
