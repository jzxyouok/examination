<?php
/**
 * Created by PhpStorm.
 * Date: 2016/10/14
 * Time: 12:00
 */

namespace app\controllers;

use app\models\CarType;
use jinxing\admin\helpers\Helper;
use jinxing\admin\traits\JsonTrait;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Json;
use yii\helpers\Url;
use app\models\Question;
use app\models\Subject;
use app\models\UserCollect;

/**
 * Class UserController
 * @package frontend\controllers
 */
class UserController extends Controller
{
    use JsonTrait;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@']
                    ]
                ]
            ]
        ];
    }

    /**
     * actionCollect 用户收藏的问题
     * @return string|\yii\web\Response
     */
    public function actionCollect()
    {
        // 查询科目
        $subject = Subject::findOne(Yii::$app->request->get('subject', 1));
        if ($subject) {
            $collect = UserCollect::findOne([
                'user_id'    => Yii::$app->user->id,
                'subject_id' => $subject->id,
            ]);

            // 有收藏
            if ($collect && $collect->qids) {
                // 全部题目
                $allTotal = Question::find()->where([
                    'status'     => Question::STATUS_KEY,
                    'subject_id' => $subject->id
                ])->count(); // 全部题库

                Yii::$app->view->params['breadcrumbs'] = [
                    [
                        'label' => $subject->name,
                        'url'   => Url::toRoute(['/', 'subject' => $subject->id]),
                    ],
                    [
                        'label' => '我的收藏',
                        'url'   => Url::toRoute(['user/collect', 'subject' => $subject->id])
                    ],
                    '顺序练习',
                ];

                // 开始查询
                $question = Question::findOne($collect->qids[0]); // 查询一条数据
                if ($question) {
                    return $this->render('/question/index', [
                        'cars'       => CarType::findOne($subject->car_id),
                        'subject'    => $subject,
                        'allTotal'   => (int)$allTotal,
                        'total'      => count($collect->qids),
                        'hasCollect' => UserCollect::hasCollect($question->id, $subject->id),
                        'allIds'     => Json::encode($collect->qids),
                        'question'   => $question,
                        'answer'     => Json::decode($question->answers),
                        'style'      => 'sequence',
                    ]);
                }
            }
        }

        // 没有数据直接返回
        return $this->redirect(['/', 'subject' => 1]);
    }

    /**
     * actionCreateCollect() 添加用户收藏信息
     * @return array 返回json字符串
     */
    public function actionCreateCollect()
    {
        $request    = Yii::$app->request;
        $intQid     = (int)$request->post('qid');
        $strType    = $request->post('type');
        $intSubject = (int)$request->post('subject', 1);
        if (empty($intQid) || empty($strType) || !in_array($strType, ['create', 'remove'])) {
            return $this->error();
        }

        // 查询对象
        if (!$model = UserCollect::findOne([
            'user_id'    => Yii::$app->user->id,
            'subject_id' => $intSubject
        ])) {
            $model             = new UserCollect();
            $model->user_id    = Yii::$app->user->id;
            $model->subject_id = $intSubject;
            $model->qids       = [];
        }

        $array = $model->qids;
        if ($strType == 'create') {
            // 获取之前的收藏信息
            if ($array && in_array($intQid, $array)) {
                return $this->error(222, '没有数据');
            }

            array_push($array, $intQid);
            $array = array_unique($array);
        } else {
            // 删除收藏
            if (!in_array($intQid, $array)) {
                return $this->error(224, '没有数据');
            }

            $intKey = array_search($intQid, $array);
            if ($intKey !== false) {
                unset($array[$intKey]);
            }
            sort($array);
        }

        $model->qids = Json::encode($array);
        if ($model->save()) {
            return $this->success($model);
        }

        return $this->error(2, Helper::arrayToString($model->getErrors()));
    }
}