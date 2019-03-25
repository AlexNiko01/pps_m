<?php

namespace console\controllers;

use backend\models\ProjectStatus;
use backend\models\Settings;
use common\components\inquirer\PaymentSystemInquirer;
use common\components\sender\MessageSender;
use common\models\Transaction;
use pps\payment\Payment;
use common\components\exception\SettingsException;
use yii\db\Query;
use yii\console\Controller;


class NotificationController extends Controller
{
    const TRANSACTION_TRACKING_INTERVAL = 60;
    const SUCCESSFUL_SERVES_CODE = 200;


    /**
     * @return null
     * Action for checking failed transaction and sending notification
     */
    public function actionTransaction()
    {
        $successfullyPsStatuses = [
            Payment::STATUS_CREATED,
            Payment::STATUS_PENDING,
            Payment::STATUS_SUCCESS
        ];

        try {
            $testingMerchantId = Settings::getValue('testing_merchant_id');
        } catch (SettingsException  $e) {
            \Yii::$app->sender->send( $e->getMessage());
            \Yii::info($e->getMessage(), 'settings');
            return null;
        }
        $transactionsSample = Transaction::find()
            ->filterWhere(['not in', 'status', $successfullyPsStatuses])
            ->andFilterWhere(['>', 'updated_at', time() - self::TRANSACTION_TRACKING_INTERVAL])
            ->andFilterWhere(['!=', 'brand_id', $testingMerchantId])
            ->select(['updated_at', 'id', 'merchant_transaction_id', 'status', 'currency', 'payment_system_id', 'brand_id'])
            ->all();

        if (!$transactionsSample) {
            return null;
        }
        $message = '';
        foreach ($transactionsSample as $item) {
            $message .=
                'Failed transaction id: ' . $item->id . ';' . PHP_EOL .
                'Merchant transaction id: ' . $item->merchant_transaction_id . ';' . PHP_EOL .
                'Brand id: ' . $item->brand_id . ';' . PHP_EOL .
                'Time: ' . date('m-d-Y h:i:s', $item->updated_at) . ';' . PHP_EOL .
                'Currency: ' . $item->currency . ';' . PHP_EOL .
                'Status: ' . \pps\payment\Payment::getStatusDescription($item->status) . ';' . PHP_EOL .
                'Payment system: ' . $item->paymentSystem->name . '.' . PHP_EOL . PHP_EOL;
        };

        \Yii::$app->sender->send($message);
    }

    /**
     * Determine payment systems efficiency
     */
    public function actionPaymentSystem(): void
    {
        /**
         * @var PaymentSystemInquirer $inquirer
         */


        $inquirer = \Yii::$app->inquirer;
        /**
         * @var MessageSender $sender
         */
        $sender = \Yii::$app->sender;
        $notRespondedPaymentSystems = $inquirer->getNotRespondedPaymentSystems();
        if ($notRespondedPaymentSystems) {
            $message = '';
            foreach ($notRespondedPaymentSystems as $ps) {
                $messageItem = 'Unresponsive payment system ' . $ps['name'] . '. ';
                if ($ps->active === 2) {
                    $messageItem = 'Not enough data for determine payment system ' . $ps['name'] . ' efficiency. ';
                }
                $message .= $messageItem . PHP_EOL;
            }
            $sender->send($message);
        }
    }

    /**
     * @return null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * Check whether pps works
     */

    public function actionPpsCheck()
    {
        $sender = \Yii::$app->sender;
        $client = new \GuzzleHttp\Client();
        try {
            $ppsUrl = Settings::getValue('pps_url');
        } catch (SettingsException  $e) {
            \Yii::info($e->getMessage(), 'settings');
            \Yii::$app->sender->send($e->getMessage());
            return null;
        }
        $res = $client->request('GET', $ppsUrl);
        if ($res->getStatusCode() != self::SUCCESSFUL_SERVES_CODE) {
            $message = 'Pps does`not work';
            $sender->send($message);
        };
    }

    /* @return null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\db\Exception
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
        if (!$nodesSample) {
            return null;
        }
        $message = '';
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
                $message .= 'Project ' . $projectStatus->name . ' does not work' . PHP_EOL;
            }
            $projectStatus->save();
        }
        if ($message) {
            $message = strip_tags($message);
            $sender->send($message);
        }

        if (!empty($projectsStatuses)) {
            foreach ($projectsStatuses as $projectStatus) {
                $projectStatus->deleted = 1;
                $projectStatus->save();
            }
        }
    }

}