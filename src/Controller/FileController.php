<?php

namespace App\Controller;

use App\Entity\File;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FileController extends AbstractController
{
    /**
     * @Route("/file/{id}", name="file_show")
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
