<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class InstallController extends AbstractController
{
    /**
     * @Route("/install", name="install")
     */
    public function index()
    {
        return $this->render('install/index.html.twig', [
            'controller_name' => 'InstallController',
        ]);
    }
}
