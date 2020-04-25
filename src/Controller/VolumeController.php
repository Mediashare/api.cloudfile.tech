<?php

namespace App\Controller;

use App\Entity\Volume;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VolumeController extends AbstractController
{
    /**
     * @Route("/volume/new", name="volume_new")
     */
    public function new(Request $request) {
        $volume = new Volume();
        $volume->setEmail($request->get('email')); // Email association
        $volume->setSize($request->get('size')); // Gb

        $em = $this->getDoctrine()->getManager();
        $em->persist($volume);
        $em->flush();

        return $this->json([
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
     * @Route("/volume/edit/{id}", name="volume_edit")
     */
    public function edit(Request $request, string $id) {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/VolumeController.php',
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
