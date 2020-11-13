<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\base\InvalidArgumentException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use app\models\forms\LoginForm;
use app\models\forms\SignupForm;
use app\models\forms\ResendVerificationEmailForm;
use app\models\forms\VerifyEmailForm;
use app\models\forms\PasswordResetRequestForm;
use app\models\forms\ResetPasswordForm;

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
                'only' => ['logout', 'register', 'verify-email', 'resend-verification-email'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['register'],
                        'allow' => false,
                        'roles' => ['@'],
                        'denyCallback' => function() {
                          return  \Yii::$app->getResponse()->redirect(['/team/default/index']);
                        }
                    ],
                    [
                       'actions' => ['index'],
                       'allow' => false,
                       'roles' => ['@'],
                       'matchCallback' => function ($rule, $action) {
                         if(Yii::$app->sys->team_required===false)
                         {
                            return false;
                         }

                         if(Yii::$app->user->identity->teamPlayer===NULL)
                         {
                           Yii::$app->session->setFlash('warning', 'You need to join a team before being able to access this area.');
                           return true;
                         }
                         if(Yii::$app->user->identity->teamPlayer->approved!==1)
                         {
                           Yii::$app->session->setFlash('warning', 'You need to have your team membership approved before being able to access this area.');
                           return true;
                         }
                         return false;
                       },
                       'denyCallback' => function() {
                         return  \Yii::$app->getResponse()->redirect(['/team/default/index']);
                       }
                    ],
                    [
                        'actions' => ['register', 'verify-email', 'resend-verification-email'],
                        'allow' => false,
                        'roles' => ['?'],
                        'matchCallback' => function ($rule, $action) {
                          return Yii::$app->sys->registrations_start!==false && (time()<=Yii::$app->sys->registrations_start || time()>=Yii::$app->sys->registrations_end);
                        },
                    ],
                    [
                        'actions' => ['index','register','verify-email', 'resend-verification-email'],
                        'allow' => true,
                        'roles' => ['?'],
                        'matchCallback' => function ($rule, $action) {
                          return !Yii::$app->DisabledRoute->disabled($action);
                        },
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
                'class' => 'app\widgets\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'offset' => 2,
                'minLength' => 7,
                'maxLength' => 7,
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
      if(!Yii::$app->user->isGuest && Yii::$app->sys->default_homepage!==false && Yii::$app->sys->default_homepage!=="")
          $this->redirect([Yii::$app->sys->default_homepage]);
      return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if(!Yii::$app->user->isGuest)
        {
            return $this->goHome();
        }

        $model=new LoginForm();
        if($model->load(Yii::$app->request->post()) && $model->login())
        {
            return $this->goBack();
        }

        $model->password='';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionRegister()
    {

        $model=new SignupForm();
        $transaction=Yii::$app->db->beginTransaction();
        try
        {
          if($model->load(Yii::$app->request->post()) && $model->signup())
          {
              $transaction->commit();
              if(Yii::$app->sys->require_activation===true)
                Yii::$app->session->setFlash('success', 'Thank you for registering. Please check your inbox for the verification email. <small>Make sure you also check the spam or junk folders.</small>');
              else {
                Yii::$app->session->setFlash('success', 'Thank you for registering. Your account is activated feel free to login.');
              }
              return $this->goHome();
          }
        }
        catch(\Exception $e)
        {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Registration failed.');
            throw $e;
        }
        catch(\Throwable $e)
        {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Registration failed.');
            throw $e;
        }


        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model=new PasswordResetRequestForm();
        if($model->load(Yii::$app->request->post()) && $model->validate())
        {
            if($model->sendEmail())
            {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions. Keep in mind that the token will expire after 24 hours.');

                return $this->goHome();
            }
            else
            {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset the password for the provided email address.');
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
        try
        {
            $model=new ResetPasswordForm($token);
        }
        catch(InvalidArgumentException $e)
        {
            throw new BadRequestHttpException($e->getMessage());
        }

        if($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword())
        {
            if(Yii::$app->user->login($model->player))
            {
              Yii::$app->session->setFlash('success', 'New password saved.');
            }
            else
            {
              Yii::$app->session->setFlash('notice', 'New password saved but failed to signin.');
            }

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
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionVerifyEmail($token)
    {
        try
        {
            $model=new VerifyEmailForm($token);
        }
        catch(InvalidArgumentException $e)
        {
            throw new BadRequestHttpException($e->getMessage());
        }
        $post=Yii::$app->request->post('VerifyEmailForm');
        $value=ArrayHelper::getValue($post, 'token');

        if($value !== $token)
        {
            return $this->render('verify-email', ['model'=>$model, 'token'=>$token]);
        }
        $transaction=Yii::$app->db->beginTransaction();
        try
        {
          if($user=$model->verifyEmail())
          {
              if(Yii::$app->user->login($user))
              {
                  $transaction->commit();
                  Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
                  return $this->redirect(['/profile/me']);
              }
          }
        }
        catch(\Exception $e)
        {
          $transaction->rollBack();
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
        $model=new ResendVerificationEmailForm();
        if($model->load(Yii::$app->request->post()) && $model->validate())
        {
            if($model->sendEmail())
            {
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
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionChangelog()
    {
      $changelog=file_get_contents('../Changelog.md');
      $todo=file_get_contents('../TODO.md');
      return $this->render('changelog', [
        'changelog'=>$changelog,
        'todo'=>$todo
      ]);
    }
}
