<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\Customer;
use App\Entity\ResetPassword;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ResetPasswordController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/mot-de-passe-oublie", name="reset_password")
     */
    public function index(Request $request): Response
    {
        if ($this->getUser())
        {
            return $this->redirectToRoute('home');
        }

        if ($request->get('email'))
        {
            $user = $this->entityManager->getRepository(Customer::class)->findOneByEmail($request->get('email'));

            if ($user)
            {
                $reset_password = new ResetPassword();
                $reset_password->setUser($user);
                $reset_password->setToken(uniqid());
                $reset_password->setCreatedAt(new \DateTime());

                $this->entityManager->persist($reset_password);
                $this->entityManager->flush();

                $url = $this->generateUrl('change_password', [
                    'token' => $reset_password->getToken()
                ]);

                $mail = new Mail();
                $content  = "Bonjour ".$user->getFirstname().".<br>Vous avez demandé à réinitialiser votre mot de passe sur le site de La Boutique Française.<br><br>.";
                $content .= "Merci de bien vouloir cliquer sur le lien suivant pour <a href='".$url."'>mettre à jour votre mot de passe</a>.";
                $mail->send($user->getEmail(), $user->getFirstname().' '.$user->getLastname(), 'Réinitialiser votre mot de passe sur La Boutique Française', $content);

                $this->addFlash('notice', 'Vous allez recevoir dans quelques instants un email avec la procédure pour réinitialiser votre mot de passe.');
            } else {
                $this->addFlash('notice', 'Cette adresse email est inconnue.');
            }
        }

        return $this->render('reset_password/index.html.twig');
    }

    /**
     * @Route("/modifier-mon-mot-de-passe/{token}", name="change_password")
     */
    public function change(Request $request, $token, UserPasswordEncoderInterface $encoder): Response
    {
        $reset_password = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);

        if (!$reset_password)
        {
            return $this->redirectToRoute('reset_password');
        }

        $now = new \DateTime();
        if ($now > $reset_password->getCreatedAt()->modify('+ 3 hour'))
        {
            $this->addFlash('notice', 'Votre demande de mot de passe a expirée. Merci de la renouveler.');

            return $this->redirectToRoute('reset_password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() and $form->isValid())
        {
            $new_pwd = $form->get('new_password')->getData();

            $password = $encoder->encodePassword($reset_password->getUser(), $new_pwd);
            $reset_password->getUser()->setPassword($password);

            $this->entityManager->flush();

            $this->addFlash('notice', 'Votre mot de passe a bien été mis à jour.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/change.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
