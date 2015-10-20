<?php
namespace app\controllers;

use base\Controller;
use base\Application;

class SiteController extends Controller
{
    public function actionIndex()
    {
        //Application::$app->getSession()->setFlash('logout-warning', 'You must be authorized');
        return $this->render('site/index',['testParam'=>'test']);
    }
}