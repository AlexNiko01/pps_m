<?php

return [
    'withdraw' => [
        'errors' => [
            /* Errors when validating parameter */
            'INVALID_OR_MISSING_ACTION' => 'Wrong action or no action is provided',
            'LOGIN_INVALID' => 'Email address and/or password were not provided',
            'INVALID_REC_PAYMENT_ID' => 'Invalid recurring payment ID is submitted by the merchant',
            'MISSING_EMAIL' => 'Provide registered email address of merchant account',
            'MISSING_PASSWORD' => 'Provide correct API/MQI password',
            'MISSING_AMOUNT' => 'Provide amount you wish to send',
            'MISSING_CURRENCY' => 'Provide currency you wish to send',
            'MISSING_BNF_EMAIL' => 'Provide email address of the beneficiary',
            'MISSING_SUBJECT' => 'Provide subject of the payment',
            'MISSING_NOTE' => 'Provide notes for the payment',
            /* Errors during log in */
            'CANNOT_LOGIN' => 'Email address and/or API/MQI password are incorrect',
            'PAYMENT_DENIED' => 'Check in your account profile that the API is enabled and you are posting your requests from the IP address specified',
            /* Errors when validating payment details */
            'INVALID_BNF_EMAIL' => 'Check the format of the beneficiary email address',
            'INVALID_SUBJECT' => 'Check parameter length submitted',
            'INVALID_NOTE' => 'Check parameter length submitted',
            'INVALID_FRN_TRN_ID' => 'Check parameter length submitted',
            'INVALID_AMOUNT' => 'Check amount format',
            'INVALID_CURRENCY' => 'Check currency code',
            'EXECUTION_PENDING' => 'If you resend a transfer request with the same session identifier before the \'transfer\' request was processed, this error will be returned',
            'ALREADY_EXECUTED' => 'If you have requested that the value for frn_trn_id must be unique for each transfer, this error will be returned when you try to submit the same value for more than one transfer',
            'BALANCE_NOT_ENOUGH' => 'Sending amount exceeds account balance',
            'SINGLE_TRN_LIMIT_VIOLATED' => 'Maximum amount per transaction = EUR 10,000',
            'DISALLOWED_RECIPIENT' => 'You are not permitted to send money to the recipient. E.g. Gaming merchants are not permitted to send or receive payments to/from US based customers', 
            'CHECK_FOR_VERIFIED_EMAIL' => 'Your account email address needs to be verified',
            'LL_NO_PAYMENT' => 'Your account is locked for security reasons. Please contact us',
            /* Errors when making Skrill 1 ‐ Tap payment requests */
            'CUSTOMER_IS_LOCKED' => 'The customer\'s account is locked for outgoing payments',
            'BALANCE_NOT_ENOUGH' => 'The customer\'s account balance is insufficient',
            'RECIPIENT_LIMIT_EXCEEDED' => 'The customer\'s account limits are not sufficient',
            'CARD_FAILED' => 'The customer\'s credit or debit card failed',
            'REQUEST_FAILED' => 'Generic response for transaction failing for any other reason',
            'ONDEMAND_CANCELLED' => 'The customer has cancelled this Skrill 1 ‐ Tap payment',
            'ONDEMAND_INVALID' => 'The Skrill 1 ‐ Tap payment requested does not exist',
            'MAX_REQ_REACHED' => 'Too many failed Skrill 1 ‐ Tap payment requests to the API. For security reasons, only two failed attempts per user per 24 hours are allowed',
            'MAX_AMOUNT_REACHED' => 'The payment amount is greater than the maximum amount configured when 1 ‐ Tap payments were setup for this user.',
        ],
    ],
    'deposit' => [
        'errors' => [
            1 => 'Referred by Card Issuer',
            2 => 'Invalid Merchant. Merchant account inactive',
            3 => 'Pick-up card',
            4 => 'Declined by Card Issuer',
            5 => 'Insufficient funds',
            6 => 'Merchant/NETELLER/Processor declined',
            7 => 'Incorrect PIN',
            8 => 'PIN tries exceed - card blocked',
            9 => 'Invalid Transaction',
            10 => 'Transaction frequency limit exceeded',
            11 => 'Invalid Amount format. Amount too high. Amount too low. Limit Exceeded',
            12 => 'Invalid credit card or bank account',
            13 => 'Invalid card Issuer',
            15 => 'Duplicate transaction reference',
            19 => 'Authentication credentials expired/disabled/locked/invalid. Cannot authenticate. Request not authorized.',
            20 => 'Neteller member is in a blocked country/state/region/geolocation',
            22 => 'Unsupported Accept header or Content-Type',
            24 => 'Card expired',
            27 => 'Requested API function not supported (legacy function)',
            28 => 'Lost/stolen card',
            30 => 'Format Failure',
            32 => 'Card Security Code (CVV2/CVC2) Check Failed',
            34 => 'Illegal Transaction',
            35 => 'Member/Merchant not entitled/authorized. Account closed. Unauthorized access. ',
            37 => 'Card restricted by Card Issuer',
            38 => 'Security violation',
            42 => 'Card blocked by Card Issuer',
            44 => 'Card Issuing Bank or Network is not available',
            45 => 'Processing error - card type is not processed by the authorization centre',
            51 => 'System error',
            58 => 'Transaction not permitted by acquirer',
            63 => 'Transaction not permitted for cardholder',
            64 => 'Invalid accountId/country/currency/customer/email/field/merchant ' .
                    'reference / merchant account currency / term length / verification code. ' .
                    'Account not found/disabled. Entity not found. URI not found. ' .
                    'Existing member email. Plan already exists. Bad request.',
            67 => 'BitPay session expired',
            68 => 'Referenced transaction has not been settled',
            69 => 'Referenced transaction is not fully authenticated',
            70 => 'Customer failed 3DS verification',
            80 => 'Fraud rules declined',
            98 => 'Error in communication with provider',
            99 => 'Cannot delete a subscribed plan. Method not supported.',
        ],
    ],
    'pay_status' => [
        'pending' => [
            'text' => 'Очікує на розгляд',
            'final' => true,
        ],
        'processed' => [
            'text' => 'выплата проведена',
            'final' => true,
        ],
        'failed' => [
            'text' => 'ошибка выплаты',
            'final' => true,
        ],
        'canceled' => [
            'text' => 'выплата отменена, средства возвращены на баланс проекта',
            'final' => true,
        ],
    ],
];