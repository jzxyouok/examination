<?php
namespace app\controllers;

use yii;
use app\models\CarType;

/**
 * Site controller
 */
class Controller extends yii\web\Controller
{
    /**
     * @param $action
     *
     * @return bool
     * @throws yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if (!Yii::$app->request->isAjax) {
            Yii::$app->view->params['carTypes'] = CarType::find()->where(['status' => 1])->orderBy(['sort' => SORT_ASC])->all();
        }

        return parent::beforeAction($action);
    }
}
