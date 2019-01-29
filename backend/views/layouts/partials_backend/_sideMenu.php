<?php
/**
 * @var $this yii\web\View
 */

use backend\components\LteMenu;
use webvimark\modules\UserManagement\UserManagementModule;

?>

<?= LteMenu::widget([
    'items' => [
        [
            'label' => 'Dashboard',
            'icon' => '<i class="fa fa-home"></i>',
            'url' => ['/site/index'],
        ],
        [
            'label' => 'Node management',
            'icon' => '<i class="fa fa-plus-square-o"></i>',
            'url' => ['/front-node/details'],
        ],
        [
            'label' => 'Users',
            'icon' => '<i class="fa fa-users"></i>',
            'url' => '#',
            'items' => [
                [
                    'label' => 'On selected node',
                    'url' => ['/front-user/index'],
                ],
                [
                    'label' => 'On branch',
                    'url' => ['/front-user/on-branch'],
                ],
            ],
        ],

        '999998' => [
            'label' => 'Mega users',
            'icon' => '<i class="fa fa-balance-scale"></i>',
            'url' => '#',
            'items' => UserManagementModule::menuItems(),
        ],

    ],
]);
