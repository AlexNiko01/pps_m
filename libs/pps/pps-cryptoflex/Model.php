<?php /** @noinspection LongInheritanceChainInspection */

namespace pps\cryptoflex;

use pps\cryptoflex\CryptoFlexApi\Currencies\crypto\CurrenciesList;
use pps\payment\CommonInMultiSettingsInterface;
use pps\payment\IModelListSupport;
use pps\payment\PaymentModel;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;
use yii\helpers\ArrayHelper;

class Model extends PaymentModel implements IModelListSupport, CommonInMultiSettingsInterface
{
    public $partner_id;
    public $secret_key;
    public $wallet;
    public $confirmations_trans = 5;
    public $confirmations_payout = 3;
    public $confirmations_withdraw = 3;
    public $fee_level = 'medium';
    public $denomination = 1;

    public function attributeLabels(): array
    {
        return [
            'partner_id' => 'Partner Id',
            'secret_key' => 'Secret Key',
            'wallet' => 'Merchant wallet',
            'confirmations_trans' => 'Number of confirmations to transit address',
            'confirmations_payout' => 'Number of confirmations from transit address',
            'confirmations_withdraw' => 'Number of confirmations withdraw',
            'fee_level' => 'Fee level',
            'denomination' => 'Denomination of payment',
        ];
    }

    public function rules(): array
    {
        return [
            [['partner_id', 'secret_key'], 'required', 'on' => self::COMMON_PARAMS_SCENARIO],
            [['partner_id', 'secret_key'], 'trim', 'on' => self::COMMON_PARAMS_SCENARIO],
            [
                [
                    'wallet',
                    'confirmations_trans',
                    'confirmations_payout',
                    'confirmations_withdraw',
                    'fee_level',
                    'denomination'
                ],
                'trim',
                'on' => self::INDIVIDUAL_PARAMS_SCENARIO
            ],
            [['partner_id', 'secret_key'], 'trim', 'on' => self::COMMON_PARAMS_SCENARIO],
            [
                [
                    'wallet',
                    'confirmations_trans',
                    'confirmations_payout',
                    'confirmations_withdraw',
                    'fee_level',
                    'denomination'
                ],
                'required',
                'on' => self::SCENARIO_REQUIRED
            ],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields(): array
    {
        return [
            'partner_id' => self::FIELD_TYPE_TEXT,
            'secret_key' => self::FIELD_TYPE_TEXT,
            'wallet' => self::FIELD_TYPE_TEXT,
            'confirmations_trans' => self::FIELD_TYPE_NUMBER,
            'confirmations_payout' => self::FIELD_TYPE_NUMBER,
            'confirmations_withdraw' => self::FIELD_TYPE_NUMBER,
            'fee_level' => self::FIELD_TYPE_LIST,
            'denomination' => self::FIELD_TYPE_LIST
        ];
    }

    /**
     * Transformation key from inside API to key from payment system
     * @param string $key
     * @return string|int|null
     */
    public function transformApiKey(string $key)
    {
        $keys = [
            'partner_id' => 'partner_id',
            'secret_key' => 'secret_key',
            'field3' => 'wallet',
            'field4' => 'confirmations_trans',
            'field5' => 'confirmations_payout',
            'field6' => 'confirmations_withdraw',
            'field7' => 'fee_level',
            'field8' => 'denomination',
        ];
        return $keys[$key] ?? null;
    }

    public function attributes()
    {
        switch ($this->scenario) {
            case self::COMMON_PARAMS_SCENARIO:
                return $this->commonSettings();
            default:
                return array_diff(parent::attributes(), $this->commonSettings());
        }
    }

    /**
     * @return array
     */
    public function getRequiredParams(): array
    {
        return ['partner_id', 'secret_key'];
    }

    /**
     * Array with list of settings which is common for all currencies
     * @return array
     */
    public function commonSettings(): array
    {
        return ['partner_id', 'secret_key'];
    }

    /**
     * Array with list of currencies which has own settings
     * @return array
     */
    public function currencies(): array
    {
        $currencies = [];
        try {
            $currencies = \Yii::$container->get(CurrenciesList::class)->getCurrenciesList();
        } catch (NotInstantiableException $e) {
            \Yii::error($e, 'pps-cryptoflex');
        } catch (InvalidConfigException $e) {
            \Yii::error($e, 'pps-cryptoflex');
        }
        return ArrayHelper::merge([self::COMMON_NAME], $currencies);
    }

    /**
     * Return the all possible values for list field
     * @param string $listFieldName
     * @return array
     */
    public function getListValues(string $listFieldName): array
    {
        $data = [
            'fee_level' => [
                'low' => 'low',
                'medium' => 'medium',
                'high' => 'high'
            ],
            'denomination' => [
                1 => '1 : 1',
                1000 => 'ml'
            ]
        ];
        return ArrayHelper::getValue($data, $listFieldName, []);
    }
}
