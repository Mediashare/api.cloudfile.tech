<?php

namespace App\Controller;

use App\Service\Slugify;
use App\Form\ProfileType;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profile", name="profile")
     */
    public function profile(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = $this->getUser();
        if (!$user): // If user not connected
            return $this->redirectToRoute('app_login');
        endif;

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()):
            if ($form->get('plainPassword')->getData()): // New Password
                $user->setPassword(
                    $passwordEncoder->encodePassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            endif;

            // Avatar
            $avatar = $form->get('avatar')->getData();
            if ($avatar) {
                // this is needed to safely include the file name as part of the URL
                $slug = new Slugify();
                $newFilename = $slug->getSlug($user->getUsername()).'-'.uniqid().'.'.$avatar->guessExtension();

                // Move the file to the directory where avatar are stored
                $avatar->move(
                    $this->getParameter('avatar_directory'),
                    $newFilename
                );

                // updates the 'avatarname' property to store the PDF file name
                // instead of its contents
                $user->setAvatar('images/avatar/'.$newFilename);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
        endif;

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    public function info() {
        $user = $this->getUser();
        // Get total size used
        $files = $user->getFiles();
        $size = 0;
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        $fileSystem = new FileSystemApi();
        $stockage = $fileSystem->getSizeReadable($size);

        return $this->render('profile/_info.html.twig', [
            'user' => $user,
            'stockage' => $stockage
        ]);
    }
}
