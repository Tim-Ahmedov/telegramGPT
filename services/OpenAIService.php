<?php

namespace app\services;

use OpenAI;

class OpenAIService
{
    private $client;

    public function __construct($apiKey)
    {
        $this->client = OpenAI::client($apiKey);
    }

    public function ask($userText)
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $userText],
            ],
        ]);
        return $response['choices'][0]['message']['content'] ?? 'Ошибка генерации ответа.';
    }
} 