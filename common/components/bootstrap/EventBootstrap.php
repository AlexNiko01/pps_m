<?php

namespace common\components\bootstrap;

use common\components\exception\ClientIsBlocked;
use common\models\AuthLog;
use common\components\helpers\Hash;
use webvimark\modules\UserManagement\components\UserConfig;
use webvimark\modules\UserManagement\models\forms\LoginForm;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\web\User;

class EventBootstrap implements BootstrapInterface
{
    const BLOCKING_DENOMINATOR_TIME_VAL = 300;

    /**
     * @param \yii\base\Application $app
     */
    public function bootstrap($app): void
    {

        $this->checkUserAccessibility();
        Event::on(LoginForm::class, Model::EVENT_BEFORE_VALIDATE, function ($event) {
            $this->addAuthLog();
        });
        Event::on(UserConfig::class, User::EVENT_AFTER_LOGIN, function ($event) {
            $this->removeAuthLog();
        });
    }


    private function addAuthLog(): void
    {
        $db = \Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $currentIp = \Yii::$app->request->getUserIP();
            $currentUserAgent = \Yii::$app->request->getUserAgent();
            $currentHash = Hash::sha1($currentUserAgent);
            $authLog = AuthLog::find()->where(['ip' => $currentIp, 'user_agent' => $currentHash])->one();
            if (!$authLog) {
                $authLog = new AuthLog();
            }
            $attempts = $authLog->attempts;
            if ($attempts === null) {
                $attempts = 1;
            } else {
                $attempts++;
            }
            if ($attempts >= 2) {
                $authLog->block = 1;
                $unblockingTime = time() + self::BLOCKING_DENOMINATOR_TIME_VAL * ($authLog->blocking_quantity ?? 1);
                $authLog->unblocking_time = $unblockingTime;
                if ($authLog->blocking_quantity === null) {
                    $authLog->blocking_quantity = 1;
                } else {
                    $authLog->blocking_quantity += 1;
                }

            }
            $authLog->ip = $currentIp;
            $authLog->user_agent = $currentHash;
            $authLog->attempts = $attempts;
            $authLog->save();
            $transaction->commit();

        } catch (\Exception $e) {
            $transaction->rollBack();

        }
    }

    private function removeAuthLog()
    {
        $currentIp = \Yii::$app->request->getUserIP();
        $currentUserAgent = \Yii::$app->request->getUserAgent();
        $currentHash = Hash::sha1($currentUserAgent);

        $authLog = AuthLog::find()->where(['ip' => $currentIp, 'user_agent' => $currentHash])->one();
        if ($authLog) {
            try {
                $authLog->delete();
            } catch (StaleObjectException $e) {
            } catch (\Throwable $e) {
            }
        }

    }

    private function checkUserAccessibility() {
        //TODO: throw new ClientIsBlocked();   if user is blocked
    }
}