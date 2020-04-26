<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Volume;
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
        $response = new Response();
        return $response->send([
            'status' => 'success'
        ]);
    }

    /**
     * @Route("/stats", name="stats")
     */
    public function stats(Request $request) {
        // Check Authority
        $response = new Response();
        $apikey = $request->headers->get('apikey');
        $authority = $response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;

        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        $files = $em->getRepository(File::class)->findBy(['apiKey' => $apikey], ['createDate' => 'DESC']);
        $size = 0;
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        
        $fileSystem = new FileSystemApi();
        $total_space = $fileSystem->human2byte($volume->getSize().'G');
        $free_space = $total_space - $size;
        return $response->send([
            'status' => 'success',
            'volume' => $volume->getInfo(),
            'stats' => [
                'files' => [
                    'counter' => count($files),
                ],
                'stockage' => [
                    'used' => $fileSystem->getSizeReadable($size),
                    'used_pct' => number_format($size * 100 / $total_space, 1),
                    'free' => $fileSystem->getSizeReadable($free_space),
                    'total' => $fileSystem->getSizeReadable($total_space)
                ]
            ],
        ]);
    }
}
