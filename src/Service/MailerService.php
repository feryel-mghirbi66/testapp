<?php

// src/Service/MailerService.php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendAdminNotification($subject, $body)
    {
        $email = (new Email())
            ->from('cryptomonnaie95@gmail.com') // L'adresse de l'expéditeur
            ->to('bouhjarmariem012@gmail.com') // L'adresse de l'administrateur
            ->subject($subject)
            ->html($body); // Le corps de l'email au format HTML

        $this->mailer->send($email);
    }


    public function sendEmail(string $to, string $password,string $userEmail): void
    {
        try {
            $email = (new Email())
                ->from('cryptomonnaie95@gmail.com')
                ->to($to)
                ->subject('Bienvenue - Votre Compte')
                ->text("Bonjour,\n\nVotre compte a été créé avec succès.\nVotre email: $userEmail\nVotre mot de passe est : $password")
            ->html("<p>Bonjour,</p><p>Votre compte a été créé avec succès.</p><p><strong>Email :</strong> $userEmail</p><p><strong>Mot de passe :</strong> $password</p>");
    
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Loggez l'erreur ou affichez-la pour le débogage
            error_log('Erreur lors de l\'envoi de l\'e-mail : ' . $e->getMessage());
            throw new \Exception('Échec de l\'envoi de l\'e-mail : ' . $e->getMessage());
        }
    }
}
