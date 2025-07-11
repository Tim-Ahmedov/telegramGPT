<?php

namespace app\services;

use Yii;

class TelegramBotHandlerService
{
    private $telegram;
    private $openai;

    public function __construct($botToken, $openAiKey)
    {
        $this->telegram = new TelegramService($botToken);
        $this->openai = new OpenAIService($openAiKey);
    }

    public function handleWebhook($update)
    {
        $message = $update['message'] ?? null;
        if (!$message) {
            return ['ok' => true];
        }
        $entities = $message['entities'] ?? [];
        $isMentioned = false;
        $botInfo = $this->telegram->getMe();
        $botUsername = $botInfo['result']['username'] ?? null;
        foreach ($entities as $entity) {
            if ($entity['type'] === 'mention') {
                $mentionText = mb_substr($message['text'], $entity['offset'], $entity['length']);
                if ($botUsername && $mentionText === '@' . $botUsername) {
                    $isMentioned = true;
                    break;
                }
            }
        }
        if (!$isMentioned) {
            return ['ok' => true];
        }
        $userText = $message['text'];
        // Если это reply, добавляем текст исходного сообщения в prompt
        if (isset($message['reply_to_message'])) {
            $replyTo = $message['reply_to_message'];
            $originalText = $replyTo['text'] ?? '';
            if ($originalText) {
                $userText = "Ответ на: " . $originalText . "\n" . $userText;
            }
        }
        $reply = $this->openai->ask($userText);
        $chatId = $message['chat']['id'];
        $this->telegram->sendMessage($chatId, $reply, $message['message_id']);
        return ['ok' => true];
    }
} 