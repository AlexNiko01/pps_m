<?php

namespace console\controllers;

use backend\models\Node;
use backend\models\ProjectStatus;
use common\models\Transaction;
use pps\payment\Payment;
use yii\db\Query;
use yii\console\Controller;
use yii\web\ForbiddenHttpException;


class NotificationController extends Controller
{
    const TRANSACTION_TRACKING_INTERVAL = 60;
    const TESTING_MERCHANT_ID = 5;
    const PPS_URL = 'http://master.backend.paygate.xim.hattiko.pw';
    const SUCCESSFUL_SERVES_CODE = 200;

    /**
     * Action for checking failed transaction and sending notification
     */
    public function actionTransaction(): void
    {
        $successfullyPsStatuses =  [
            Payment::STATUS_CREATED,
            Payment::STATUS_PENDING,
            Payment::STATUS_SUCCESS
        ];
        $transactionsSample = Transaction::find()
            ->filterWhere(['not in', 'status', $successfullyPsStatuses])
            ->andFilterWhere(['>', 'updated_at', time() - self::TRANSACTION_TRACKING_INTERVAL])
            ->andFilterWhere(['!=', 'brand_id', self::TESTING_MERCHANT_ID])
            ->select(['updated_at', 'id', 'merchant_transaction_id', 'status', 'currency', 'payment_system_id', 'brand_id'])
            ->all();

        foreach ($transactionsSample as $item) {
            \Yii::$app->sender->send([
                'Failed transaction id: ' . $item->id . ';',
                'Merchant transaction id: ' . $item->merchant_transaction_id . ';',
                'Brand id: ' . $item->brand_id . ';',
                'Time: ' . date('m-d-Y h:i:s', $item->updated_at) . ';',
                'Currency: ' . $item->currency . ';',
                'Status: ' . \pps\payment\Payment::getStatusDescription($item->status) . ';',
                'Payment system: ' . $item->paymentSystem->name . '.'
            ]);
        };
    }

    /**
     * Determine payment systems efficiency
     */
    public function actionPaymentSystem(): void
    {
        $inquirer = \Yii::$app->inquirer;
        $sender = \Yii::$app->sender;
        $notRespondedPaymentSystems = $inquirer->getNotRespondedPaymentSystems();
        if ($notRespondedPaymentSystems) {
            foreach ($notRespondedPaymentSystems as $ps) {
                $message = 'Unresponsive payment system ' . $ps['name'] . '.';
                if ($ps->active === 2) {
                    $message = 'Not enough data for determine payment system ' . $ps['name'] . ' efficiency.';
                }
                $sender->send([
                    $message
                ]);
            }
        }
    }

    /**
     * Check whether pps works
     */
    public function actionPpsCheck()
    {
        $sender = \Yii::$app->sender;
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', self::PPS_URL);
        if ($res->getStatusCode() != self::SUCCESSFUL_SERVES_CODE) {
            $message = 'Pps does`not work';
            $sender->send([
                $message
            ]);
        };
    }

    /**
     * Check whether pps projects works
     */
    public function actionCheckProjects()
    {
        $sender = \Yii::$app->sender;
        $client = new \GuzzleHttp\Client();
        $projectsStatuses = ProjectStatus::find()->indexBy('node_id')->all();
        $query = new Query;
        $query->select([
            'node.name',
            'node.domain',
            'node.id AS project_id'
        ])
            ->from('node')
            ->leftJoin('merchant',
                'merchant.node_id = node.id'
            )
            ->where(['active' => 1])
            ->andWhere(['not', ['merchant.node_id' => null]]);
        $command = $query->createCommand(\Yii::$app->db2);
        $nodesSample = $command->queryAll();

        foreach ($nodesSample as $project) {
            if (!$project['project_id']) {
                continue;
            }
            $id = $project['project_id'];
            if (array_key_exists($id, $projectsStatuses)) {
                $projectStatus = $projectsStatuses[$id];
                unset($projectsStatuses[$id]);
            } else {
                $projectStatus = new ProjectStatus();
            }
            $projectStatus->name = $project['name'] ?? '';
            $projectStatus->domain = $project['domain'] ?? '';
            $projectStatus->node_id = $project['project_id'] ?? '';
            try {
                $res = $client->request('GET', $projectStatus->domain);
                if ($res->getStatusCode() === self::SUCCESSFUL_SERVES_CODE) {
                    $projectStatus->active = 1;
                }

            } catch (\Exception $e) {
                $projectStatus->active = 0;
                $message = 'Project ' . $projectStatus->name . ' does not work';
                $sender->send([
                    $message
                ]);
            }
           $projectStatus->save();
        }
        if (!empty($projectsStatuses)) {
            foreach ($projectsStatuses as $projectStatus) {
                $projectStatus->deleted = 1;
                $projectStatus->save();
            }
        }
    }
}