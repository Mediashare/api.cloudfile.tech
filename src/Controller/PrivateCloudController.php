<?php

namespace App\Controller;

use App\Entity\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PrivateCloudController extends AbstractController
{
    /**
     * @Route("/private", name="private_cloud")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();
        $containers = $em->getRepository(Container::class)->findBy(['user' => $this->getUser()], ['updateDate' => 'DESC']);

        return $this->render('private_cloud/index.html.twig', [
            'containers' => $containers
        ]);
    }

    /**
     * @Route("/private/show/{id}", name="private_cloud_show")
     */
    public function show(string $id)
    {
        $em = $this->getDoctrine()->getManager();
        $container = $em->getRepository(Container::class)->findOneBy([
            'id' => $id,
            'user' => $this->getUser()
        ], ['updateDate' => 'DESC']);

        return $this->render('private_cloud/show.html.twig', [
            'container' => $container
        ]);
    }

    /**
     * @Route("/private/new", name="private_cloud_new")
     */
    public function new(Request $request)
    {
        if ($request->getMethod() === 'POST'):
            $container = new Container();
            $container->setName($request->get('name'));
            $container->setDescription($request->get('description'));
            $container->setUser($this->getUser());
            $em = $this->getDoctrine()->getManager();
            $em->persist($container);
            $em->flush();

            return $this->redirect('private_cloud');
        endif;
        return $this->redirect('private_cloud');
    }
}
