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
     * Upload form
     *
     * @return render
     */
    public function form()
    {
        return $this->render('public_cloud/_form.html.twig');
    }
}
