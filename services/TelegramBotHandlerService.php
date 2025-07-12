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
        $myBotId = 7512768996; // ваш bot ID
        foreach ($entities as $entity) {
            if ($entity['type'] === 'mention') {
                $mentionText = mb_substr($message['text'], $entity['offset'], $entity['length']);
                if ($botUsername && $mentionText === '@' . $botUsername) {
                    $isMentioned = true;
                    break;
                }
            }
        }
        // Новая логика: если это reply на сообщение бота, тоже отвечаем
        $isReplyToMe = false;
        if (isset($message['reply_to_message']['from']['id'])) {
            if ($message['reply_to_message']['from']['id'] == $myBotId) {
                $isReplyToMe = true;
            }
        }
        if (!$isMentioned && !$isReplyToMe) {
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