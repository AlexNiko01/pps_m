<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 20.11.18
 * Time: 11:23
 */

use yii\helpers\Html;

?>
<div class="individual-settings" style="margin-bottom: 20px">
    <?= Html::button('<i class="fa fa-cloud-download"></i> Create wallet', ['id' => 'create-wallet', 'class' => 'btn btn-default']) ?>
    <?= Html::button('<i class="fa fa-gavel"></i> Get wallet balance', ['id' => 'get-balance', 'class' => 'btn btn-default']) ?>
    <div class="allertInfo"></div>
</div>
<hr style="margin-bottom: 15px">

<script>

    window.onload = function () {
        let userPsId = $('#configure-payment-system').attr('data-user-ps-id');
        let alertDiv = $('.allertInfo');
        $('#create-wallet').on('click', function () {
            let currency = $('#currency-tab li.active a').text();
            if (currency === 'common') {
                alertDiv.prepend(alertRender('danger', 'Please, chose the currency'));
                return;
            }
            let walletInput = $('#configure-payment-system .tab-content .tab-pane.active input:first');
            $.ajax({
                url: '/user-payment-system/individual-settings?id=' + userPsId,
                type: "POST",
                data: {
                    "actionName": "generateWallet",
                    "params": {
                        "currency": currency
                    }
                },
                dataType: 'json',
                success: function (response) {
                    let alertClass = 'success';
                    if (response.error === 'ok') {
                        walletInput.val(response.data.wallet);
                    } else {
                        alertClass = 'danger';
                    }
                    alertDiv.prepend(alertRender(alertClass, response.message));
                },
                error: function (jqXHR) {
                    alertDiv.prepend(alertRender('danger', 'Processing error: ' + jqXHR.responseText));
                },
            });
        });

        $('#get-balance').on('click', function () {
            let currency = $('#currency-tab li.active a').text();
            let wallet = $('#configure-payment-system .tab-content .tab-pane.active input:first').val();
            $.ajax({
                url: '/user-payment-system/individual-settings?id=' + userPsId,
                type: "POST",
                data: {
                    "actionName": "getBalance",
                    "params": {
                        "currency": currency,
                        "wallet": wallet
                    }
                },
                dataType: 'json',
                success: function (response) {
                    let alertClass = 'success';
                    if (response.error === 'ok') {
                        alertDiv.prepend(alertRender(alertClass, 'Current balance: ' + response.data.balance));
                    } else {
                        alertClass = 'danger';
                        alertDiv.prepend(alertRender(alertClass, response.message));
                    }
                },
                error: function (jqXHR) {
                    alertDiv.find('.alert').remove();
                    alertDiv.prepend(alertRender('danger', 'Processing error: ' + jqXHR.responseText));
                },
            });
        });
    };

    /**
     * @var type - can be success, info, warning, danger, primary, secondary, light, dark
     * */
    function alertRender(type, message) {
        $('.allertInfo').find('.alert').remove();
        return '<div class="alert alert-' + type + ' fade in alert-dismissible show" style="margin-top: 15px">\n' +
            ' <button type="button" class="close" data-dismiss="alert" aria-label="Close">\n' +
            '    <span aria-hidden="true" style="font-size:20px">×</span>\n' +
            '  </button>' + message +
            '</div>'
    }

</script>