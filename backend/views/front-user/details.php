<?php
/**
 * @var $this yii\web\View
 * @var $model FrontUserSearch
 */

use backend\models\search\FrontUserSearch;
use webvimark\modules\UserManagement\components\GhostHtml;
use webvimark\modules\UserManagement\models\rbacDB\Role;
use webvimark\modules\UserManagement\models\User;
use yii\helpers\ArrayHelper;
use yii\widgets\DetailView;

$this->title = Yii::t('admin', 'User details');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

    <div class="row" style="margin-bottom: 10px">
        <div class="col-sm-6">
            <?= GhostHtml::a(
                '<i class="fa fa-pencil"></i> ' . Yii::t('admin', 'Edit'),
                ['edit', 'id' => $model->id],
                ['class' => 'btn btn-info']
            ) ?>

            <?= GhostHtml::a(
                '<i class="fa fa-random"></i> ' . Yii::t('admin', 'Change password'),
                ['change-password', 'id' => $model->id],
                ['class' => 'btn btn-primary']
            ) ?>
        </div>
        <div class="col-sm-6 text-right">
            <?= GhostHtml::a(
                '<i class="fa fa-trash"></i> ' . Yii::t('admin', 'Delete'),
                ['delete', 'id' => $model->id],
                [
                    'class' => 'btn btn-danger',
                    'data-confirm' => Yii::t('admin', 'Are you sure?'),
                ]
            ) ?>
        </div>
    </div>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'username',
        [
            'label' => Yii::t('admin', 'Roles'),
            'value' => implode('<br>', ArrayHelper::map(Role::getUserRoles($model->id), 'name', 'description')),
            'visible' => User::hasPermission('viewUserRoles'),
            'format' => 'raw',
        ],
        [
            'attribute' => 'assignments',
            'value' => $model->getAssignments(),
            'format' => 'raw',
        ],
        'created_at:datetime',
    ],
]) ?>