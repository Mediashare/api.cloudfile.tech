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
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        // Get Volume
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        
        $files = $request->files;
        if (count($files) > 0):
            // Check Espace Disk
            $free_disk = $this->checkDisk($volume, $files); 
            if (!$free_disk):
                return $this->response->send([
                    'status' => 'error',
                    'message' => 'There is a missing of space on your volume disk.'
                ]);
            endif;
            // Upload file(s)
            $stockage = $this->getParameter('stockage');
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
                $volume->setUpdateDate(new \DateTime());
                // Record
                $em->persist($file, $volume);
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

    /**
     * If freespace return true, else false.
     *
     * @param Volume $volume
     * @param array $files
     * @return bool
     */
    private function checkDisk(Volume $volume, $files): bool {
        $fileSystem = new FileSystemApi();
        $volume_size = $fileSystem->human2byte($volume->getSize().'Gb');
        $files_size = 0;
        foreach ($volume->getFiles() as $file) {
            $files_size += $file->getSize();
        }
        foreach ($files as $file) {
            $files_size += $file->getSize();
        }
        if ($files_size > $volume_size):
            return false;
        else:
            return true;
        endif;
    }
}
