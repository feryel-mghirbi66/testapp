<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceService
{
    private $httpClient;
    private $apiKey;
    private $history = [];

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function chatWithAI(string $message): string
    {
        $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/facebook/blenderbot-400M-distill', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'inputs' => $message,
                'parameters' => [
                    'temperature' => 0.7,
                    'top_p' => 0.9
                ]
            ]
        ]);
    
        $data = $response->toArray();
    
        // Debug : Afficher la réponse complète
       
        return $data[0]['generated_text'] ?? 'Erreur : aucune réponse';
    }
    

}