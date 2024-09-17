<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailController extends AbstractController
{
    public function sendEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('taher.benismail@gmail.com')
            ->to('benismail.taher@esprit.tn')
            ->subject('Subject of the Email')
            ->text('This is the body of the email.');

        $mailer->send($email);

        return new Response('Email sent successfully');
    }
}
