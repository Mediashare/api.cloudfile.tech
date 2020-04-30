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
     * Display volume informations
     * @Route("/volume", name="volume")
     */
    public function index(Request $request) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        return $this->response->send([
            'status' => 'success',
            'volume' => $volume->getInfo()
        ]);
    }

    /**
     * Create new volume
     * @Route("/volume/new", name="volume_new")
     */
    public function new(Request $request) {
        if ($this->getParameter('cloudfile_password') 
            && $request->get('cloudfile_password') !== $this->getParameter('cloudfile_password')):
                return $this->response->send([
                    'status' => 'error',
                    'message' => 'Authority not valid for volume creation.'
                ]);
        endif;
        
        $volume = new Volume();
        $volume->setEmail($request->get('email')); // Email association
        $volume->setSize($request->get('size')); // Gb
        $volume->setStockage(rtrim($this->getParameter('stockage'), '/').'/'.$volume->getId());

        $em = $this->getDoctrine()->getManager();
        $em->persist($volume);
        $em->flush();

        return $this->response->send([
            'status' => 'success',
            'volume' => $volume->getInfo()
        ]);
    }

    /**
     * Reset ApiKey from volume & files associated
     * @Route("/volume/reset/apikey", name="volume_reset_apikey")
     */
    public function resetApiKey(Request $request) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        $volume->generateApiKey();

        foreach ($volume->getFiles() as $file):
            $file->setApiKey($volume->getApiKey());
        endforeach;

        $volume->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->flush();
        
        return $this->response->send([
            'status' => 'success',
            'volume' => $volume->getInfo()
        ]);
    }

    /**
     * Remove all files from volume
     * @Route("/volume/clear", name="volume_clear")
     */
    public function clear(Request $request) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;

        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        
        // Remove file(s)
        $fileSystem = new FileSystemApi();
        $fileSystem->remove($volume->getStockage());
        foreach ($volume->getFiles() as $file):
            // Remove to database
            $em->remove($file);
            $em->flush();
        endforeach;
        
        $volume->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->flush();

        return $this->response->send([
            'status' => 'success',
            'message' => 'All files from volume ['.$volume->getId().'] was deleted.'
        ]);
    }

    /**
     * Delete volume & all files associated
     * @Route("/volume/delete", name="volume_delete")
     */
    public function delete(Request $request) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        
        // Remove file(s)
        $fileSystem = new FileSystemApi();
        $fileSystem->remove($volume->getStockage());
        foreach ($volume->getFiles() as $file) {    
            // Remove to database
            $em->remove($file);
            $em->flush();
        }
        // Delete Volume
        $em->remove($volume);
        $em->flush();
        
        return $this->response->send([
            'status' => 'success',
            'message' => 'Volume  ['.$volume->getId().'] was deleted.'
        ]);
    }
}
