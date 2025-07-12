<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\services\TelegramService;
use app\services\OpenAIService;
use app\services\TelegramBotHandlerService;

class TelegramController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionWebhook()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $input = Yii::$app->request->getRawBody();
        $update = json_decode($input, true);

        $botToken = Yii::$app->params['telegramBotToken'];
        $openAiKey = Yii::$app->params['openAiApiKey'];
        if (!$botToken || !$openAiKey) {
            return ['ok' => false, 'error' => 'Bot token or OpenAI key not set'];
        }

        $handler = new TelegramBotHandlerService($botToken, $openAiKey);
        return $handler->handleWebhook($update);
    }
} 