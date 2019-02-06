<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 18.07.18
 * Time: 11:38
 */

namespace pps\zotapay\ZotaPayApi;

class ZotaPayStatuses
{
    /**
     * Transaction is approved, final status
     */
    const STATUS_TRANSACTION_APPROVED = 'approved';
    /**
     *    Transaction is declined, final status
     */
    const STATUS_TRANSACTION_DECLINED = 'declined';
    /**
     * Transaction is declined but something went wrong, please inform your account manager, final status
     */
    const STATUS_TRANSACTION_ERROR = 'error';
    /**
     * Transaction is declined by fraud internal or external control systems, final status
     */
    const STATUS_TRANSACTION_FILTERED = 'filtered';
    /**
     * Transaction is being processed, you should continue polling, non final status
     */
    const STATUS_TRANSACTION_PROCESSING = 'processing';
    /**
     *    The status of transaction is unknown, please inform your account manager, non final status
     */
    const STATUS_TRANSACTION_UNKNOWN = 'unknown';

    /**
     * Card MPI Status Y or A
     */
    const STATUS_3D_SECURE_AUTHENTICATED = 'AUTHENTICATED';
    /**
     * Card MPI Status N
     */
    const STATUS_3D_SECURE_NOT_AUTHENTICATED = 'NOT_AUTHENTICATED';
    /**
     *    Card MPI Status U
     */
    const STATUS_3D_SECURE_UNSUPPORTED = 'UNSUPPORTED';
    /**
     * Verification have not been performed
     */
    const STATUS_3D_SECURE_UNKNOWN = 'UNKNOWN';

    /**
     *    The customer has entered correct random sum
     */
    const STATUS_RAND_SUM_CHECK_AUTHENTICATED = 'AUTHENTICATED';
    /**
     *    The customer has entered incorrect random sum
     */
    const STATUS_RAND_SUM_CHECK_NOT_AUTHENTICATED = 'NOT_AUTHENTICATED';
    /**
     * The customer has not entered random sum
     */
    const STATUS_RAND_SUM_CHECK_UNSUPPORTED = 'UNSUPPORTED';
    /**
     *    Verification have not been performed
     */
    const STATUS_RAND_SUM_CHECK_UNKNOWN = 'UNKNOWN';

    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AUTH_3D_VALIDATED = 'AUTH_3D_VALIDATED';
    /**
     * Transaction is being processed, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AUTH_3D_VALIDATING = 'AUTH_3D_VALIDATING';
    /**
     * Transaction has been approved, final status * final stage = Yes
     */
    const TRANSACTION_STAGE_AUTH_APPROVED = 'AUTH_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_AUTH_CANCELLED = 'AUTH_CANCELLED';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AUTH_CARD_CHIP_VALIDATED = 'AUTH_CARD_CHIP_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_CARD_CHIP_VALIDATING = 'AUTH_CARD_CHIP_VALIDATING';
    /** * Transaction has been declined, but processing is still in progress, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_CHAIN_DECLINED = 'AUTH_CHAIN_DECLINED';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AUTH_CUST_EMAIL_VALIDATED = 'AUTH_CUST_EMAIL_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_CUST_EMAIL_VALIDATING = 'AUTH_CUST_EMAIL_VALIDATING';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AUTH_CUST_PHONE_VALIDATED = 'AUTH_CUST_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_CUST_PHONE_VALIDATING = 'AUTH_CUST_PHONE_VALIDATING';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No */
    const TRANSACTION_STAGE_AUTH_CUST_TWITTER_VALIDATED = 'AUTH_CUST_TWITTER_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_CUST_TWITTER_VALIDATING = 'AUTH_CUST_TWITTER_VALIDATING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_AUTH_DECLINED = 'AUTH_DECLINED';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No */
    const TRANSACTION_STAGE_AUTH_DESCRIPTOR_VALIDATED = 'AUTH_DESCRIPTOR_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_DESCRIPTOR_VALIDATING = 'AUTH_DESCRIPTOR_VALIDATING';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_AUTH_ERROR = 'AUTH_ERROR';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_AUTH_FAILED = 'AUTH_FAILED';
    /**
     * Transaction has been declined by fraud internal or external control systems, final status * final stage = Yes
     */
    const TRANSACTION_STAGE_AUTH_FILTERED = 'AUTH_FILTERED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_FILTERING = 'AUTH_FILTERING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_LOAN_REGISTERING = 'AUTH_LOAN_REGISTERING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_LOAN_VALIDATING = 'AUTH_LOAN_VALIDATING';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AUTH_LOYALTY_VALIDATED = 'AUTH_LOYALTY_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_LOYALTY_VALIDATING = 'AUTH_LOYALTY_VALIDATING';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AUTH_PHONE_VALIDATED = 'AUTH_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_PHONE_VALIDATING = 'AUTH_PHONE_VALIDATING';
    /** * Verification has been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_PHONE_VERIFICATED = 'AUTH_PHONE_VERIFICATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_PHONE_VERIFICATING = 'AUTH_PHONE_VERIFICATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_PROCESSING = 'AUTH_PROCESSING';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AUTH_RND_VALIDATED = 'AUTH_RND_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_RND_VALIDATING = 'AUTH_RND_VALIDATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_STARTING = 'AUTH_STARTING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AUTH_TEST_CHECKING = 'AUTH_TEST_CHECKING';
    /**
     * Transaction has been declined but something went wrong, please inform your account manager, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AUTH_UNKNOWN = 'AUTH_UNKNOWN';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AV_3D_VALIDATED = 'AV_3D_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_3D_VALIDATING = 'AV_3D_VALIDATING';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_AV_APPROVED = 'AV_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_AV_CANCELLED = 'AV_CANCELLED';
    /** * Transaction has been declined, but processing is still in progress, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_CHAIN_DECLINED = 'AV_CHAIN_DECLINED';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AV_CUST_EMAIL_VALIDATED = 'AV_CUST_EMAIL_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_CUST_EMAIL_VALIDATING = 'AV_CUST_EMAIL_VALIDATING';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AV_CUST_PHONE_VALIDATED = 'AV_CUST_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_CUST_PHONE_VALIDATING = 'AV_CUST_PHONE_VALIDATING';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AV_CUST_TWITTER_VALIDATED = 'AV_CUST_TWITTER_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_CUST_TWITTER_VALIDATING = 'AV_CUST_TWITTER_VALIDATING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_AV_DECLINED = 'AV_DECLINED';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AV_DESCRIPTOR_VALIDATED = 'AV_DESCRIPTOR_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_DESCRIPTOR_VALIDATING = 'AV_DESCRIPTOR_VALIDATING';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_AV_ERROR = 'AV_ERROR';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_AV_FAILED = 'AV_FAILED';
    /**
     * Transaction has been declined by fraud internal or external control systems, final status
     * final stage = Yes
     */
    const TRANSACTION_STAGE_AV_FILTERED = 'AV_FILTERED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_FILTERING = 'AV_FILTERING';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AV_PHONE_VALIDATED = 'AV_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_PHONE_VALIDATING = 'AV_PHONE_VALIDATING';
    /** * Verification has been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_PHONE_VERIFICATED = 'AV_PHONE_VERIFICATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_PHONE_VERIFICATING = 'AV_PHONE_VERIFICATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_PROCESSING = 'AV_PROCESSING';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AV_RND_VALIDATED = 'AV_RND_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_RND_VALIDATING = 'AV_RND_VALIDATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_STARTING = 'AV_STARTING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_AV_TEST_CHECKING = 'AV_TEST_CHECKING';
    /**
     * Transaction has been declined but something went wrong, please inform your account manager, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_AV_UNKNOWN = 'AV_UNKNOWN';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_CANCEL_APPROVED = 'CANCEL_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_CANCEL_DECLINED = 'CANCEL_DECLINED';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_CANCEL_ERROR = 'CANCEL_ERROR';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CANCEL_PROCESSING = 'CANCEL_PROCESSING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_CANCEL_REJECTED = 'CANCEL_REJECTED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CANCEL_STARTING = 'CANCEL_STARTING';
    /**
     * Transaction has been declined but something went wrong, please inform your account manager, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_CANCEL_UNKNOWN = 'CANCEL_UNKNOWN';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_CAPTURE_APPROVED = 'CAPTURE_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_CAPTURE_DECLINED = 'CAPTURE_DECLINED';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_CAPTURE_ERROR = 'CAPTURE_ERROR';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CAPTURE_PROCESSING = 'CAPTURE_PROCESSING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_CAPTURE_REJECTED = 'CAPTURE_REJECTED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CAPTURE_STARTING = 'CAPTURE_STARTING';
    /**
     * Transaction has been declined but something went wrong, please inform your account manager, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_CAPTURE_UNKNOWN = 'CAPTURE_UNKNOWN';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_CHARGEBACK_APPROVED = 'CHARGEBACK_APPROVED';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_CREATE_CM_APPROVED = 'CREATE_CM_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_CREATE_CM_CANCELLED = 'CREATE_CM_CANCELLED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_CREATE_CM_DECLINED = 'CREATE_CM_DECLINED';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_CREATE_CM_EMAIL_VALIDATED = 'CREATE_CM_EMAIL_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_EMAIL_VALIDATING = 'CREATE_CM_EMAIL_VALIDATING';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_CREATE_CM_ERROR = 'CREATE_CM_ERROR';
    /**
     * Transaction is being processed, internal stage have been finished, you should continue polling, non final status
     * final stage = No
     */
    const TRANSACTION_STAGE_CREATE_CM_EXTERNAL_VALIDATED = 'CREATE_CM_EXTERNAL_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_EXTERNAL_VALIDATING = 'CREATE_CM_EXTERNAL_VALIDATING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_CREATE_CM_FAILED = 'CREATE_CM_FAILED';
    /**
     * Transaction has been declined by fraud internal or external control systems, final status
     * final stage = Yes
     */
    const TRANSACTION_STAGE_CREATE_CM_FILTERED = 'CREATE_CM_FILTERED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_FILTERING = 'CREATE_CM_FILTERING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_PE_VALIDATED = 'CREATE_CM_PE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_PE_VALIDATING = 'CREATE_CM_PE_VALIDATING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_PHONE_VALIDATED = 'CREATE_CM_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_PHONE_VALIDATING = 'CREATE_CM_PHONE_VALIDATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_PROCESSING = 'CREATE_CM_PROCESSING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_STARTING = 'CREATE_CM_STARTING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_TWITTER_VALIDATED = 'CREATE_CM_TWITTER_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_TWITTER_VALIDATING = 'CREATE_CM_TWITTER_VALIDATING';
    /** * Transaction has been declined but something went wrong, please inform your account manager, non final status * final stage = No */
    const TRANSACTION_STAGE_CREATE_CM_UNKNOWN = 'CREATE_CM_UNKNOWN';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_DELETE_CM_APPROVED = 'DELETE_CM_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_DELETE_CM_CANCELLED = 'DELETE_CM_CANCELLED';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_DELETE_CM_EMAIL_VALIDATED = 'DELETE_CM_EMAIL_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_DELETE_CM_EMAIL_VALIDATING = 'DELETE_CM_EMAIL_VALIDATING';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_DELETE_CM_ERROR = 'DELETE_CM_ERROR';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_DELETE_CM_FAILED = 'DELETE_CM_FAILED';
    /** * Transaction has been declined by fraud internal or external control systems, final status * final stage = Yes */
    const TRANSACTION_STAGE_DELETE_CM_FILTERED = 'DELETE_CM_FILTERED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_DELETE_CM_FILTERING = 'DELETE_CM_FILTERING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_DELETE_CM_PHONE_VALIDATED = 'DELETE_CM_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_DELETE_CM_PHONE_VALIDATING = 'DELETE_CM_PHONE_VALIDATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_DELETE_CM_PROCESSING = 'DELETE_CM_PROCESSING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_DELETE_CM_STARTING = 'DELETE_CM_STARTING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_DELETE_CM_TWITTER_VALIDATED = 'DELETE_CM_TWITTER_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_DELETE_CM_TWITTER_VALIDATING = 'DELETE_CM_TWITTER_VALIDATING';
    /** * Transaction has been declined but something went wrong, please inform your account manager, non final status * final stage = No */
    const TRANSACTION_STAGE_DELETE_CM_UNKNOWN = 'DELETE_CM_UNKNOWN';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_DISPUTE_APPROVED = 'DISPUTE_APPROVED';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_FRAUD_APPROVED = 'FRAUD_APPROVED';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_INQUIRE_CM_APPROVED = 'INQUIRE_CM_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_INQUIRE_CM_CANCELLED = 'INQUIRE_CM_CANCELLED';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_INQUIRE_CM_EMAIL_VALIDATED = 'INQUIRE_CM_EMAIL_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_INQUIRE_CM_EMAIL_VALIDATING = 'INQUIRE_CM_EMAIL_VALIDATING';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_INQUIRE_CM_ERROR = 'INQUIRE_CM_ERROR';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_INQUIRE_CM_FAILED = 'INQUIRE_CM_FAILED';
    /** * Transaction has been declined by fraud internal or external control systems, final status * final stage = Yes */
    const TRANSACTION_STAGE_INQUIRE_CM_FILTERED = 'INQUIRE_CM_FILTERED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_INQUIRE_CM_FILTERING = 'INQUIRE_CM_FILTERING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_INQUIRE_CM_PHONE_VALIDATED = 'INQUIRE_CM_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_INQUIRE_CM_PHONE_VALIDATING = 'INQUIRE_CM_PHONE_VALIDATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_INQUIRE_CM_PROCESSING = 'INQUIRE_CM_PROCESSING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_INQUIRE_CM_STARTING = 'INQUIRE_CM_STARTING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_INQUIRE_CM_TWITTER_VALIDATED = 'INQUIRE_CM_TWITTER_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_INQUIRE_CM_TWITTER_VALIDATING = 'INQUIRE_CM_TWITTER_VALIDATING';
    /** * Transaction has been declined but something went wrong, please inform your account manager, non final status * final stage = No */
    const TRANSACTION_STAGE_INQUIRE_CM_UNKNOWN = 'INQUIRE_CM_UNKNOWN';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_MFO_SCORING_APPROVED = 'MFO_SCORING_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_MFO_SCORING_DECLINED = 'MFO_SCORING_DECLINED';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_MFO_SCORING_ERROR = 'MFO_SCORING_ERROR';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_MFO_SCORING_STARTING = 'MFO_SCORING_STARTING';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_PAN_ELIGIBILITY_APPROVED = 'PAN_ELIGIBILITY_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_PAN_ELIGIBILITY_CANCELLED = 'PAN_ELIGIBILITY_CANCELLED';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_PAN_ELIGIBILITY_ERROR = 'PAN_ELIGIBILITY_ERROR';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_PAN_ELIGIBILITY_FAILED = 'PAN_ELIGIBILITY_FAILED';
    /** * Transaction has been declined by fraud internal or external control systems, final status * final stage = Yes */
    const TRANSACTION_STAGE_PAN_ELIGIBILITY_FILTERED = 'PAN_ELIGIBILITY_FILTERED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_PAN_ELIGIBILITY_FILTERING = 'PAN_ELIGIBILITY_FILTERING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_PAN_ELIGIBILITY_PROCESSING = 'PAN_ELIGIBILITY_PROCESSING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_PAN_ELIGIBILITY_REJECTED = 'PAN_ELIGIBILITY_REJECTED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_PAN_ELIGIBILITY_STARTING = 'PAN_ELIGIBILITY_STARTING';
    /** * Transaction has been declined but something went wrong, please inform your account manager, non final status * final stage = No */
    const TRANSACTION_STAGE_PAN_ELIGIBILITY_UNKNOWN = 'PAN_ELIGIBILITY_UNKNOWN';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_RETRIEVAL_APPROVED = 'RETRIEVAL_APPROVED';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_REVERSAL_APPROVED = 'REVERSAL_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_REVERSAL_DECLINED = 'REVERSAL_DECLINED';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_REVERSAL_ERROR = 'REVERSAL_ERROR';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_REVERSAL_PROCESSING = 'REVERSAL_PROCESSING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_REVERSAL_REJECTED = 'REVERSAL_REJECTED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_REVERSAL_STARTING = 'REVERSAL_STARTING';
    /** * Transaction has been declined but something went wrong, please inform your account manager, non final status * final stage = No */
    const TRANSACTION_STAGE_REVERSAL_UNKNOWN = 'REVERSAL_UNKNOWN';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_SALE3D_END_APPROVED = 'SALE3D_END_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_SALE3D_END_DECLINED = 'SALE3D_END_DECLINED';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_SALE3D_END_ERROR = 'SALE3D_END_ERROR';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE3D_END_PROCESSING = 'SALE3D_END_PROCESSING';
    /** * Transaction has been declined but something went wrong, please inform your account manager, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE3D_END_UNKNOWN = 'SALE3D_END_UNKNOWN';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_3D_VALIDATED = 'SALE_3D_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_3D_VALIDATING = 'SALE_3D_VALIDATING';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_SALE_APPROVED = 'SALE_APPROVED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_AUTO_REVERSAL_PROCESSING = 'SALE_AUTO_REVERSAL_PROCESSING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_SALE_CANCELLED = 'SALE_CANCELLED';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_CARD_CHIP_VALIDATED = 'SALE_CARD_CHIP_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_CARD_CHIP_VALIDATING = 'SALE_CARD_CHIP_VALIDATING';
    /** * Transaction has been declined, but processing is still in progress, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_CHAIN_DECLINED = 'SALE_CHAIN_DECLINED';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_CUST_EMAIL_VALIDATED = 'SALE_CUST_EMAIL_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_CUST_EMAIL_VALIDATING = 'SALE_CUST_EMAIL_VALIDATING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_CUST_PHONE_VALIDATED = 'SALE_CUST_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_CUST_PHONE_VALIDATING = 'SALE_CUST_PHONE_VALIDATING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_CUST_TWITTER_VALIDATED = 'SALE_CUST_TWITTER_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_CUST_TWITTER_VALIDATING = 'SALE_CUST_TWITTER_VALIDATING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_SALE_DECLINED = 'SALE_DECLINED';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_DESCRIPTOR_VALIDATED = 'SALE_DESCRIPTOR_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_DESCRIPTOR_VALIDATING = 'SALE_DESCRIPTOR_VALIDATING';
    /** * Transaction is being processed, sending 0200 to an Acquirer, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_EMV_PROCESSING = 'SALE_EMV_PROCESSING';
    /** * Transaction is being processed, waiting for 2nd Gen AC from a chip, you should send EMV Final Advice to a host * final stage = No */
    const TRANSACTION_STAGE_SALE_EMV_VALIDATING = 'SALE_EMV_VALIDATING';
    /** * Transaction is being processed, EMV Final Advice is received, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_EMV_VALIDATED = 'SALE_EMV_VALIDATED';
    /** * Transaction is being processed, sending 0220 to an Acquirer, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_EMV_ADVICE_PROCESSING = 'SALE_EMV_ADVICE_PROCESSING';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_SALE_ERROR = 'SALE_ERROR';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_SALE_FAILED = 'SALE_FAILED';
    /** * Transaction has been declined by fraud internal or external control systems, final status * final stage = Yes */
    const TRANSACTION_STAGE_SALE_FILTERED = 'SALE_FILTERED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_FILTERING = 'SALE_FILTERING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_LOYALTY_VALIDATED = 'SALE_LOYALTY_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_LOYALTY_VALIDATING = 'SALE_LOYALTY_VALIDATING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_PHONE_VALIDATED = 'SALE_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_PHONE_VALIDATING = 'SALE_PHONE_VALIDATING';
    /** * Verification has been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_PHONE_VERIFICATED = 'SALE_PHONE_VERIFICATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_PHONE_VERIFICATING = 'SALE_PHONE_VERIFICATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_PROCESSING = 'SALE_PROCESSING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_RND_VALIDATED = 'SALE_RND_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_RND_VALIDATING = 'SALE_RND_VALIDATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_STARTING = 'SALE_STARTING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_TEST_CHECKING = 'SALE_TEST_CHECKING';
    /** * Transaction has been declined but something went wrong, please inform your account manager, non final status * final stage = No */
    const TRANSACTION_STAGE_SALE_UNKNOWN = 'SALE_UNKNOWN';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_3D_VALIDATED = 'TRANSFER_3D_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_3D_VALIDATING = 'TRANSFER_3D_VALIDATING';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_TRANSFER_APPROVED = 'TRANSFER_APPROVED';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_BALANCE_VALIDATED = 'TRANSFER_BALANCE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_BALANCE_VALIDATING = 'TRANSFER_BALANCE_VALIDATING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_TRANSFER_CANCELLED = 'TRANSFER_CANCELLED';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_CARD_CHIP_VALIDATED = 'TRANSFER_CARD_CHIP_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_CARD_CHIP_VALIDATING = 'TRANSFER_CARD_CHIP_VALIDATING';
    /** * Transaction has been declined, but processing is still in progress, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_CHAIN_DECLINED = 'TRANSFER_CHAIN_DECLINED';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_CHECK_VALIDATED = 'TRANSFER_CHECK_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_CHECK_VALIDATING = 'TRANSFER_CHECK_VALIDATING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_CUST_EMAIL_VALIDATED = 'TRANSFER_CUST_EMAIL_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_CUST_EMAIL_VALIDATING = 'TRANSFER_CUST_EMAIL_VALIDATING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_CUST_PHONE_VALIDATED = 'TRANSFER_CUST_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_CUST_PHONE_VALIDATING = 'TRANSFER_CUST_PHONE_VALIDATING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_CUST_TWITTER_VALIDATED = 'TRANSFER_CUST_TWITTER_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_CUST_TWITTER_VALIDATING = 'TRANSFER_CUST_TWITTER_VALIDATING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_TRANSFER_DECLINED = 'TRANSFER_DECLINED';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_DESCRIPTOR_VALIDATED = 'TRANSFER_DESCRIPTOR_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_DESCRIPTOR_VALIDATING = 'TRANSFER_DESCRIPTOR_VALIDATING';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_TRANSFER_ERROR = 'TRANSFER_ERROR';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_TRANSFER_FAILED = 'TRANSFER_FAILED';
    /** * Transaction has been declined by fraud internal or external control systems, final status * final stage = Yes */
    const TRANSACTION_STAGE_TRANSFER_FILTERED = 'TRANSFER_FILTERED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_FILTERING = 'TRANSFER_FILTERING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_LOYALTY_VALIDATED = 'TRANSFER_LOYALTY_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_LOYALTY_VALIDATING = 'TRANSFER_LOYALTY_VALIDATING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_PHONE_VALIDATED = 'TRANSFER_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_PHONE_VALIDATING = 'TRANSFER_PHONE_VALIDATING';
    /** * Verification has been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_PHONE_VERIFICATED = 'TRANSFER_PHONE_VERIFICATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_PHONE_VERIFICATING = 'TRANSFER_PHONE_VERIFICATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_PROCESSING = 'TRANSFER_PROCESSING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_RND_VALIDATED = 'TRANSFER_RND_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_RND_VALIDATING = 'TRANSFER_RND_VALIDATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_TEST_CHECKING = 'TRANSFER_TEST_CHECKING';
    /** * Transaction has been declined but something went wrong, please inform your account manager, non final status * final stage = No */
    const TRANSACTION_STAGE_TRANSFER_UNKNOWN = 'TRANSFER_UNKNOWN';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_UPDATE_CM_APPROVED = 'UPDATE_CM_APPROVED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_UPDATE_CM_CANCELLED = 'UPDATE_CM_CANCELLED';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_UPDATE_CM_DECLINED = 'UPDATE_CM_DECLINED';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_EMAIL_VALIDATED = 'UPDATE_CM_EMAIL_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_EMAIL_VALIDATING = 'UPDATE_CM_EMAIL_VALIDATING';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_UPDATE_CM_ERROR = 'UPDATE_CM_ERROR';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_EXTERNAL_VALIDATED = 'UPDATE_CM_EXTERNAL_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_EXTERNAL_VALIDATING = 'UPDATE_CM_EXTERNAL_VALIDATING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_UPDATE_CM_FAILED = 'UPDATE_CM_FAILED';
    /** * Transaction has been declined by fraud internal or external control systems, final status * final stage = Yes */
    const TRANSACTION_STAGE_UPDATE_CM_FILTERED = 'UPDATE_CM_FILTERED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_FILTERING = 'UPDATE_CM_FILTERING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_PE_VALIDATED = 'UPDATE_CM_PE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_PE_VALIDATING = 'UPDATE_CM_PE_VALIDATING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_PHONE_VALIDATED = 'UPDATE_CM_PHONE_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_PHONE_VALIDATING = 'UPDATE_CM_PHONE_VALIDATING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_PROCESSING = 'UPDATE_CM_PROCESSING';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_STARTING = 'UPDATE_CM_STARTING';
    /** * Transaction is being processed, internal stage have been finished, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_TWITTER_VALIDATED = 'UPDATE_CM_TWITTER_VALIDATED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_TWITTER_VALIDATING = 'UPDATE_CM_TWITTER_VALIDATING';
    /** * Transaction has been declined but something went wrong, please inform your account manager, non final status * final stage = No */
    const TRANSACTION_STAGE_UPDATE_CM_UNKNOWN = 'UPDATE_CM_UNKNOWN';
    /** * Transaction has been approved, final status * final stage = Yes */
    const TRANSACTION_STAGE_VOID_APPROVED = 'VOID_APPROVED';
    /** * The status of transaction is unknown, please inform your account manager, final status * final stage = Yes */
    const TRANSACTION_STAGE_VOID_ERROR = 'VOID_ERROR';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_VOID_PROCESSING = 'VOID_PROCESSING';
    /** * Transaction has been declined, final status * final stage = Yes */
    const TRANSACTION_STAGE_VOID_REJECTED = 'VOID_REJECTED';
    /** * Transaction is being processed, you should continue polling, non final status * final stage = No */
    const TRANSACTION_STAGE_VOID_STARTING = 'VOID_STARTING';
    /** * Transaction has been declined but something went wrong, please inform your account manager, non final status * final stage = No */
    const TRANSACTION_STAGE_VOID_UNKNOWN = 'VOID_UNKNOWN';


    public static function getTransactionFinalStages()
    {
        return [
            self::TRANSACTION_STAGE_AUTH_APPROVED,
            self::TRANSACTION_STAGE_AUTH_CANCELLED,
            self::TRANSACTION_STAGE_AUTH_DECLINED,
            self::TRANSACTION_STAGE_AUTH_ERROR,
            self::TRANSACTION_STAGE_AUTH_FAILED,
            self::TRANSACTION_STAGE_AUTH_FILTERED,
            self::TRANSACTION_STAGE_AV_APPROVED,
            self::TRANSACTION_STAGE_AV_CANCELLED,
            self::TRANSACTION_STAGE_AV_DECLINED,
            self::TRANSACTION_STAGE_AV_ERROR,
            self::TRANSACTION_STAGE_AV_FAILED,
            self::TRANSACTION_STAGE_AV_FILTERED,
            self::TRANSACTION_STAGE_CANCEL_APPROVED,
            self::TRANSACTION_STAGE_CANCEL_DECLINED,
            self::TRANSACTION_STAGE_CANCEL_ERROR,
            self::TRANSACTION_STAGE_CANCEL_REJECTED,
            self::TRANSACTION_STAGE_CAPTURE_APPROVED,
            self::TRANSACTION_STAGE_CAPTURE_DECLINED,
            self::TRANSACTION_STAGE_CAPTURE_ERROR,
            self::TRANSACTION_STAGE_CAPTURE_REJECTED,
            self::TRANSACTION_STAGE_CHARGEBACK_APPROVED,
            self::TRANSACTION_STAGE_CREATE_CM_APPROVED,
            self::TRANSACTION_STAGE_CREATE_CM_CANCELLED,
            self::TRANSACTION_STAGE_CREATE_CM_DECLINED,
            self::TRANSACTION_STAGE_CREATE_CM_ERROR,
            self::TRANSACTION_STAGE_CREATE_CM_FAILED,
            self::TRANSACTION_STAGE_CREATE_CM_FILTERED,
            self::TRANSACTION_STAGE_DELETE_CM_APPROVED,
            self::TRANSACTION_STAGE_DELETE_CM_CANCELLED,
            self::TRANSACTION_STAGE_DELETE_CM_ERROR,
            self::TRANSACTION_STAGE_DELETE_CM_FAILED,
            self::TRANSACTION_STAGE_DELETE_CM_FILTERED,
            self::TRANSACTION_STAGE_DISPUTE_APPROVED,
            self::TRANSACTION_STAGE_FRAUD_APPROVED,
            self::TRANSACTION_STAGE_INQUIRE_CM_APPROVED,
            self::TRANSACTION_STAGE_INQUIRE_CM_CANCELLED,
            self::TRANSACTION_STAGE_INQUIRE_CM_ERROR,
            self::TRANSACTION_STAGE_INQUIRE_CM_FAILED,
            self::TRANSACTION_STAGE_INQUIRE_CM_FILTERED,
            self::TRANSACTION_STAGE_MFO_SCORING_APPROVED,
            self::TRANSACTION_STAGE_MFO_SCORING_DECLINED,
            self::TRANSACTION_STAGE_MFO_SCORING_ERROR,
            self::TRANSACTION_STAGE_PAN_ELIGIBILITY_APPROVED,
            self::TRANSACTION_STAGE_PAN_ELIGIBILITY_CANCELLED,
            self::TRANSACTION_STAGE_PAN_ELIGIBILITY_ERROR,
            self::TRANSACTION_STAGE_PAN_ELIGIBILITY_FAILED,
            self::TRANSACTION_STAGE_PAN_ELIGIBILITY_FILTERED,
            self::TRANSACTION_STAGE_PAN_ELIGIBILITY_REJECTED,
            self::TRANSACTION_STAGE_RETRIEVAL_APPROVED,
            self::TRANSACTION_STAGE_REVERSAL_APPROVED,
            self::TRANSACTION_STAGE_REVERSAL_DECLINED,
            self::TRANSACTION_STAGE_REVERSAL_ERROR,
            self::TRANSACTION_STAGE_REVERSAL_REJECTED,
            self::TRANSACTION_STAGE_SALE3D_END_APPROVED,
            self::TRANSACTION_STAGE_SALE3D_END_DECLINED,
            self::TRANSACTION_STAGE_SALE3D_END_ERROR,
            self::TRANSACTION_STAGE_SALE_APPROVED,
            self::TRANSACTION_STAGE_SALE_CANCELLED,
            self::TRANSACTION_STAGE_SALE_DECLINED,
            self::TRANSACTION_STAGE_SALE_ERROR,
            self::TRANSACTION_STAGE_SALE_FAILED,
            self::TRANSACTION_STAGE_SALE_FILTERED,
            self::TRANSACTION_STAGE_TRANSFER_APPROVED,
            self::TRANSACTION_STAGE_TRANSFER_CANCELLED,
            self::TRANSACTION_STAGE_TRANSFER_DECLINED,
            self::TRANSACTION_STAGE_TRANSFER_ERROR,
            self::TRANSACTION_STAGE_TRANSFER_FAILED,
            self::TRANSACTION_STAGE_TRANSFER_FILTERED,
            self::TRANSACTION_STAGE_UPDATE_CM_APPROVED,
            self::TRANSACTION_STAGE_UPDATE_CM_CANCELLED,
            self::TRANSACTION_STAGE_UPDATE_CM_DECLINED,
            self::TRANSACTION_STAGE_UPDATE_CM_ERROR,
            self::TRANSACTION_STAGE_UPDATE_CM_FAILED,
            self::TRANSACTION_STAGE_UPDATE_CM_FILTERED,
            self::TRANSACTION_STAGE_VOID_APPROVED,
            self::TRANSACTION_STAGE_VOID_ERROR,
            self::TRANSACTION_STAGE_VOID_REJECTED,
        ];
    }

    public static function getFinalStatuses(): array
    {
        return [
            self::STATUS_TRANSACTION_APPROVED,
            self::STATUS_TRANSACTION_DECLINED,
            self::STATUS_TRANSACTION_ERROR,
            self::STATUS_TRANSACTION_FILTERED,
        ];
    }

    /**
     * @param $status
     * @return bool
     */
    public static function isSuccessStatus($status): bool
    {
        return $status === self::STATUS_TRANSACTION_APPROVED;
    }

    /**
     * @param $status
     * @return bool
     */
    public static function isFinalErrorStatus($status): bool
    {
        $statuses = [
            self::STATUS_TRANSACTION_DECLINED,
            self::STATUS_TRANSACTION_ERROR,
            self::STATUS_TRANSACTION_FILTERED,
        ];
        return in_array($status, $statuses, true);
    }

    /**
     * @param $status
     * @return bool
     */
    public static function isFinalStatus($status): bool
    {
        return in_array($status, self::getFinalStatuses(), true);
    }

    /**
     * @param $transactionStage
     * @return bool
     */
    public static function isFinalStage($transactionStage): bool
    {
        return in_array($transactionStage, self::getTransactionFinalStages(), true);
    }
}
