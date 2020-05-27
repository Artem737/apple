<?php
namespace frontend\controllers;

use common\exceptions\AppleException;
use common\models\Apple;
use frontend\models\ResendVerificationEmailForm;
use frontend\models\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup', 'apples', 'eat', 'fall', 'generate'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['apples'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['eat'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['fall'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['generate'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
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
     * @return mixed
     */
    public function actionIndex()
    {
        $this->redirect('/site/apples');
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($user = $model->verifyEmail()) {
            if (Yii::$app->user->login($user)) {
                Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
                return $this->goHome();
            }
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model
        ]);
    }

    /**
     * @return string
     */
    public function actionApples()
    {
        return $this->render('apples', [
            'apples' => Apple::find()->all()
        ]);
    }

    public function actionGenerate()
    {
        try {

            $count = rand(5, 20);
            $colors = [Apple::COLOR_GREEN, Apple::COLOR_RED, Apple::COLOR_YELLOW];

            Apple::deleteAll();

            for ($i = 0; $i < $count; $i++) {
                $apple = new Apple;
                $apple->color = $colors[array_rand($colors)];
                $apple->created_at = date('Y-m-d H:i:s');
                if (!$apple->save()) {
                    throw new AppleException('Не удалсоь создать яблоки');
                }
            }

        } catch (AppleException $exception) {

            Yii::$app->session->setFlash('appleError', $exception->getMessage());

        }

        $this->redirect('/site/apples');
    }

    /**
     * @param int $appleId
     * @param float $part
     * @throws \yii\db\StaleObjectException
     * @throws \Throwable
     */
    public function actionEat(int $appleId, float $part)
    {
        try {

            $apple = Apple::findOne($appleId);

            if (!$apple) {
                throw new AppleException('Не удалось найти яблоко с номером ' . $appleId);
            }

            $apple->eat($part);

        } catch (AppleException $exception) {

            Yii::$app->session->setFlash('appleError', $exception->getMessage());

        }

        $this->redirect('/site/apples');
    }

    /**
     * @param int $appleId
     */
    public function actionFall(int $appleId)
    {
        try {
            $apple = Apple::findOne($appleId);

            if (!$apple) {
                throw new AppleException('Не удалось найти яблоко с номером ' . $appleId);
            }

            $apple->fallToGround();

        } catch (AppleException $exception) {

            Yii::$app->session->setFlash('appleError', $exception->getMessage());

        }

        $this->redirect('/site/apples');
    }
}
