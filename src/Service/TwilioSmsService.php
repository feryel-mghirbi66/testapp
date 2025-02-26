<?php
 namespace App\Service;

 use Symfony\Contracts\HttpClient\HttpClientInterface;
 use Psr\Log\LoggerInterface;
 
 class TwilioSmsService
 {
     private HttpClientInterface $httpClient;
     private string $accountSid;
     private string $authToken;
     private string $fromPhoneNumber;
     private LoggerInterface $logger;
 
     public function __construct(HttpClientInterface $httpClient, string $accountSid, string $authToken, string $fromPhoneNumber, LoggerInterface $logger)
     {
         $this->httpClient = $httpClient;
         $this->accountSid = $accountSid;
         $this->authToken = $authToken;
         $this->fromPhoneNumber = $fromPhoneNumber;
         $this->logger = $logger;
     }
 
     public function sendSms(string $to, string $message): string
     {
         $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
 
         try {
             $response = $this->httpClient->request('POST', $url, [
                 'auth_basic' => [$this->accountSid, $this->authToken],
                 'body' => [
                     'To' => $to,
                     'From' => $this->fromPhoneNumber,
                     'Body' => $message,
                 ],
             ]);
 
             // Log the response status code and body
             $this->logger->info('Twilio Response Status: ' . $response->getStatusCode());
             $this->logger->info('Twilio Response Body: ' . $response->getContent(false));
 
             if ($response->getStatusCode() === 201) {
                 return "SMS Sent successfully!";
             } else {
                 $errorContent = $response->getContent(false);
                 $this->logger->error("Failed to send SMS: " . $errorContent);
                 return "Failed to send SMS: " . $errorContent;
             }
         } catch (\Exception $e) {
             $this->logger->error('Error while sending SMS: ' . $e->getMessage());
             return "Error occurred: " . $e->getMessage();
         }
     }
 }
 