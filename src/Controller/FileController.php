<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Proxy;
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
        $apikey = $request->headers->get('apikey') ?? $request->get('apikey');
        if ($apikey):
            $authority = $this->response->checkAuthority($em, $apikey);
            if ($authority):
                return $authority;
            endif;
            $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
            $counter = count($volume->getFiles());
            $volume = $volume->getInfo();
        else:
            $counter = count($em->getRepository(File::class)->findBy(['private' => false]));
            $volume = null;
        endif;
        $files = $em->getRepository(File::class)->pagination($apikey, $page, $max = 100);
        
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
            $authority = $this->response->checkAuthority($em, $apikey);
            if ($authority):
                return $authority;
            endif;
            $file = $em->getRepository(File::class)->findOneBy(['apiKey' => $apikey, 'id' => $id], ['createDate' => 'DESC']);    
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
            $authority = $this->response->checkAuthority($em, $apikey);
            if ($authority):
                return $authority;
            endif;
            $file = $em->getRepository(File::class)->findOneBy(['apiKey' => $apikey, 'id' => $id], ['createDate' => 'DESC']);    
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
            $authority = $this->response->checkAuthority($em, $apikey);
            if ($authority):
                return $authority;
            endif;
            $file = $em->getRepository(File::class)->findOneBy(['apiKey' => $apikey, 'id' => $id], ['createDate' => 'DESC']);    
        else:
            $file = $em->getRepository(File::class)->findOneBy(['id' => $id, 'private' => false], ['createDate' => 'DESC']);
        endif;
        
        if (!$file):
            return $this->response->send([
                'status' => 'error',
                'message' => 'File not found.',
            ], 404);
        endif;

        // Generate proxy url for bypass apikey protection
        $proxy = new Proxy($file);
        $em->persist($proxy);
        $em->flush();
        $url = $this->generateUrl('proxy', ['id' => $proxy->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $showContent = new ShowContent($url);
        $showContent->file->mimeType = $file->getMimeType();
        if ($showContent->file->getType() === "text"):
            $showContent->file->content = \file_get_contents($file->getPath());
            if (substr($file->getName(), -3) === ".md"):
                $showContent->file->mimeType = "text/markdown";
            elseif ($showContent->file->getMimeType() === "text/x-php"):
                $showContent->file->content = str_replace('<?', '&lt;?', $showContent->file->getContent());
                $showContent->file->content = str_replace('?>', '?&gt;', $showContent->file->getContent());
            endif;
        endif;
        $showContent->cache = $this->getParameter('kernel_dir').'/var/cache';
        return new \Symfony\Component\HttpFoundation\Response($showContent->show());
    }


    /**
     * @Route("/proxy/{id}", name="proxy")
     */
    public function proxy(string $id) {
        $em = $this->getDoctrine()->getManager();
        $proxy = $em->getRepository(Proxy::class)->find($id);
        if ($proxy):
            $date = new \DateTime();
            $diff = (int) $date->diff($proxy->getCreateDate())->format('%h%');
            if ($diff > 6):
                $em->remove($proxy);
                $em->flush();
            endif;
            $response = new BinaryFileResponse($proxy->getFile()->getPath(), 200);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Headers', '*');
            $response->headers->set('Content-Type', $proxy->getFile()->getMimeType());
            return $response;
        else:
        endif;

        return $this->response->send([
            'status' => 'error',
            'message' => 'Your proxy session was not found.'
        ]);
    }

    /**
     * @Route("/download/{id}", name="download")
     */
    public function download(Request $request, string $id) {
        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $apikey = $request->headers->get('apikey') ?? $request->get('apikey');
        if ($apikey):
            $authority = $this->response->checkAuthority($em, $apikey);
            if ($authority):
                return $authority;
            endif;
            $file = $em->getRepository(File::class)->findOneBy(['apiKey' => $apikey, 'id' => $id], ['createDate' => 'DESC']);    
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
        $authority = $this->response->checkAuthority($em, $apikey);
        if ($authority):
            return $authority;
        endif;

        $file = $em->getRepository(File::class)->findOneBy(['apiKey' => $apikey, 'id' => $id], ['createDate' => 'DESC']);
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
}
