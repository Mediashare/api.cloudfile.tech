<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PrivateCloudController extends AbstractController
{
    /**
     * @Route("/private", name="private_cloud")
     */
    public function index()
    {
        return $this->render('private_cloud/index.html.twig');
    }
}
