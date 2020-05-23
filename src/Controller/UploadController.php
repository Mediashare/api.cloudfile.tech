<?php

namespace App\Controller;

use App\Entity\Disk;
use App\Entity\File;
use App\Entity\Volume;
use App\Service\Response;
use Mediashare\PingIt\PingIt;
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
            $free_disk = $this->checkVokumeSize($volume, $files); 
            if (!$free_disk):
                return $this->response->send([
                    'status' => 'error',
                    'message' => 'There is a missing of space on your volume disk.'
                ]);
            endif;

            // Upload file(s)
            $pingIt = new PingIt($this->getParameter('pingit_uploads'));
            $fileSystem = new FileSystemApi();
            $results = [];
            $size = 0;
            foreach ($files as $file) {
                // Generate ID
                $id = \uniqid(); // Generate uniqid()
                while ($em->getRepository(File::class)->find($id)) { // Check $id if already used
                    $id = \uniqid();
                }

                // Select Disk Storage
                $disk_usage = 99;
                foreach ($em->getRepository(Disk::class)->findAll() as $disk):
                    if (\file_exists($disk->getPath())):
                        $info = $disk->getInfo();
                        if ((float) $info['size']['used_pct'] < $disk_usage):
                            $disk_usage = (float) $info['size']['used_pct'];
                            $disk_selected = $disk;
                        endif;
                    endif;
                endforeach;

                if (empty($disk_selected)):
                    return $this->json([
                        'status' => 'error',
                        'message' => 'No disk has been found!'
                    ]);
                endif;

                // Upload file
                $file = $fileSystem->upload($id, $file, $disk_selected, $volume);

                // Set metadata
                $file->setMetadata($_REQUEST);
                $file->setPrivate($volume->getPrivate());
                // ApiKey & Volume
                $file->setApiKey($apikey);
                $volume->setUpdateDate(new \DateTime());

                // Record
                $em->persist($file, $volume);
                $em->flush();
                
                $results[] = $file->getInfo();
                $size += $file->getSize();
                
                // PingIt
                $pingIt->send('[API] File uploaded '.$file->getName(), '['.$volume->getId().'] '.$volume->getName().' receveid '.$file->getName().' file - '.$fileSystem->getSizeReadable($file->getSize()), 'feather icon-save', 'success');
            }
            $size = $fileSystem->getSizeReadable($size);
            // Response
            return $this->response->send([
                'status' => 'success',
                'message' => 'Your file(s) was uploaded.',
                'files' => [
                    'counter' => count($results),
                    'size' => $size,
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
    private function checkVokumeSize(Volume $volume, $files): bool {
        $fileSystem = new FileSystemApi();
        if ($volume->getSize() < 1):
            return true;
        endif;
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
