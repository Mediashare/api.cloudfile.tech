<?php

namespace App\Controller;

use App\Entity\File;
use App\Service\Indexer;
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
        $filesystem = new FileSystemApi();
        $files = $filesystem->getFiles($request, $this->getDoctrine()->getManager());

        $response = new Response();
        return $response->send([
            'status' => 'success',
            'files' => [
                'counter' => count($files),
                'results' => $files
            ],
        ]);
    }
}
