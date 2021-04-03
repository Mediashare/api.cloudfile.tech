<?php

namespace App\Controller;

use App\Entity\Disk;
use App\Entity\File;
use App\Entity\Config;
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
        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Volume::class);
        $authority = $repo->authority($apikey = $request->headers->get('apikey'));
        if ($authority): return $this->response->json($authority); endif;
        $volume = $repo->findOneBy(['apikey' => $apikey]);
        
        $files = $request->files;
        if (count($files) > 0):
            // Check Espace Disk
            $free_disk = $this->checkVokumeSize($volume, $files); 
            if (!$free_disk):
                return $this->response->json([
                    'status' => 'error',
                    'message' => 'There is a missing of space on your volume disk.'
                ]);
            endif;

            if (!$volume->getEncrypt() && !empty($request->get('encrypt'))):
                if ($request->get('encrypt') == "false" || $request->get('encrypt') === false):
                    $encrypt = false;
                else: $encrypt = true; endif;
            else: $encrypt = $volume->getEncrypt(); endif;

            
            if (!$volume->getConvertToMp4() && !empty($request->get('convert_to_mp4'))):
                if ($em->getRepository(Config::class)->findOneBy(['cloudfile_password' => $request->get('cloudfile_password')])):
                    if ($request->get('convert_to_mp4') == "false" || $request->get('convert_to_mp4') === false):
                        $convert_to_mp4 = false;
                    else: $convert_to_mp4 = true; endif;
                else: $convert_to_mp4 = false; endif;
            else: $convert_to_mp4 = $volume->getConvertToMp4(); endif;

            // Upload file(s)
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
                    if (\file_exists($disk->getPath()) && \is_writable($disk->getPath())):
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
                        'message' => 'No space into disk(s)!'
                    ]);
                endif;

                // Upload file
                $file = $fileSystem->upload($id, $file, $disk_selected, $volume);

                // Metadata
                // Check if description is base64
                if (isset($_REQUEST['description'])):
                    if ($this->is_base64_string($_REQUEST['description'])):
                        $_REQUEST['description'] = \base64_decode($_REQUEST['description']);
                    endif;
                endif;
                // Set metadata
                $file->setMetadata($_REQUEST);
                $file->setPrivate($volume->getPrivate());
                // ApiKey
                $file->generateApiKey();
                // File encryption
                if ($encrypt): $file->setEncrypt($encrypt); endif;
                // File convertion
                if ($convert_to_mp4): $file->setConvertToMp4($convert_to_mp4); endif;

                // Volume update
                $volume->setUpdateDate(new \DateTime());

                // Record
                $em->persist($file, $volume);
                $em->flush();

                if ($file->getEncrypt()):
                    $file = $fileSystem->encrypt($file);
                    if ($file):
                        $em->persist($file);
                        $em->flush();
                    endif;
                endif;
                
                $results[] = $file->getInfo();
                $size += $file->getSize();
            }
            $size = $fileSystem->getSizeReadable($size);
            // Response
            return $this->response->json([
                'status' => 'success',
                'message' => 'Your file(s) was uploaded.',
                'files' => [
                    'counter' => count($results),
                    'size' => $size,
                    'results' => $results
                ]
            ]);
        endif;
        return $this->response->json([
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

    private function is_base64_string(string $string) {
        // first check if we're dealing with an actual valid base64 encoded string
        if (($decoded = base64_decode($string, true)) === false) return false;
        // now check whether the decoded data could be actual text
        $encodage = mb_detect_encoding($decoded);
        if (in_array($encodage, array('UTF-8', 'ASCII'))):
            return true;
        else:
            return false;
        endif;
    }
}
