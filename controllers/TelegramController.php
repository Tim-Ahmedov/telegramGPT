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
        error_log('TOKEN=' . Yii::$app->params['telegramBotToken']);
        error_log('OPENAI=' . Yii::$app->params['openAiApiKey']);
        Yii::$app->response->format = Response::FORMAT_JSON;
        $input = Yii::$app->request->getRawBody();
        $update = json_decode($input, true);

        $botToken = Yii::$app->params['telegramBotToken'];
        $openAiKey = Yii::$app->params['openAiApiKey'];
        error_log('DEBUG: params.telegramBotToken=' . var_export($botToken, true));
        error_log('DEBUG: params.openAiApiKey=' . var_export($openAiKey, true));
        error_log('DEBUG: getenv(TELEGRAM_BOT_TOKEN)=' . var_export(getenv('TELEGRAM_BOT_TOKEN'), true));
        error_log('DEBUG: getenv(OPENAI_API_KEY)=' . var_export(getenv('OPENAI_API_KEY'), true));
        if (!$botToken || !$openAiKey) {
            return ['ok' => false, 'error' => 'Bot token or OpenAI key not set'];
        }

        $handler = new TelegramBotHandlerService($botToken, $openAiKey);
        return $handler->handleWebhook($update);
    }
} 