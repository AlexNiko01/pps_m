<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */

/* @var $exception Exception */

$this->title = $name;
?>
<style>
    html,
    body,
    .content {
        height: 100%;
        margin: 0;
    }

    h1 {
        color: #e53935
    }

    .site-error-wrap {
        display: table;
        height: 100%;
        width: 100%;
    }

    .site-error {
        text-align: center;
        position: relative;
        display: table-cell;
        vertical-align: middle;
        padding-bottom: 180px;
    }

    .pulse {
        vertical-align: middle;
        position: absolute;
        top: calc(50% + 45px);
        left: 50%;
        margin: -5px 0 0 -5px;
        width: 100px;
        height: 100px;
        text-align: center;
        border-radius: 999px;
        font-size: 24px;
        -webkit-transform: translate(-50%, -50%);
        -moz-transform: translate(-50%, -50%);
        -ms-transform: translate(-50%, -50%);
        -o-transform: translate(-50%, -50%);
        transform: translate(-50%, -50%);
        display: table;
    }

    .pulse::after {
        content: "";
        position: absolute;
        left: 50%;
        top: 50%;
        width: 10px;
        height: 10px;
        margin: -5px 0 0 -5px;
        background: rgba(66, 165, 245, 0.63);
        opacity: 0;
        border-radius: 999px;
        -webkit-animation: animation-pulse 800ms linear infinite;
        -moz-animation: animation-pulse 800ms linear infinite;
        -o-animation: animation-pulse 800ms linear infinite;
        animation: animation-pulse 800ms linear infinite;
    }

    @-webkit-keyframes animation-pulse {
        0% {
            opacity: 0;
        }
        50% {
            opacity: 1;
            -webkit-transform: scale(10, 10);
        }
        100% {
            opacity: 0;
            -webkit-transform: scale(20, 20);
        }
    }

    @-moz-keyframes animation-pulse {
        0% {
            opacity: 0;
        }
        50% {
            opacity: 1;
            -moz-transform: scale(10, 10);
        }
        100% {
            opacity: 0;
            -moz-transform: scale(20, 20);
        }
    }

    @keyframes animation-pulse {
        0% {
            opacity: 0;
        }
        50% {
            opacity: 1;
            transform: scale(10, 10);
        }
        100% {
            opacity: 0;
            transform: scale(20, 20);
        }
    }

    .clock {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }
</style>
<div class="site-error-wrap">
    <div class="site-error">
        <h1>You are not allowed to this page</h1>
        <h2>next try through...</h2>
        <div class="pulse">
            <span id="clock" class="clock"></span>
        </div>
    </div>

</div>
<script src="https://code.jquery.com/jquery-3.4.0.slim.min.js"
        integrity="sha256-ZaXnYkHGqIhqTbJ6MB4l9Frs/r7U4jlx7ir8PJYBqbI="
        crossorigin="anonymous"></script>

<script src="/js/jquery.countdown.min.js"></script>
<script>
    (function ($) {
        $('#clock').countdown("<?php echo $unblockingTime ?? ''; ?>", function (event) {
            $(this).html(event.strftime('%H:%M:%S'));
        });
    })(jQuery);

</script>