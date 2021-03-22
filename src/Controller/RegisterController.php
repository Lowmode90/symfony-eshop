<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\Customer;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegisterController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/inscription", name="register")
     */
    public function index(Request $request, UserPasswordEncoderInterface $encoder): Response
    {
        $notification = null;

        $customer = new Customer();
        $form = $this->createForm(RegisterType::class, $customer);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $customer = $form->getData();

            $search_email = $this->entityManager->getRepository(Customer::class)->findOneByEmail($customer->getEmail());

            if (!$search_email)
            {
                $password = $encoder->encodePassword($customer, $customer->getPassword());

                $customer->setPassword($password);

                $this->entityManager->persist($customer);
                $this->entityManager->flush();

                $mail = new Mail();
                $content = "Bonjour ".$customer->getFirstname().".<br>Bienvenue sur la première boutique dédiée au Made in France.<br>
                    <br>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Molestias, sunt?";
                $mail->send($customer->getEmail(), $customer->getFirstname(), 'Bienvenue sur La Boutique Française', $content);

                $notification = 'Votre inscription a bien été prise en compte. Vous pouvez dès à présent vous connecter à votre compte.';
            } else {
                $notification = 'L\'email que vous avez renseigné existe déjà.';
            }
        }

        return $this->render('register/index.html.twig', [
            'form'         => $form->createView(),
            'notification' => $notification
        ]);
    }
}
