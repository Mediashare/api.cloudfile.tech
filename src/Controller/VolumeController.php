<?php

namespace App\Controller;

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
     * @Route("/volume/new", name="volume_new")
     */
    public function new(Request $request) {
        if ($this->getParameter('volume_password') 
            && $request->get('volume_password') !== $this->getParameter('volume_password')):
                return $this->response->send([
                    'status' => 'error',
                    'message' => 'Authority not valid for volume creation.'
                ]);
        endif;
        
        $volume = new Volume();
        $volume->setEmail($request->get('email')); // Email association
        $volume->setSize($request->get('size')); // Gb

        $em = $this->getDoctrine()->getManager();
        $em->persist($volume);
        $em->flush();

        return $this->response->send([
            'status' => 'success',
            'volume' => [
                'id' => $volume->getId(),
                'email' => $volume->getEmail(),
                'apikey' => $volume->getApiKey(),
                'size' => $volume->getSize(),
            ]
        ]);
    }

    /**
     * @Route("/volume/info", name="volume_info")
     */
    public function info(Request $request) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        return $this->response->send($volume->getInfo());
    }

    /**
     * @Route("/volumes/retrieve", name="volumes_retrieve")
     */
    public function retrieve(Request $request) {
        if ($this->getParameter('volume_password') 
            && $request->get('volume_password') !== $this->getParameter('volume_password')):
                return $this->response->send([
                    'status' => 'error',
                    'message' => 'Authority not valid for volume creation.'
                ]);
        endif;
        
        $email = $request->get('email');
        $em = $this->getDoctrine()->getManager();
        $volumes = $em->getRepository(Volume::class)->findBy(['email' => $email]);
        if (!$volumes):
            return $this->response->send([
                'status' => 'error',
                'message' => 'Not volume(s) associated with this email.'
            ]);
        endif;

        $results = [];
        foreach ($volumes as $volume) {
            $results[] = $volume->getInfo();
        }

        return $this->response->send($results);
    }

    /**
     * @Route("/volume/clear", name="volume_clear")
     */
    public function clear(Request $request) {
        $apiKey = $request->headers->get('apikey');
        if (!$apiKey):
            return $this->response->send([
                'status' => 'error',
                'message' => 'ApiKey not found in Header.'
            ]);
        endif;
        $em = $this->getDoctrine()->getManager();
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apiKey, 'online' => true]);
        if (!$volume):
            return $this->response->send([
                'status' => 'error',
                'message' => 'Volume not found with your ApiKey.'
            ]);
        endif;

        // Remove file(s)
        $fileSystem = new FileSystemApi();
        foreach ($volume->getFiles() as $file) {    
            // Remove to database
            $em->remove($file);
            $em->flush();
            // Remove file stockage
            $fileSystem->remove($file->getStockage());
        }
        return $this->response->send([
            'status' => 'success',
            'message' => 'All files from volume ['.$volume->getId().'] was deleted.'
        ]);
    }

    /**
     * @Route("/volume/generate/apikey", name="volume_generate_apikey")
     */
    public function generateApiKey(Request $request) {
        $apiKey = $request->headers->get('apikey');
        if (!$apiKey):
            return $this->response->send([
                'status' => 'error',
                'message' => 'ApiKey not found in Header.'
            ]);
        endif;
        $em = $this->getDoctrine()->getManager();
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apiKey, 'online' => true]);
        if (!$volume):
            return $this->response->send([
                'status' => 'error',
                'message' => 'Volume not found with your ApiKey.'
            ]);
        endif;

        $volume->generateApiKey();
        $em->persist($volume);
        $em->flush();
        
        return $this->response->send([
            'status' => 'success',
            'volume' => $volume->getInfo()
        ]);
    }

    /**
     * @Route("/volume/delete/{id}", name="volume_delete")
     */
    public function delete(Request $request, string $id) {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/VolumeController.php',
        ]);
    }
}
