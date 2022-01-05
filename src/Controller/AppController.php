<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Volume;
use App\Entity\Disk;
use App\Service\Response;
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
        $em = $this->getDoctrine()->getManager();

        $volumes_size = 0;
        foreach ($volumes = $em->getRepository(Volume::class)->findAll() as $volume):
            if ($volume->getSize() > 0):
                $volumes_size += $volume->getSize();
            endif;
        endforeach;

        $disks = $em->getRepository(Disk::class)->findAll();
        foreach ($disks as $key => $disk):
            $disks[$key] = $disk->getInfo();
        endforeach;

        return $response->json([
            'status' => 'success',
            'volumes' => [
                'counter' =>  count($volumes),
                'size' => $volumes_size . 'Gb'
            ],
            'files' => [
                'counter' => count($em->getRepository(File::class)->findAll())
            ],
            'disks' => $disks
        ]);
    }
}
