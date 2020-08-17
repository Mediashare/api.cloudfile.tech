<?php

namespace App\Controller;

use App\Entity\Disk;
use App\Entity\Config;
use App\Entity\Volume;
use App\Service\Response;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VolumeController extends AbstractController
{
    public function __construct() {
        $this->response = new Response();
    }

    /**
     * Display all volumes public
     * @Route("/volumes", name="volumes")
     */
    public function list() {
        $em = $this->getDoctrine()->getManager();
        $volumes = $em->getRepository(Volume::class)->findBy(['private' => false], ['updateDate' => 'DESC']);
        $results = [];
        foreach ($volumes as $volume):
            $results[] = $volume->getInfo($all_data = false);
        endforeach;
        return $this->response->json([
            'status' => 'success',
            'volumes' => $results
        ]);
    }

    /**
     * Create new volume
     * @Route("/volume/new", name="volume_new")
     */
    public function new(Request $request) {
        $em = $this->getDoctrine()->getManager();
        if (!$em->getRepository(Config::class)->findOneBy(['cloudfile_password' => $request->get('cloudfile_password')])):
            return $this->response->json([
                'status' => 'error',
                'message' => 'Authority not valid for volume creation.'
            ]);
        endif;
        
        $volume = new Volume();
        $volume->setName($request->get('name'));
        $volume->setSize($request->get('size')); // Gb

        $em->persist($volume);
        $em->flush();

        return $this->response->json([
            'status' => 'success',
            'volume' => $volume->getInfo()
        ]);
    }

    /**
     * Display volume informations
     * @Route("/volume", name="volume")
     */
    public function volume(Request $request) {
        // Check Authority
        $repo = $this->getDoctrine()
            ->getManager()
            ->getRepository(Volume::class);
        $authority = $repo->authority($apikey = $request->headers->get('apikey'));
        if ($authority): return $this->response->json($authority); endif;
        $volume = $repo->findOneBy(['apikey' => $apikey]);

        return $this->response->json([
            'status' => 'success',
            'volume' => $volume->getInfo()
        ]);
    }

    /**
     * @Route("/volume/edit", name="volume_edit")
     */
    public function edit(Request $request) {
        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Volume::class);
        $authority = $repo->authority($apikey = $request->headers->get('apikey'));
        if ($authority): return $this->response->json($authority); endif;
        $volume = $repo->findOneBy(['apikey' => $apikey]);

        if ($name = (string) $request->get('name')):
            $volume->setName($name);
        endif;        
        if (!empty($request->get('private'))):
            $private = $request->get('private');
            if ($private == "true" || $private === true):
                $private = true;
            else: $private = false;endif;
            $volume->setPrivate($private);
        endif;

        if ($em->getRepository(Config::class)->findOneBy(['cloudfile_password' => $request->get('cloudfile_password')])):
            if ($size = (int) $request->get('size')):
                $volume->setSize($size);
            endif;
        endif;
        
        $volume->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->flush();
        
        return $this->response->json([
            'status' => 'success',
            'message' => 'This action can take several minutes',
            'volume' => $volume->getInfo()
        ]);
    }

    /**
     * Reset ApiKey from volume & files associated
     * @Route("/volume/reset/apikey", name="volume_reset_apikey")
     */
    public function resetApiKey(Request $request) {
        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Volume::class);
        $authority = $repo->authority($apikey = $request->headers->get('apikey'));
        if ($authority): return $this->response->json($authority); endif;
        $volume = $repo->findOneBy(['apikey' => $apikey]);

        $volume->generateApiKey();
        $volume->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->flush();

        return $this->response->json([
            'status' => 'success',
            'message' => 'This action can take several minutes',
            'volume' => $volume->getInfo()
        ]);
    }

    /**
     * Remove all files from volume
     * @Route("/volume/clear", name="volume_clear")
     */
    public function clear(Request $request) {
        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Volume::class);
        $authority = $repo->authority($apikey = $request->headers->get('apikey'));
        if ($authority): return $this->response->json($authority); endif;
        $volume = $repo->findOneBy(['apikey' => $apikey]);

        // Remove file(s)
        foreach ($volume->getFiles() as $file):
            $volume->removeFile($file);
            $em->remove($file);
        endforeach;

        // Remove file(s) from Disks
        $fileSystem = new FileSystemApi();
        $disks = $em->getRepository(Disk::class)->findAll();
        foreach ($disks as $disk):
            $fileSystem->remove(rtrim($disk->getPath(), '/').'/'.$volume->getId());
        endforeach;
        
        $volume->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->flush();

        return $this->response->json([
            'status' => 'success',
            'message' => 'This action can take several minutes.'
        ]);
    }

    /**
     * Delete volume & all files associated
     * @Route("/volume/delete", name="volume_delete")
     */
    public function delete(Request $request) {
        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Volume::class);
        $authority = $repo->authority($apikey = $request->headers->get('apikey'));
        if ($authority): return $this->response->json($authority); endif;
        $volume = $repo->findOneBy(['apikey' => $apikey]);
        
        // Remove file(s)
        foreach ($volume->getFiles() as $file):
            $volume->removeFile($file);
            $em->remove($file);
        endforeach;
        // Remove file(s) from Disks
        $fileSystem = new FileSystemApi();
        $disks = $em->getRepository(Disk::class)->findAll();
        foreach ($disks as $disk):
            $fileSystem->remove(rtrim($disk->getPath(), '/').'/'.$volume->getId());
        endforeach;

        // Delete Volume
        $em->remove($volume);
        $em->flush();
        
        return $this->response->json([
            'status' => 'success',
            'message' => 'Volume  ['.$volume->getId().'] was deleted.'
        ]);
    }
}
