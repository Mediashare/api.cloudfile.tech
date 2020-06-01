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
            $repo = $em->getRepository(Volume::class);
            $authority = $repo->authority($apikey);
            if ($authority): return $this->response->json($authority); endif;
            $volume = $repo->findOneBy(['apikey' => $apikey]);
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
        return $this->response->json([
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
        $repo = $this->getDoctrine()->getManager()->getRepository(File::class);
        $apikey = $request->headers->get('apikey') ?? $request->get('apikey');
        if ($apikey):
            $authority = $repo->authority($id, $apikey);
            if ($authority): return $authority; endif;
            $file = $repo->findOneBy(['id' => $id], ['createDate' => 'DESC']);    
        else: $file = $repo->findOneBy(['id' => $id, 'private' => false], ['createDate' => 'DESC']); endif;

        if (!$file): return $this->response->json(['status' => 'error', 'message' => 'File not found.'], 404); endif;
        
        return $this->response->json([
            'status' => 'success',
            'file' => $file->getInfo()
        ]);
    }

    /**
     * @Route("/show/{id}", name="show")
     */
    public function show(Request $request, string $id) {
        // Check Authority
        $repo = $this->getDoctrine()->getManager()->getRepository(File::class);
        $apikey = $request->headers->get('apikey') ?? $request->get('apikey');
        if ($apikey):
            $authority = $repo->authority($id, $apikey);
            if ($authority): return $authority; endif;
            $file = $repo->findOneBy(['id' => $id], ['createDate' => 'DESC']); 
        else: $file = $repo->findOneBy(['id' => $id, 'private' => false], ['createDate' => 'DESC']); endif;

        if (!$file): return $this->response->json(['status' => 'error', 'message' => 'File not found.'], 404); endif;

        return $this->response->show($file);
    }

    /**
     * @Route("/render/{id}", name="render")
     */
    public function renderFile(Request $request, string $id) {
        // Check Authority
        $repo = $this->getDoctrine()->getManager()->getRepository(File::class);
        $apikey = $request->headers->get('apikey') ?? $request->get('apikey');
        if ($apikey):
            $authority = $repo->authority($id, $apikey);
            if ($authority): return $authority; endif;
            $file = $repo->findOneBy(['id' => $id], ['createDate' => 'DESC']);        
        else: $file = $repo->findOneBy(['id' => $id, 'private' => false], ['createDate' => 'DESC']); endif;
        
        if (!$file): return $this->response->json(['status' => 'error', 'message' => 'File not found.'], 404); endif;

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
        return $this->response->render($showContent);
    }

    /**
     * @Route("/download/{id}", name="download")
     */
    public function download(Request $request, string $id) {
        // Check Authority
        $repo = $this->getDoctrine()->getManager()->getRepository(File::class);
        $apikey = $request->headers->get('apikey') ?? $request->get('apikey');
        if ($apikey):
            $authority = $repo->authority($id, $apikey);
            if ($authority): return $authority; endif;
            $file = $repo->findOneBy(['id' => $id], ['createDate' => 'DESC']);
        else: $file = $repo->findOneBy(['id' => $id, 'private' => false], ['createDate' => 'DESC']); endif;

        if (!$file): return $this->response->json(['status' => 'error', 'message' => 'File not found.'], 404); endif;
        
        return $this->response->downlaod($file);
    }

    /**
     * @Route("/remove/{id}", name="remove")
     */
    public function remove(Request $request, string $id) {
        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $authority = $em->getRepository(Volume::class)->authority($request->headers->get('apikey'));
        if ($authority): return $authority; endif;
        
        $file = $em->getRepository(File::class)->findOneBy(['id' => $id, 'volume' => $volume], ['createDate' => 'DESC']);    
        if (!$file): return $this->response->json(['status' => 'error', 'message' => 'File not found.'], 404); endif;
        
        // Remove to database
        $volume = $file->getVolume()->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->remove($file);
        $em->flush();

        // Remove file stockage
        $fileSystem = new FileSystemApi();
        $fileSystem->remove(dirname($file->getPath()));

        // Response
        return $this->response->json([
            'status' => 'success',
            'message' => '['.$id.'] File was removed.',
        ]);
    }
}
