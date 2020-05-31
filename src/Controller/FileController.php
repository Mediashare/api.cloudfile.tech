<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Volume;
use App\Service\Response;
use App\Service\FileSystemApi;
use Mediashare\ShowContent\ShowContent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        $em = $this->getDoctrine()->getManager();
        $apikey = $request->headers->get('apikey');
        if ($apikey):
            $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey]);
            if (!$volume):
                return $this->send([
                    'status' => 'error',
                    'message' => 'Volume not found with your apikey.'
                ]);
            endif;
            $counter = count($volume->getFiles());
        else:
            $counter = count($em->getRepository(File::class)->findBy(['private' => false]));
            $volume = null;
        endif;
        $files = $em->getRepository(File::class)->pagination($volume, $page, $max = 100);
        if ($volume): $volume = $volume->getInfo(); endif;
        
        $results = [];
        $size = 0;
        foreach ($files as $file) {
            $results[] = $file->getInfo();;
            $size += $file->getSize();
        }
        
        $fileSystem = new FileSystemApi();
        return $this->response->send([
            'status' => 'success',
            'volume' => $volume,
            'files' => [
                'page' => $page,
                'counter' => $counter,
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
        $em = $this->getDoctrine()->getManager();
        $apikey = $request->headers->get('apikey') ?? $request->get('apikey');
        if ($apikey):
            $authority = $this->checkAuthority($apikey);
            if ($authority):
                return $authority;
            endif;
            $file = $em->getRepository(File::class)->findOneBy(['apikey' => $apikey, 'id' => $id], ['createDate' => 'DESC']);    
        else:
            $file = $em->getRepository(File::class)->findOneBy(['id' => $id, 'private' => false], ['createDate' => 'DESC']);
        endif;

        if (!$file):
            return $this->response->send([
                'status' => 'error',
                'message' => 'File not found.',
            ], 404);
        endif;
        
        return $this->response->send([
            'status' => 'success',
            'file' => $file->getInfo()
        ]);
    }

    /**
     * @Route("/show/{id}", name="show")
     */
    public function show(Request $request, string $id) {
        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $apikey = $request->headers->get('apikey') ?? $request->get('apikey');
        if ($apikey):
            $authority = $this->checkAuthority($apikey);
            if ($authority):
                return $authority;
            endif;
            $file = $em->getRepository(File::class)->findOneBy(['apikey' => $apikey, 'id' => $id], ['createDate' => 'DESC']); 
        else:
            $file = $em->getRepository(File::class)->findOneBy(['id' => $id, 'private' => false], ['createDate' => 'DESC']);
        endif;

        if (!$file):
            return $this->response->send([
                'status' => 'error',
                'message' => 'File not found.',
            ], 404);
        endif;

        $response = new BinaryFileResponse($file->getPath(), 200);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }

    /**
     * @Route("/render/{id}", name="render")
     */
    public function renderFile(Request $request, string $id) {
        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $apikey = $request->headers->get('apikey') ?? $request->get('apikey');
        if ($apikey):
            $authority = $this->checkAuthority($apikey);
            if ($authority):
                return $authority;
            endif;
            $file = $em->getRepository(File::class)->findOneBy(['apikey' => $apikey, 'id' => $id], ['createDate' => 'DESC']);        
        else:
            $file = $em->getRepository(File::class)->findOneBy(['id' => $id, 'private' => false], ['createDate' => 'DESC']);
        endif;
        
        if (!$file):
            return $this->response->send([
                'status' => 'error',
                'message' => 'File not found.',
            ], 404);
        endif;

        // Generate url for bypass apikey protection
        $url = $file->getInfo()['urls']['show'];
        $showContent = new ShowContent($url);
        $showContent->file->mimeType = $file->getMimeType();
        if ($showContent->file->getType() === "text"):
            $showContent->file->content = \file_get_contents($file->getPath());
            if (substr($file->getName(), -3) === ".md"):
                $showContent->file->mimeType = "text/markdown";
            endif;
        endif;

        $showContent->cache = $this->getParameter('kernel_dir').'/var/cache';
        $response = new \Symfony\Component\HttpFoundation\Response($showContent->show());
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }

    /**
     * @Route("/download/{id}", name="download")
     */
    public function download(Request $request, string $id) {
        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $apikey = $request->headers->get('apikey') ?? $request->get('apikey');
        if ($apikey):
            $authority = $this->checkAuthority($apikey);
            if ($authority):
                return $authority;
            endif;
            $file = $em->getRepository(File::class)->findOneBy(['apikey' => $apikey, 'id' => $id], ['createDate' => 'DESC']);
        else:
            $file = $em->getRepository(File::class)->findOneBy(['id' => $id, 'private' => false], ['createDate' => 'DESC']);
        endif;
        
        if (!$file):
            return $this->response->send([
                'status' => 'error',
                'message' => 'File not found.',
            ], 404);
        endif;

        $response = new BinaryFileResponse($file->getPath());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file->getName()
        );
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        $response->headers->set('Content-Length', $file->getSize());
        return $response;
    }

    /**
     * @Route("/remove/{id}", name="remove")
     */
    public function remove(Request $request, string $id) {
        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $apikey = $request->headers->get('apikey') ?? $request->get('apikey');
        $authority = $this->checkAuthority($apikey);
        if ($authority):
            return $authority;
        endif;

        $file = $em->getRepository(File::class)->findOneBy(['apikey' => $apikey, 'id' => $id], ['createDate' => 'DESC']);    
        if (!$file):
            return $this->response->send([
                'status' => 'error',
                'message' => 'File not found.',
            ], 404);
        endif;
        
        // Remove to database
        $em = $this->getDoctrine()->getManager();
        $volume = $file->getVolume()->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->remove($file);
        $em->flush();
        // Remove file stockage
        $fileSystem = new FileSystemApi();
        $fileSystem->remove(dirname($file->getPath()));
        // Response
        return $this->response->send([
            'status' => 'success',
            'message' => '['.$id.'] File was removed.',
        ]);
    }

    /**
     * Check if ApiKey exist & if Volume associated.
     *
     * @param Request $request
     * @return Response|null
     */
    private function checkAuthority($apikey) {
        $em = $this->getDoctrine()->getManager();
        if (!$apikey):
            return $this->response->send([
                'status' => 'error',
                'message' => 'ApiKey not found in Header/Post data.'
            ]);
        endif;
        $file = $em->getRepository(File::class)->findOneBy(['apikey' => $apikey]);
        if (!$file):
            return $this->response->send([
                'status' => 'error',
                'message' => 'File not found with your apikey.'
            ]);
        endif;
        
        return null; // Checkup valid!
    }
}
