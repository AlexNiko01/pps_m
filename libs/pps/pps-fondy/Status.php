<?php

namespace pps\fondy;

/**
 * Class Status
 * @package pps\fondy
 */
class Status
{
    # order has been created, but the customer has not entered payment details yet;
    # merchant must continue to request the status of the order
    const ORDER_STATUS_CREATED = 'created';
    # order is still in processing by payment gateway;
    #   merchant must continue to request the status of the order
    const ORDER_STATUS_PROCESSING = 'processing';
    # order is declined by FONDY payment gateway or by bank or by external payment system
    const ORDER_STATUS_DECLINED = 'declined';
    # order completed successfully, funds are hold on the payer’s account and soon will be credited of the merchant;
    # merchant can provide the service or ship goods
    const ORDER_STATUS_APPROVED = 'approved';
    # order lifetime expired.
    const ORDER_STATUS_REFUNDED = 'expired';
    # previously approved transaction was fully or partially reversed.
    # In this case parameter reversal_amount will be > 0
    const ORDER_STATUS_REVERSED = 'reversed';

    # Request processing status.
    # If parameters sent by merchant did not pass validation then failure, else success
    const RESPONSE_STATUS_FAILURE = "failure";
    const RESPONSE_STATUS_SUCCESS = "success";
}