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
        $fileSystem = new FileSystemApi();
        $em = $this->getDoctrine()->getManager();

        $volumes_size = 0;
        foreach ($volumes = $em->getRepository(Volume::class)->findAll() as $volume):
            $volumes_size += $volume->getSize();
        endforeach;

        $free_space = \disk_free_space($this->getParameter('stockage'));
        $total_space = \disk_total_space($this->getParameter('stockage'));
        $used_space = $total_space - $free_space;

        return $response->send([
            'status' => 'success',
            'volumes' => [
                'counter' =>  count($volumes),
                'size' => $volumes_size . 'Gb'
            ],
            'files' => [
                'counter' => count($em->getRepository(File::class)->findAll())
            ],
            'stockage' => [
                'used_pct' => number_format($used_space * 100 / $total_space, 1),
                'used' => $fileSystem->getSizeReadable($used_space),
                'total' => $fileSystem->getSizeReadable($total_space),
                'free' => $fileSystem->getSizeReadable($free_space),
            ],
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
        return $response->send([
            'status' => 'success',
            'volume' => $volume->getInfo(),
        ]);
    }
}
