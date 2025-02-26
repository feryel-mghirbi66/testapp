<?php

namespace App\Controller;

use App\Service\TwilioSmsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class TwilioSmsController extends AbstractController
{
    private TwilioSmsService $twilioSmsService;

    public function __construct(TwilioSmsService $twilioSmsService)
    {
        $this->twilioSmsService = $twilioSmsService;
    }

    #[Route('/send-sms', name: 'twilio_sms_send', methods: ['GET', 'POST'])]
    public function sendSms(): Response
    {
        // Call the sendSms method to send the SMS
        $result = $this->twilioSmsService->sendSms('+21626341370', 'ya good !');

        // Return the result (can be success message or response from Twilio)
        return new Response('SMS sent: ' . $result);
    }
}
