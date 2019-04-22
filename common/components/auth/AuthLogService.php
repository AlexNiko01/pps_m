<?php

namespace common\components\auth;


use common\components\exception\ClientIsBlocked;
use common\components\helpers\Hash;
use common\models\AuthLog;
use yii\base\Component;
use yii\db\StaleObjectException;
use yii\helpers\Url;

class AuthLogService extends Component
{
    const BLOCKING_DENOMINATOR_TIME_VAL = 300;

    /**
     * Adding a record to the database about the attempt to authorize a user
     */
    public function addAuthLog(): void
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
            $authLog->attempts = $attempts;
            if ($attempts >= 2) {
                $authLog->block = 1;
                $unblockingTime = time() + self::BLOCKING_DENOMINATOR_TIME_VAL * ($authLog->blocking_quantity ?? 1);
                $authLog->unblocking_time = $unblockingTime;
                if ($authLog->blocking_quantity === null) {
                    $authLog->blocking_quantity = 1;
                } else {
                    $authLog->blocking_quantity += 1;
                }
                $authLog->attempts = null;
            }

            $authLog->ip = $currentIp;
            $authLog->user_agent = $currentHash;
            $authLog->save();
            $transaction->commit();

        } catch (\Exception $e) {
            $transaction->rollBack();
        }
    }


    /**
     * @return StaleObjectException
     * Deletes the record of the blocked user in case of successful authorization
     */
    public function removeAuthLog(): StaleObjectException
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


    public function checkUserAccessibility()
    {
        $currentIp = \Yii::$app->request->getUserIP();
        $currentUserAgent = \Yii::$app->request->getUserAgent();
        $currentHash = Hash::sha1($currentUserAgent);
        $authLog = AuthLog::find()->where(['ip' => $currentIp, 'user_agent' => $currentHash])->one();
        $currentTime = time();
        if ($authLog && $authLog->block === 1 && $currentTime < $authLog->unblocking_time) {
            throw new ClientIsBlocked();
        }
    }
}