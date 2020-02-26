<?php

namespace App\Controller;

use App\Entity\File;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PublicCloudController extends AbstractController
{
    /**
     * @Route("/public", name="public_cloud")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();
        $files = $em->getRepository(File::class)->findBy(['private' => false], ['createDate' => 'DESC']);
        return $this->render('public_cloud/index.html.twig', [
            'files' => $files,
        ]);
    }

    /**
     * @Route("/public/{id}", name="public_cloud_file_show")
     */
    public function show(string $id)
    {
        $em = $this->getDoctrine()->getManager();
        $file = $em->getRepository(File::class)->findOneBy([
            'id' => $id,
            'private' => false
        ], ['createDate' => 'DESC']);
        if (!$file):
            return $this->redirect('public_cloud');
        endif;
        return $this->render('file/show.html.twig', [
            'file' => $file,
        ]);
    }
}
