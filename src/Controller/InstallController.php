<?php

namespace App\Controller;

use App\Entity\Disk;
use App\Entity\User;
use App\Entity\Config;
use App\Security\CustomAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class InstallController extends AbstractController
{
    /**
     * @Route("/install", name="install")
     */
    public function install(Request $request, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, CustomAuthenticator $authenticator) {
        $em = $this->getDoctrine()->getManager();
        $installed = $em->getRepository(User::class)->findAll();
        if ($installed): return $this->redirectToRoute('easyadmin'); endif;

        if ($request->get('username') && $request->get('password')):
            $user = new User();
            $user->setUsername($request->get('username'));
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $request->get('password')
                )
            );
            $em->persist($user);
            
            $config = new Config();
            $config->setCloudfilePassword($request->get('cloudfile_password'));
            $em->persist($config);

            $disk = new Disk();
            $disk->setName($request->get('disk_name'));
            $disk->setPath($request->get('disk_path'));
            $em->persist($disk);

            $em->flush();

            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );
        endif;
        return $this->render('install/index.html.twig');
    }
}
