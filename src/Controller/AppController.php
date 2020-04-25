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
        $response = new Response();
        // Check Authority
        $authority = $response->checkAuthority($request, $em = $this->getDoctrine()->getManager());
        if ($authority):
            return $authority;
        endif;
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $request->headers->get('apikey'), 'online' => true]);

        $fileSystem = new FileSystemApi();
        $files = $fileSystem->getFiles($request, $em);
        $size = 0;
        foreach ($files as $file) {
            $size += $file->getSize();
        }

        $stockage = $this->getParameter('kernel_dir').$this->getParameter('stockage');
        $total_space = $this->human2byte($volume->getSize().'G');
        $free_space = $total_space - $size;
        return $response->send([
            'status' => 'success',
            'stats' => [
                'files' => [
                    'counter' => count($files),
                ],
                'stockage' => [
                    'used' => $fileSystem->getSizeReadable($size),
                    'used_pct' => number_format($size * 100 / $free_space, 1),
                    'free' => $fileSystem->getSizeReadable($free_space),
                    'total' => $fileSystem->getSizeReadable($total_space)
                ]
            ],
        ]);
    }

    private function human2byte($value) {
        return preg_replace_callback('/^\s*(\d+)\s*(?:([kmgt]?)b?)?\s*$/i', function ($m) {
        switch (strtolower($m[2])) {
            case 't': $m[1] *= 1024;
            case 'g': $m[1] *= 1024;
            case 'm': $m[1] *= 1024;
            case 'k': $m[1] *= 1024;
        }
        return $m[1];
        }, $value);
    }
}
