<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    /**
     * @Route("/nous-contacter", name="contact")
     */
    public function index(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() and $form->isValid())
        {
            $this->addFlash('notice', 'Merci pour votre message, nos équipes vont vous répondre dans les meilleurs délais.');

            $formData = $form->getData();

            $content  = "Nom : ".$formData['firstname']." ".$formData['lastname']."<br>";
            $content .= "Email : ".$formData['email']."<br>";
            $content .= "Message : <br>".$formData['content'];

            $mail = new Mail();
            $mail->send('patrick.laboutiquefrancaise@gmail.com', 'La Boutique Française', 'Vous avez reçu une nouvelle demande de contact', $content);
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
