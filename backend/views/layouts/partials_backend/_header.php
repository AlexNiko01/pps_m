<?php

use backend\widgets\timezone\Timezone;
use webvimark\modules\UserManagement\components\GhostHtml;
use webvimark\modules\UserManagement\UserManagementModule;
use yii\helpers\Html;

?>

<style>
    .version {
        text-align: center;
        color: #777;
        font-size: 12px;
    }
</style>

<header class="main-header">

    <?= Html::a(
        '<i class="fa fa-server"></i> ' . Yii::$app->name,
        Yii::$app->homeUrl,
        ['class' => 'logo', 'title' => 'Payment Processing Service']
    ) ?>

    <nav class="navbar navbar-static-top" role="navigation">

        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>

        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav" style="margin-top: 12px; width: 250px">
                <?= Timezone::widget() ?>
            </ul>

            <ul class="nav navbar-nav">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                        <i class="glyphicon glyphicon-user"></i>&nbsp;
                        <?= Yii::$app->user->username ?> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu" role="menu">
                        <li>
                            <?= GhostHtml::a(
                                '<i class="fa fa-random"></i> Change password',
                                ['/user-management/auth/change-own-password']
                            ) ?>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <?= Html::a(
                                '<i class="fa fa-power-off"></i> Logout',
                                ['/user-management/auth/logout'],
                                ['class' => 'btn btn-default btn-flat']
                            ) ?>
                        </li>
                        <li class="divider"></li>
                        <li class="version">Version: <?= Yii::$app->params['version'];?></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</header>