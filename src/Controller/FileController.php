<?php

namespace App\Controller;

use App\Entity\Container;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FileController extends AbstractController
{
    /**
     * Upload form
     *
     * @return render
     */
    public function upload(?string $container = null)
    {
        if ($container):
            $em = $this->getDoctrine()->getManager();
            $container = $em->getRepository(Container::class)->find($container);
        else:
            $container = null;
        endif;
        return $this->render('file/_upload.html.twig', ['container' => $container]);
    }
}
