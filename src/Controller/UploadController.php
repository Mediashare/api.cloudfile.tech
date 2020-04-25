<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Volume;
use App\Service\Response;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UploadController extends AbstractController
{
    public function __construct() {
        $this->response = new Response();
    }

    /**
     * @Route("/upload", name="upload")
     */
    public function upload(Request $request) {
        // Check ApiKey
        $authority = $this->response->checkAuthority($request, $em = $this->getDoctrine()->getManager());
        if ($authority):
            return $authority;
        endif;
        
        $apikey = $request->headers->get('apikey');
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        
        // Upload file(s)
        $files = $request->files;
        if (count($files) > 0):
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
                // ApiKey & Volume
                $file->setApiKey($apikey);
                $file->setVolume($volume);
                // Record
                $em->persist($file);
                $em->flush();
                
                $results[] = $file->getInfo();
                $size += $file->getSize();
            }
            // Response
            return $this->response->send([
                'status' => 'success',
                'message' => 'Your file(s) was uploaded.',
                'files' => [
                    'counter' => count($results),
                    'size' => $fileSystem->getSizeReadable($size),
                    'results' => $results
                ]
            ]);
        endif;
        return $this->response->send([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }
}
