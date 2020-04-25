<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Volume;
use App\Service\Response;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FileController extends AbstractController
{
    public function __construct() {
        $this->response = new Response();
    }

    /**
     * @Route("/list/{page}", name="list")
     */
    public function list(Request $request, ?int $page = 1) {
        // Check Authority
        $authority = $this->response->checkAuthority($request, $em = $this->getDoctrine()->getManager());
        if ($authority):
            return $authority;
        endif;
           
        // Get Files
        $fileSystem = new FileSystemApi();
        $result = $fileSystem->getFiles($request, $em, $page);

        $files = $result['files'];
        $results = [];
        $size = 0;
        foreach ($files as $file) {
            $results[] = $file->getInfo();;
            $size += $file->getSize();
        }

        return $this->response->send([
            'status' => 'success',
            'files' => [
                'page' => $page,
                'counter' => $result['counter'],
                'size' => $fileSystem->getSizeReadable($size),
                'results' => $results
            ],
        ]);
    }

    /**
     * @Route("/info/{id}", name="info")
     */
    public function info(Request $request, string $id) {
        // Check Authority
        $authority = $this->response->checkAuthority($request, $em = $this->getDoctrine()->getManager());
        if ($authority):
            return $authority;
        endif;

        $fileSystem = new FileSystemApi();
        $file = $fileSystem->getFile($request, $id, $em);
        if ($file):
            return $this->response->send($file->getInfo());
        endif;
        return $this->response->send([
            'status' => 'error',
            'message' => 'File not found.',
        ], 404);
    }

    /**
     * @Route("/show/{id}", name="show")
     */
    public function show(Request $request, string $id) {
        // Check Authority
        $authority = $this->response->checkAuthority($request, $em = $this->getDoctrine()->getManager());
        if ($authority):
            return $authority;
        endif;
        
        $fileSystem = new FileSystemApi();
        $file = $fileSystem->getFile($request, $id, $this->getDoctrine()->getManager());
        if ($file):
            $response = new BinaryFileResponse($file->getPath(), 200);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Headers', '*');
            return $response;
        endif;
        return $this->response->send([
            'status' => 'error',
            'message' => 'File not found.',
        ], 404);
    }

    /**
     * @Route("/download/{id}", name="download")
     */
    public function download(Request $request, string $id) {
        $fileSystem = new FileSystemApi();
        $file = $fileSystem->getFile($request, $id, $this->getDoctrine()->getManager());
        if ($file):
            $response = new BinaryFileResponse($file->getPath());
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $file->getName()
            );
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Headers', '*');
            return $response;
        endif;
        return $this->response->send([
            'status' => 'error',
            'message' => 'File not found.',
        ], 404);
    }

    /**
     * @Route("/remove/{id}", name="remove")
     */
    public function remove(Request $request, string $id) {
        $fileSystem = new FileSystemApi();
        $file = $fileSystem->getFile($request, $id, $this->getDoctrine()->getManager());
        if ($file):
            // Remove to database
            $em = $this->getDoctrine()->getManager();
            $em->remove($file);
            $em->flush();
            // Remove file stockage
            $fileSystem->remove($file->getStockage());
            // Response
            return $this->response->send([
                'status' => 'success',
                'message' => '['.$id.'] File was removed.',
            ]);
        endif;
        return $this->response->send([
            'status' => 'error',
            'message' => 'File not found.',
        ], 404);
    }
}
