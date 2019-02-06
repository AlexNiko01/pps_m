<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.11.18
 * Time: 10:50
 */

namespace pps\payment;

interface IndividualSettingsInterface
{
    /**
     * method should return HTML with special settings markup (buttons, fields, etc...) in case PS
     * provide some setting which we can implement in our system
     * this elemets will be processing by UserPaymentSystemController::actionIndividualSettings($id) (via AJAX)
     *
     * @param array $params - additional parameters
     * @return string [path alias](guide:concept-aliases) (e.g. "@vendor/pps/pps-current/view/settings");
     */
    public static function getSettingsView(array $params = []): string;
    /**
     * handler for UserPaymentSystemController::actionIndividualSettings($id).
     * Implement of specific logic for import settings for current PS
     * @param string $actionName
     * @param array $params
     * @return mixed
     */
    public function runIndSettingsAction(string $actionName, array $params = []);
}
