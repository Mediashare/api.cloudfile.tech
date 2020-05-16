<?php

namespace App\Controller;

use App\Entity\Volume;
use App\Service\PingIt;
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
        $volume->setName($request->get('name'));
        $volume->setSize($request->get('size')); // Gb
        $volume->setStockage(rtrim($this->getParameter('stockage'), '/').'/'.$volume->getId());

        $em = $this->getDoctrine()->getManager();
        $em->persist($volume);
        $em->flush();

        $pingIt = new PingIt($this->getParameter('pingit_volumes'));
        $pingIt->send('[API] Volume added', '['.$volume->getId().'] '.$volume->getName().' - ('.$volume->getSize().'Gb) has been created.', 'feather icon-folder', 'success');

        return $this->response->send([
            'status' => 'success',
            'volume' => $volume->getInfo()
        ]);
    }

    /**
     * @Route("/volume/edit", name="volume_edit")
     */
    public function edit(Request $request) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        
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

        if ($request->get('cloudfile_password') === $this->getParameter('cloudfile_password')):
            if ($size = (int) $request->get('size')):
                $volume->setSize($size);
            endif;
            if ($online = $request->get('online')):
                if ($online == "true"):
                    $online = true;
                else: $online = false;endif;
                $volume->setOnline($online);
            endif;
        endif;
        
        $volume->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->flush();
        
        return $this->response->send([
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
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        $volume->generateApiKey();

        $volume->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->flush();

        $pingIt = new PingIt($this->getParameter('pingit_volumes'));
        $pingIt->send('[API] Volume reset ApiKey', '['.$volume->getId().'] '.$volume->getName().' ('.$volume->getSize().'Gb) ApiKey has been regenerated.', 'fa fa-key', 'warning');

        return $this->response->send([
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
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;

        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);        
        // Remove file(s)
        $fileSystem = new FileSystemApi();
        $fileSystem->remove($volume->getStockage());
        
        $volume->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->flush();

        $pingIt = new PingIt($this->getParameter('pingit_volumes'));
        $pingIt->send('[API] Volume clear', '['.$volume->getId().'] '.$volume->getName().' ('.$volume->getSize().'Gb) has been cleared.', 'fa fa-recycle', 'warning');

        return $this->response->send([
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
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        
        // Remove file(s)
        $fileSystem = new FileSystemApi();
        $fileSystem->remove($volume->getStockage());

        // Delete Volume
        $em->remove($volume);
        $em->flush();

        $pingIt = new PingIt($this->getParameter('pingit_volumes'));
        $pingIt->send('[API] Volume deleted', '['.$volume->getId().'] '.$volume->getName().' ('.$volume->getSize().'Gb) has been deleted.', 'feather icon-trash', 'danger');
        
        return $this->response->send([
            'status' => 'success',
            'message' => 'Volume  ['.$volume->getId().'] was deleted.'
        ]);
    }
}
