<?php

namespace App\Controller;

use App\Entity\File;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FileController extends AbstractController
{
    /**
     * @Route("/info/{id}", name="info")
     */
    public function info(Request $request, string $id) {
        $file = $this->getFile($request, $id);
        if ($file):
            $response = new JsonResponse($file->getInfo());
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Headers', '*');
            return $response;
        endif;
        $response = new JsonResponse([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }

    /**
     * @Route("/show/{id}", name="show")
     */
    public function show(Request $request, string $id) {
        $file = $this->getFile($request, $id);
        if ($file):
            $response = new BinaryFileResponse($file->getPath());
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Headers', '*');
            return $response;
        endif;
        $response = new JsonResponse([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }

    /**
     * @Route("/download/{id}", name="download")
     */
    public function download(Request $request, string $id) {
        $file = $this->getFile($request, $id);
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
        $response = new JsonResponse([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }

    /**
     * @Route("/remove/{id}", name="remove")
     */
    public function remove(Request $request, string $id) {
        $file = $this->getFile($request, $id);
        if ($file):
            $fileSystem = new FileSystemApi();
            // Remove to database
            $em = $this->getDoctrine()->getManager();
            $em->remove($file);
            $em->flush();
            // Remove file stockage
            $fileSystem->remove($file->getStockage());
            // Response
            $response = new JsonResponse([
                'status' => 'success',
                'message' => '['.$id.'] File was removed.',
            ]);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Headers', '*');
            return $response;
        endif;
        $response = new JsonResponse([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }

    /**
     * Get file from public or private cloud.
     *
     * @param Request $request
     * @param string $id
     * @return File|null
     */
    private function getFile(Request $request, string $id): ?File {
        $em = $this->getDoctrine()->getManager();
        $apiKey = $request->headers->get('apikey');
        if ($apiKey):
            return $em->getRepository(File::class)->findOneBy(
                ['apiKey' => $apiKey, 'id' => $id], 
                ['createDate' => 'DESC']
            );
        else:
            return $em->getRepository(File::class)->findOneBy(
                ['private' => false, 'id' => $id], 
                ['createDate' => 'DESC']
            );
        endif;
        return null;
    }
}
