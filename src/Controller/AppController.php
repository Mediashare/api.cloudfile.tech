<?php

namespace App\Controller;

use App\Entity\File;
use App\Service\Response;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AppController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(Request $request) {
        $fileSystem = new FileSystemApi();
        $files = $fileSystem->getFiles($request, $this->getDoctrine()->getManager());
        $results = [];
        $size = 0;
        foreach ($files as $file) {
            $results[] = $file->getInfo();;
            $size += $file->getSize();
        }
        
        $response = new Response();
        return $response->send([
            'status' => 'success',
            'files' => [
                'counter' => count($results),
                'size' => $fileSystem->getSizeReadable($size),
                'results' => $results
            ],
        ]);
    }

    /**
     * @Route("/stats", name="stats")
     */
    public function stats(Request $request) {
        $fileSystem = new FileSystemApi();
        $files = $fileSystem->getFiles($request, $this->getDoctrine()->getManager());
        $size = 0;
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        $stockage = $this->getParameter('kernel_dir').$this->getParameter('stockage');
        $free_space = disk_free_space($stockage);
        $total_space = disk_total_space($stockage);
        $response = new Response();
        return $response->send([
            'status' => 'success',
            'stats' => [
                'files' => [
                    'counter' => count($files),
                ],
                'stockage' => [
                    'used' => $fileSystem->getSizeReadable((int) $size),
                    'used_pct' => number_format($size * 100 / $free_space),
                    'free' => $fileSystem->getSizeReadable((int) $free_space),
                    'total' => $fileSystem->getSizeReadable((int) $total_space)
                ]
            ],
        ]);
    }
}
