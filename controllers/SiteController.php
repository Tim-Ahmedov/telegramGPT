<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\services\TelegramService;
use app\services\OpenAIService;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Telegram webhook endpoint
     */
    public function actionTelegramWebhook()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $input = Yii::$app->request->getRawBody();
        $update = json_decode($input, true);

        $botToken = Yii::$app->params['telegramBotToken'];
        $openAiKey = Yii::$app->params['openAiApiKey'];
        if (!$botToken || !$openAiKey) {
            return ['ok' => false, 'error' => 'Bot token or OpenAI key not set'];
        }

        $telegram = new TelegramService($botToken);
        $openai = new OpenAIService($openAiKey);

        $message = $update['message'] ?? null;
        if (!$message) {
            return ['ok' => true];
        }
        $entities = $message['entities'] ?? [];
        $isMentioned = false;
        $botInfo = $telegram->getMe();
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
        $reply = $openai->ask($userText);
        $chatId = $message['chat']['id'];
        $telegram->sendMessage($chatId, $reply, $message['message_id']);
        return ['ok' => true];
    }
}
