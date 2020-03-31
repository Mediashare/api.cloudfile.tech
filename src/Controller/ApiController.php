<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Container;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiController extends AbstractController
{
    /**
     * @Route("/", name="api")
     */
    public function index(Request $request) {
        $fileSystem = new FileSystemApi();
        $em = $this->getDoctrine()->getManager();
        
        $apiKey = $request->headers->get('apikey');
        if ($apiKey):
            $files = $em->getRepository(File::class)->findBy(['apiKey' => $apiKey], ['createDate' => 'DESC']);
        else:
            $files = $em->getRepository(File::class)->findBy(['private' => false], ['createDate' => 'DESC']);
        endif;

        $results = [];
        $size = 0;
        foreach ($files as $file) {
            $results[] = $file->getInfo();;
            $size += $file->getSize();
        }
        return $this->json([
            'status' => 'success',
            'files' => [
                'counter' => count($results),
                'size' => $fileSystem->getSizeReadable($size),
                'results' => $results
            ],
        ]);
    }

    /**
     * @Route("/info/{id}", name="api_info")
     */
    public function info(Request $request, string $id) {
        $file = $this->getFile($request, $id);
        if ($file):
            return $file->getInfo();
        endif;
        return $this->json([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }

    /**
     * @Route("/show/{id}", name="api_show")
     */
    public function show(Request $request, string $id) {
        $file = $this->getFile($request, $id);
        if ($file):
            return new BinaryFileResponse($file->getPath());
        endif;
        return $this->json([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }

    /**
     * @Route("/download/{id}", name="api_download")
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
        return $this->json([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }

    /**
     * @Route("/remove/{id}", name="api_remove")
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
            return $this->json([
                'status' => 'success',
                'message' => '['.$id.'] File was removed.',
            ]);
        endif;
        return $this->json([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }

    /**
     * @Route("/upload", name="api_upload")
     */
    public function upload(Request $request) {
        $files = $request->files;
        if (count($files) > 0):
            $em = $this->getDoctrine()->getManager();
            $stockage = $this->getParameter('kernel.project_dir').$this->getParameter('stockage');
            $fileSystem = new FileSystemApi();
            $results = [];
            $size = 0;
            foreach ($files as $index => $file) {
                // Generate ID
                $id = \uniqid(); // Generate uniqid()
                while ($em->getRepository(File::class)->find($id)) { // Check $id if already used
                    $id = \uniqid();
                }
                // Upload file
                $file = $fileSystem->upload($id, $file, $stockage);
                // Set metadata
                $file->setMetadata($_REQUEST);
                // Container ApiKey
                $apiKey = null;
                if ($request->headers->get('apikey')):
                    $apiKey = $request->headers->get('apikey');
                elseif ($request->get('apikey')):
                    $apiKey = $request->get('apikey');
                endif;
                if ($apiKey):
                    $container = $em->getRepository(Container::class)->findOneBy(['apiKey' => $apiKey], ['updateDate' => 'desc']);
                    if ($container):
                        $file->setContainer($container);
                    else:
                        return $this->json([
                            'status' => 'error',
                            'message' => 'Your ApiKey is not valid.'
                        ]);
                    endif;
                endif;
                // User
                if ($this->getUser()):
                    $file->setUser($this->getUser());
                endif;
                // Record
                $em->persist($file);
                $em->flush();
                
                $results[] = $file->getInfo();
                $size += $file->getSize();
            }
            // Response
            return $this->json([
                'status' => 'success',
                'message' => 'Your file(s) was uploaded.',
                'files' => [
                    'counter' => count($results),
                    'size' => $fileSystem->getSizeReadable($size),
                    'results' => $results
                ]
            ]);
        endif;
        return $this->json([
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
