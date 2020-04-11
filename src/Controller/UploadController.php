<?php

namespace App\Controller;

use App\Entity\File;
use App\Service\Response;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UploadController extends AbstractController
{
    /**
     * @Route("/upload", name="upload")
     */
    public function upload(Request $request) {
        $files = $request->files;
        if (count($files) > 0):
            $em = $this->getDoctrine()->getManager();
            $stockage = $this->getParameter('kernel.project_dir').$this->getParameter('stockage');
            $fileSystem = new FileSystemApi();
            $results = [];
            $size = 0;
            foreach ($files as $file) {
                // Generate ID
                $id = \uniqid(); // Generate uniqid()
                while ($em->getRepository(File::class)->find($id)) { // Check $id if already used
                    $id = \uniqid();
                }
                // Upload file
                $file = $fileSystem->upload($id, $file, $stockage);
                // Set metadata
                $file->setMetadata($_REQUEST);
                // ApiKey
                $file->setApiKey($request->headers->get('apikey'));
                // Record
                $em->persist($file);
                $em->flush();
                
                $results[] = $file->getInfo();
                $size += $file->getSize();
            }
            // Response
            $response = new Response();
            return $response->send([
                'status' => 'success',
                'message' => 'Your file(s) was uploaded.',
                'files' => [
                    'counter' => count($results),
                    'size' => $fileSystem->getSizeReadable($size),
                    'results' => $results
                ]
            ]);
        endif;
        $response =  new Response();
        return $response->send([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }
}
