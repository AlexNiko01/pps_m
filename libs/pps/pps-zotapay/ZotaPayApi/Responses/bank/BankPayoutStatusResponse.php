<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 17:41
 */

namespace pps\zotapay\ZotaPayApi\Responses\bank;

use pps\zotapay\ZotaPayApi\Responses\BaseResponse;

/**
 * The type of response. May be status_response .
 * @property  $type
 * See Status List for details. .
 * @property  $status
 * Amount of the initial transaction. .
 * @property  $amount
 * Order id assigned to the order by Zotapay .
 * @property  $paynet_order_id
 * Merchant order id .
 * @property  $merchant_order_id
 * Customer phone. .
 * @property  $phone
 * HTML code of 3D authorization form, encoded in application/x_www_form_urlencoded MIME format. Merchant must decode
 *     this parameter before showing the form to the Customer. The Zotapay System returns the following response
 *     parameters when it gets 3D authorization form from the Issuer Bank. It contains auth form HTML code which must
 *     be passed through without any changes to the client’s browser. This parameter exists and has value only when the
 *     redirection HTML is already available. For non_3D this never happens. For 3D HTML has value after some short
 *     time after the processing has been started. .
 * @property  $html
 * Unique number assigned by Zotapay server to particular request from the Merchant. .
 * @property  $serial_number
 * Last four digits of customer credit card number. .
 * @property  $last_four_digits
 * Bank BIN of customer credit card number. .
 * @property  $bin
 * Type of customer credit card (VISA, MASTERCARD, etc). .
 * @property  $card_type
 * Processing gate support partial reversal (enabled or disabled). .
 * @property  $gate_partial_reversal
 * Processing gate support partial capture (enabled or disabled). .
 * @property  $gate_partial_capture
 * Transaction type (sale, reversal, capture, preauth). .
 * @property  $transaction_type
 * Bank Receiver Registration Number. .
 * @property  $processor_rrn
 * Acquirer transaction identifier. .
 * @property  $processor_tx_id
 * Electronical link to receipt https://gate.zotapay.com/paynet/view_receipt/ENDPOINTID/receipt_id/ .
 * @property  $receipt_id
 * Cardholder name. .
 * @property  $name
 * Cardholder name. .
 * @property  $cardholder_name
 * Card expiration month. .
 * @property  $card_exp_month
 * Card expiration year. .
 * @property  $card_exp_year
 * Unique card identifier to use for loyalty programs or fraud checks. .
 * @property  $card_hash_id
 * Customer e_mail. .
 * @property  $email
 * Bank name by customer card BIN. .
 * @property  $bank_name
 * Acquirer terminal identifier to show in receipt. .
 * @property  $terminal_id
 * Acquirer transaction processing date. .
 * @property  $paynet_processing_date
 * Bank approval code. .
 * @property  $approval_code
 * The current stage of the transaction processing. See Order Stage for details .
 * @property  $order_stage
 * The current bonuses balance of the loyalty program for current operation. if available .
 * @property  $loyalty_balance
 * The message from the loyalty program. if available .
 * @property  $loyalty_message
 * The bonus value of the loyalty program for current operation. if available .
 * @property  $loyalty_bonus
 * The name of the loyalty program for current operation. if available .
 * @property  $loyalty_program
 * Bank identifier of the payment recipient. .
 * @property  $descriptor
 * If status in declined, error, filtered this parameter contains the reason for decline .
 * @property  $error_message
 * The error code is case status in declined, error, filtered. .
 * @property  $error_code
 * Serial number from status request, if exists in request. Warning parameter amount always shows initial transaction
 *     amount, even if status is requested by_request_sn. .
 * @property  $by_request_sn
 * See 3d Secure Status List for details .
 * @property  $verified_3d_status
 * See Random Sum Check Status List for details .
 * @property  $verified_rsc_status
 */
class BankPayoutStatusResponse extends BaseResponse
{
    public $type;
    public $status;
    public $amount;
    public $paynet_order_id;
    public $merchant_order_id;
    public $phone;
    public $html;
    public $serial_number;
    public $last_four_digits;
    public $bin;
    public $card_type;
    public $gate_partial_reversal;
    public $gate_partial_capture;
    public $transaction_type;
    public $processor_rrn;
    public $processor_tx_id;
    public $receipt_id;
    public $name;
    public $cardholder_name;
    public $card_exp_month;
    public $card_exp_year;
    public $card_hash_id;
    public $email;
    public $bank_name;
    public $terminal_id;
    public $paynet_processing_date;
    public $approval_code;
    public $order_stage;
    public $loyalty_balance;
    public $loyalty_message;
    public $loyalty_bonus;
    public $loyalty_program;
    public $descriptor;
    public $error_message;
    public $error_code;
    public $by_request_sn;
    public $verified_3d_status;
    public $verified_rsc_status;

    public function rules()
    {
        return [
            [
                [
                    'type',
                    'status',
                    'amount',
                    'paynet_order_id',
                    'merchant_order_id',
                    'phone',
                    'html',
                    'serial_number',
                    'last_four_digits',
                    'bin',
                    'card_type',
                    'gate_partial_reversal',
                    'gate_partial_capture',
                    'transaction_type',
                    'processor_rrn',
                    'processor_tx_id',
                    'receipt_id',
                    'name',
                    'cardholder_name',
                    'card_exp_month',
                    'card_exp_year',
                    'card_hash_id',
                    'email',
                    'bank_name',
                    'terminal_id',
                    'paynet_processing_date',
                    'approval_code',
                    'order_stage',
                    'loyalty_balance',
                    'loyalty_message',
                    'loyalty_bonus',
                    'loyalty_program',
                    'descriptor',
                    'error_message',
                    'error_code',
                    'by_request_sn',
                    'verified_3d_status',
                    'verified_rsc_status',
                ],
                'trim'
            ]
        ];
    }
}
