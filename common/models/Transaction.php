<?php

namespace common\models;

use api\classes\ApiProxy;
use backend\components\Notification;
use backend\components\SocketClient;
use backend\events\{
    AlertEvent, MessageEvent
};
use backend\models\{
    BlockchainConfig, Dispatch, Merchant, Requests
};
use common\classes\CurrencyList;
use console\jobs\{
    NotifyMerchantJob, TransactionTimeOverJob
};
use pps\payment\{
    Payment, ICryptoCurrency
};
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use backend\models\PaymentSystem;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%transaction}}".
 * @property string $id
 * @property integer $brand_id
 * @property integer $payment_system_id
 * @property string $way
 * @property string $currency
 * @property number $amount
 * @property number $write_off
 * @property number $refund
 * @property number $receive
 * @property string $payment_method
 * @property integer $merchant_transaction_id
 * @property number $commission
 * @property integer $buyer_id
 * @property boolean $trn_process_mode
 * @property string $external_id
 * @property string $requisites
 * @property string $status
 * @property string $comment
 * @property string $result_data
 * @property string $query_data
 * @property string $callback_data
 * @property string $urls
 * @property string $commission_payer
 * @property integer $created_at
 * @property integer $updated_at
 */
class Transaction extends ActiveRecord
{
    const TYPE_CRYPTO = 'crypto';

    /**
     * @var
     */
    protected $_brands;

    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return \Yii::$app->db2;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%transaction}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['brand_id', 'payment_system_id', 'way', 'currency', 'amount', 'payment_method', 'merchant_transaction_id', 'buyer_id'], 'required'],
            [['brand_id', 'payment_system_id', 'created_at', 'updated_at', 'status'], 'integer'],
            [['amount', 'write_off', 'refund', 'receive', 'commission'], 'number'],
            [['result_data', 'query_data', 'urls', 'merchant_transaction_id', 'buyer_id'], 'string'],
            [['id'], 'string', 'max' => 36],
            [['id'], 'trim'],
            [['way'], 'string', 'max' => 10],
            [['way'], 'trim'],
            [['currency'], 'string', 'max' => 4],
            [['currency'], 'trim'],
            [['payment_method'], 'string', 'max' => 32],
            [['external_id'], 'string', 'max' => 64],
            [['payment_method', 'external_id'], 'trim'],
            [['requisites', 'comment'], 'string', 'max' => 255],
            [['requisites', 'comment'], 'trim'],
            [['brands'], 'string'],
            [['brand_id', 'merchant_transaction_id'], 'unique', 'targetAttribute' => ['brand_id', 'merchant_transaction_id'], 'message' => 'The combination of Brand ID and Merchant Transaction ID has already been taken.'],
            [['id'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'brand_id' => 'Brand',
            'payment_system_id' => 'Payment system',
            'way' => 'Way',
            'currency' => 'Currency',
            'amount' => 'Amount',
            'write_off' => 'Write off',
            'refund' => 'Refund',
            'receive' => 'Receive',
            'payment_method' => 'Payment method',
            'merchant_transaction_id' => 'Merchant transaction',
            'commission' => 'Commission',
            'buyer_id' => 'Buyer',
            'external_id' => 'External',
            'requisites' => 'Requisites',
            'status' => 'Status',
            'comment' => 'Comment',
            'result_data' => 'Result data',
            'query_data' => 'Query data',
            'urls' => 'Urls',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ];
    }

    /**
     * Relation with Payment system
     * @return \yii\db\ActiveQuery
     */
    public function getPaymentSystem()
    {
        return $this->hasOne(PaymentSystem::className(), ['id' => 'payment_system_id']);
    }

    public function addToRedisQueue()
    {
        $data = self::getNoteStructure($this);

        $paymentSystem = PaymentSystem::find()
            ->select(['code'])
            ->where(['id' => $this->payment_system_id])
            ->asArray()
            ->one();

        //ApiProxy::setMerchant($this->brand_id);
        $PPS = ApiProxy::loadPPS($paymentSystem['code'], $this->payment_system_id, $this->brand_id, $this->currency);

        $sendData = [
            'transaction_id' => $this->id,
            'brand_id' => $this->brand_id,
            'status' => $this->status,
            'data' => [
                'data' => $data
            ],
        ];

        if (($PPS instanceof ICryptoCurrency) && CurrencyList::isCrypto($this->currency)) {
            $sendData['data']['type'] = self::TYPE_CRYPTO;
        }

        Requests::saveData($this->id, 10, json_encode($sendData));
        try {
            Yii::$app->queue->push(new NotifyMerchantJob($sendData));
        } catch (\RedisException $e) {
            Requests::saveData($this->id, 501, 'Error push to Redis queue. Transaction ID = ' . $this->id);
        }
    }

    /**
     * @param Transaction $transaction
     * @param int|null $denomCoeff
     * @return array
     */
    public static function getNoteStructure(Transaction $transaction, int $denomCoeff = null): array
    {
        $coefficient = $denomCoeff ?? Payment::getCoefficient($transaction->currency);

        $data = [
            'id' => $transaction->id,
            'transaction_id' => $transaction->merchant_transaction_id,
            'currency' => $transaction->currency,
            'status' => $transaction->status,
            'way' => $transaction->way,
            'buyer_id' => $transaction->buyer_id,
            'comment' => $transaction->comment,
            'amount' => $transaction->amount * $coefficient,
        ];

        if (!empty($transaction->trn_process_mode)) {
            $data['trn_process_mode'] = $transaction->trn_process_mode;
        }

        if ($transaction->way === Payment::WAY_DEPOSIT) {
            $data['buyer_write_off'] = $transaction->write_off * $coefficient;
            $data['merchant_refund'] = $transaction->refund * $coefficient;
            $data['requisites'] = self::getRequisites($transaction);
        } elseif ($transaction->way === Payment::WAY_WITHDRAW) {
            $data['buyer_receive'] = $transaction->receive * $coefficient;
            $data['merchant_write_off'] = $transaction->write_off * $coefficient;
        }

        return $data;
    }

    /**
     * @param Transaction $transaction
     * @param int|null $denomCoeff
     * @return array
     */
    public static function getWithdrawAnswer(Transaction $transaction, int $denomCoeff = null): array
    {
        $coefficient = $denomCoeff ?? Payment::getCoefficient($transaction->currency);

        return [
            'id' => $transaction->id,
            'transaction_id' => $transaction->merchant_transaction_id,
            'status' => $transaction->status,
            'amount' => $transaction->amount * $coefficient,
            'merchant_write_off' => $transaction->write_off * $coefficient,
            'buyer_receive' => $transaction->receive * $coefficient,
            'currency' => $transaction->currency,
        ];
    }

    /**
     * @param Transaction $transaction
     * @param int|null $denomCoeff
     * @return array
     */
    public static function getDepositAnswer(Transaction $transaction, int $denomCoeff = null): array
    {
        $coefficient = $denomCoeff ?? Payment::getCoefficient($transaction->currency);

        return [
            'id' => $transaction->id,
            'transaction_id' => $transaction->merchant_transaction_id,
            'status' => $transaction->status,
            'amount' => $transaction->amount * $coefficient,
            'buyer_write_off' => $transaction->write_off * $coefficient,
            'merchant_refund' => $transaction->refund * $coefficient,
            'currency' => $transaction->currency,
        ];
    }

    /**
     * @param Transaction $transaction
     * @return array|mixed
     */
    public static function getRequisites(Transaction $transaction)
    {
        $requisites = (!empty($transaction->callback_data)) ? json_decode($transaction->callback_data, true) : [];

        /*foreach ($requisites as $ps => $req) {
            foreach ($req as $key => $value) {
                if (is_integer($value) && $value > 10**8 || is_string($value) && intval($value) > 10**8) {
                    unset($requisites[$ps][$key]);
                }
            }
        }*/

        return $requisites;
    }

    /**
     * Generate new id for new transaction
     * @param int $length
     * @return bool|string
     * @throws \Exception
     */
    private static function _setId($length = 36)
    {
        // unique id gives 13 chars, but you could adjust it to your needs.
        if (function_exists("random_bytes")) :
            $bytes = random_bytes(ceil($length / 2));
        elseif (function_exists("openssl_random_pseudo_bytes")) :
            $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
        else :
            throw new \Exception("no cryptographically secure random function available");
        endif;

        return substr(bin2hex($bytes), 0, $length);
    }

    /**
     * @param string $name
     * @return float|mixed
     */
    public function __get($name)
    {
        if (in_array($name, ['amount', 'refund', 'receive', 'write_off'])) {
            $field = $this->getAttribute($name);
            return $field ? (float)$field : $field;
        }

        return parent::__get($name);
    }

    /**
     * @param $brands
     */
    public function setBrands($brands)
    {
        $this->_brands = $brands;
    }

    /**
     * @return float|mixed
     */
    public function getBrands()
    {
        return $this->_brands;
    }

    /**
     * TODO modify this method using yii2
     * @param $brand_id
     * @param array $request
     * @param array $errors
     * @return array
     */
    public static function getTransactionsInfo($brand_id, array $request, array &$errors = [])
    {
        $ps = PaymentSystem::find()->all();
        $paymentSystems = ArrayHelper::map($ps, 'id', 'code');
        $paymentSystemCodes = ArrayHelper::map($ps, 'code', 'id');
        $statuses = Payment::getStatuses();

        $currencies = CurrencyList::getCurrencies();

        $query = Transaction::find()->where([
            'brand_id' => $brand_id
        ])
            ->orderBy('created_at desc');

        $where = [];

        if (isset($request['limit'])) {
            if ($request['limit'] > 0) {
                $query->limit($request['limit']);
            } else {
                $errors[] = "'limit' should be more then 0";
            }
        }

        if (isset($request['offset'])) {
            if ($request['offset'] > 0) {
                $query->offset($request['offset']);
            } else {
                $errors[] = "'offset' should be more then 0";
            }
        }

        if (isset($request['order']) && $request['order'] === 'asc') {
            $query->orderBy('created_at');
        }

        if (isset($request['status'])) {
            if (in_array($request['status'], array_keys($statuses))) {
                $where['status'] = $request['status'];
            } else {
                $errors[] = "Undefined status {$request['status']}";
            }
        }

        if (isset($request['payment_system'])) {
            if (in_array($request['payment_system'], array_keys($paymentSystemCodes))) {
                $where['peyment_system_id'] = $paymentSystemCodes[$request['payment_system']];
            } else {
                $errors[] = "Undefined payment_system {$request['payment_system']}";
            }
        }

        if (isset($request['way'])) {
            if (in_array($request['way'], ['deposit', 'withdraw'])) {
                $where['way'] = $request['way'];
            } else {
                $errors[] = "The way should be 'deposit' or 'withdraw'";
            }
        }

        if (isset($request['currency'])) {
            if (in_array($request['currency'], array_keys($currencies))) {
                $where['currency'] = $request['currency'];
            } else {
                $errors[] = "Undefined currency {$request['currency']}";
            }
        }

        if (isset($request['buyer_id']) && is_scalar($request['buyer_id'])) {
            $where['buyer_id'] = $request['buyer_id'];
        }

        $query->where($where);

        if (isset($request['created_from']) && is_scalar($request['created_from'])) {
            $query->andWhere(['>=', 'created_at', $request['created_from']]);
        }

        if (isset($request['created_to']) && is_scalar($request['created_to'])) {
            $query->andWhere(['<=', 'created_at', $request['created_to']])->asArray();
        }

        $query->asArray();

        if (isset($request['count']) && $request['count'] == true) {
            return ['count' => $query->count()];
        }

        $transactions = $query->all();

        $items = [];

        foreach ($transactions as $transaction) {

            $item = [
                'id' => $transaction['id'],
                'transaction_id' => $transaction['merchant_transaction_id'],
                'way' => $transaction['way'] ?? 'undefined',
                'payment_system_code' => $paymentSystems[$transaction['payment_system_id']],
                'payment_method' => $transaction['payment_method'],
                'currency' => $transaction['currency'] ?? 'undefined',
                'buyer_id' => $transaction['buyer_id'] ?? 0,
                'urls' => $transaction['urls'] ?? 'undefined',
            ];

            if ($transaction['way'] === 'deposit') {
                $item['amount_client'] = (float)$transaction['amount'];
                $item['amount_merchant'] = (float)$transaction['refund'];
            }

            if ($transaction['way'] === 'withdraw') {
                $item['amount_client'] = (float)$transaction['receive'];
                $item['amount_merchant'] = (float)$transaction['amount'];
            }

            $item['status'] = $transaction['status'];
            $item['status_description'] = $statuses[$transaction['status']] ?? 'undefined';

            $item['requisites'] = json_decode($transaction['requisites'], true);
            $item['created_at'] = $transaction['created_at'];
            $item['updated_at'] = $transaction['updated_at'] ?? 'undefined';

            $items[] = $item;
        }

        return ['transactions' => $items];
    }

    /**
     * Send event to merchant if something went wrong with confirmations
     * @return bool
     */
    private function _checkConfirmations()
    {
        /**
         * @var Notification $notify
         */
        $blockchainConfig = $this->getBlockchainConfig();

        if (!$blockchainConfig) {
            return false;
        }

        $notify = Yii::$app->notify;

        $event = new AlertEvent();
        $event->brand_id = $this->brand_id;

        $allMerchantTrx = self::find()
            ->where([
                'brand_id' => $this->brand_id,
                'status' => '10'
            ]);

        $allUserTrx = self::find()
            ->where([
                'brand_id' => $this->brand_id,
                'status' => Payment::STATUS_UNCONFIRMED,
                'buyer_id' => $this->buyer_id,
            ]);

        $merchantCount = $allMerchantTrx->count();
        $merchantSum = $allMerchantTrx->sum('amount');

        $userCount = $allUserTrx->count();
        $userSum = $allUserTrx->sum('amount');

        //die(var_dump($merchantCount, $merchantSum, $userCount, $userSum));
        $event->message['transaction'] = self::getNoteStructure($this);

        if ($blockchainConfig->boundary_trx_num_merch >= $merchantCount) {
            $event->type = Dispatch::ALERT_BOUNDARY_TRX_NUM_FOR_MERCH;
            $notify->trigger(Notification::EVENT_SEND_ALERT, $event);
        }

        if ($blockchainConfig->boundary_sum_merch >= $merchantSum) {
            $event->type = Dispatch::ALERT_BOUNDARY_SUM_FOR_MERCH;
            $notify->trigger(Notification::EVENT_SEND_ALERT, $event);
        }

        if ($blockchainConfig->boundary_trx_num_user >= $userCount) {
            $event->type = Dispatch::ALERT_BOUNDARY_TRX_NUM_FOR_USER;
            $event->message['buyer_id'] = $this->buyer_id;
            $notify->trigger(Notification::EVENT_SEND_ALERT, $event);
        }

        if ($blockchainConfig->boundary_sum_user >= $userSum) {
            $event->type = Dispatch::ALERT_BOUNDARY_SUM_FOR_USER;
            $event->message['buyer_id'] = $this->buyer_id;
            $notify->trigger(Notification::EVENT_SEND_ALERT, $event);
        }

        return true;
    }

    /**
     * @param null $way
     * @param array $query
     * @return int
     */
    public static function getCountOfFinalTransactions($way = null, $query = []): int
    {
        $count = self::find()
            ->where(['status' => Payment::getFinalStatuses()])
            ->andWhere($query);

        if (!empty($way)) {
            $count->andWhere(['way' => $way]);
        }

        return $count->count();
    }

    /**
     * @param array $query
     * @return int
     */
    public static function getCountOfTransactionForToday($query = []): int
    {
        $dateOfBeginDay = strtotime(date('Y-m-d') . " 00:00:00");

        return self::find()
            ->where(['>', 'created_at', $dateOfBeginDay])
            ->andWhere($query)
            ->count();
    }

    /**
     * @param int $day
     * @param int $interval
     * @param array $query
     * @return array
     */
    public static function getCountOfTxsByMinutes($day = 7, $interval = 15, $query = [])
    {
        $day = abs($day);
        $interval = abs($interval);
        $date = new \DateTime('now');
        $date->modify("-{$day} day");

        $items = self::find()
            ->select(['COUNT(id) as count', "FLOOR(created_at/(60*{$interval})) as timekey", "FROM_UNIXTIME(created_at, '%m.%d %H:%i') as date"])
            ->where(['>', 'FROM_UNIXTIME(created_at)', $date->format('Y-m-d H:i:s')])
            ->andWhere($query)
            ->asArray()
            ->groupBy('timekey')
            ->all();

        return ArrayHelper::map($items, 'date', 'count');
    }

    /**
     * @param array $query
     * @return array
     */
    public static function getCountOfStatuses($query = []): array
    {
        $statusesQuery = self::find()
            ->select(['COUNT(id) as count', 'status'])
            ->groupBy('status')
            ->asArray();

        if (!empty($query)) {
            $statusesQuery->where($query);
        }

        $statuses = Yii::$app->cache->getOrSet(['getCountOfStatuses', $query], function () use ($statusesQuery) {
            return $statusesQuery->all();
        }, 30);
        //$statuses = $statusesQuery->all();

        $countOfStatuses = ArrayHelper::map($statuses, 'status', 'count');

        foreach ($countOfStatuses as $key => $count) {
            unset($countOfStatuses[$key]);
            if ($key === '') {
                $countOfStatuses['Empty'] = $count;
                continue;
            }
            $countOfStatuses[Payment::getStatusDescription($key)] = $count;
        }

        return $countOfStatuses;
    }

    /**
     * @param array $query
     * @param int $limit
     * @return array
     */
    public static function getTransactionsByStatus(array $query = [], int $limit = 20): array
    {
        return self::find()
            ->select(['transaction.id', 'buyer_id', 'way', 'transaction.status', 'payment_system_id', 'payment_system.name as payment_system', 'transaction.created_at'])
            ->joinWith('paymentSystem')
            ->where($query)
            ->asArray()
            ->limit($limit)
            ->orderBy('way desc, created_at desc')
            ->all();
    }
}
