<?php
namespace app\controllers;

use app\models\User;
use base\Application;
use base\Controller;
use base\helpers\HtmlPurifier;

class UserController extends Controller
{
    public function actionLogin()
    {
        if(!Application::$app->getUser()->getIsGuest()){
            Application::$app->getSession()->setFlash('login-warning', 'You must be unauthorized');
            return $this->redirect('site/index');
        }
        if(isset($_POST) && !empty($_POST) && isset($_POST['email']) && isset($_POST['password'])){
            $identity = User::findIdentityByEmailAndPassword($_POST['email'], $_POST['password']);
            if ($identity){
                Application::$app->getUser()->login($identity,0);
                Application::$app->getSession()->setFlash('login-success', 'Logged in successfully.');
                return $this->redirect('site/index');
            } else {
                Application::$app->getSession()->setFlash('login-danger', 'Wrong email or password.');
            }
        }
        return $this->render('user/login');
    }

    public function actionLogout()
    {
        if(Application::$app->getUser()->getIsGuest()){
            Application::$app->getSession()->setFlash('logout-warning', 'You must be authorized');
            return $this->redirect('site/index');
        }
        if (Application::$app->getUser()->logout()){
            Application::$app->getSession()->setFlash('logout-success', 'Logged out successfully.');
        }else{
            Application::$app->getSession()->setFlash('logout-danger', 'Can not log out.');
        }
        return $this->redirect('site/index');
    }

    public function actionRegister()
    {
        if(!Application::$app->getUser()->getIsGuest()){
            Application::$app->getSession()->setFlash('register-warning', 'You must be unauthorized');
            return $this->redirect('site/index');
        }
        if (!empty($_POST) && (
            !isset($_POST['email']) || empty($_POST['email']) ||
            !isset($_POST['password']) || empty($_POST['password']) ||
            !isset($_POST['repeat_password']) ||  empty($_POST['repeat_password'])))
        {
            Application::$app->getSession()->setFlash('register-danger', 'Please fill all fields.');
        } elseif(!empty($_POST) && ($_POST['password'] !== $_POST['repeat_password'])){
            Application::$app->getSession()->setFlash('register-danger', 'Passwords not match.');
        }elseif(!empty($_POST)) {
            //check email
            $model = User::findIdentityByEmail($_POST['email']);
            if(!empty($model)){
                Application::$app->getSession()->setFlash('register-danger', 'User with email '.HtmlPurifier::process($_POST['email']).' already registered.');
                return $this->redirect('user/register');
            }
            $model = new User();
            $model->load($_POST);
            $model->insert();
            Application::$app->getSession()->setFlash('register-success', 'User successfully created.');
            return $this->redirect('user/login');
        }
        return $this->render('user/register');
    }
}