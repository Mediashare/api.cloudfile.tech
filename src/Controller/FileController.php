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
            return new JsonResponse($file->getInfo());
        endif;
        return new JsonResponse([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }

    /**
     * @Route("/show/{id}", name="show")
     */
    public function show(Request $request, string $id) {
        $file = $this->getFile($request, $id);
        if ($file):
            return new BinaryFileResponse($file->getPath());
        endif;
        return new JsonResponse([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
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
            return $response;
        endif;
        return new JsonResponse([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
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
            return new JsonResponse([
                'status' => 'success',
                'message' => '['.$id.'] File was removed.',
            ]);
        endif;
        return new JsonResponse([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
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
