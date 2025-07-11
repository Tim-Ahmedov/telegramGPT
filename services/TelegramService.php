<?php

namespace app\services;

use GuzzleHttp\Client;

class TelegramService
{
    private $botToken;
    private $apiUrl;
    private $client;

    public function __construct($botToken)
    {
        $this->botToken = $botToken;
        $this->apiUrl = "https://api.telegram.org/bot{$botToken}/";
        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout'  => 5.0,
        ]);
    }

    public function getMe()
    {
        $response = $this->client->get('getMe');
        return json_decode($response->getBody(), true);
    }

    public function sendMessage($chatId, $text, $replyToMessageId = null)
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        if ($replyToMessageId) {
            $params['reply_to_message_id'] = $replyToMessageId;
        }
        $response = $this->client->post('sendMessage', [
            'form_params' => $params,
        ]);
        return json_decode($response->getBody(), true);
    }
} 