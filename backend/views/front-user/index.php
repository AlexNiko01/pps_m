<?php

use backend\models\Node;
use backend\models\search\FrontUserSearch;
use webvimark\modules\UserManagement\components\GhostHtml;
use webvimark\modules\UserManagement\models\rbacDB\Role;
use webvimark\modules\UserManagement\models\User;
use webvimark\modules\UserManagement\UserManagementModule;
use yii\helpers\{
    Html, ArrayHelper
};
use yii\widgets\Pjax;
use webvimark\extensions\GridPageSize\GridPageSize;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var FrontUserSearch $searchModel
 */

$this->title = Yii::t('app', 'Users on the node') . ' - ' . Node::getCurrentNode()->getPrettyName();
$this->params['breadcrumbs'][] = $this->title;

$searchModel = new FrontUserSearch();
?>


<div class="row">
    <div class="col-sm-6">
        <p>
            <?= GhostHtml::a(
                '<i class="fa fa-plus"></i> ' . Yii::t('admin', 'Create'),
                ['create'],
                ['class' => 'btn btn-success']
            ) ?>

            <?= GhostHtml::a(
                '<i class="fa fa-check"></i> ' . Yii::t('admin', 'Assign'),
                ['assign'],
                ['class' => 'btn btn-info']
            ) ?>

        </p>
    </div>

    <div class="col-sm-6 text-right">
        <?= GridPageSize::widget(['pjaxId' => 'user-grid-pjax']) ?>
    </div>
</div>


<?php Pjax::begin([
    'id' => 'user-grid-pjax',
]) ?>

<?= GridView::widget([
    'id' => 'user-grid',
    'dataProvider' => $searchModel->searchOnSelectedNode(Yii::$app->request->getQueryParams()),
    'pager' => [
        'options' => ['class' => 'pagination pagination-sm'],
        'hideOnSinglePage' => true,
        'lastPageLabel' => '>>',
        'firstPageLabel' => '<<',
    ],
    'filterModel' => $searchModel,
    'layout' => '{items}<div class="row"><div class="col-sm-8">{pager}</div><div class="col-sm-4 text-right">{summary}</div></div>',
    'columns' => [
        ['class' => 'yii\grid\SerialColumn', 'options' => ['style' => 'width:10px']],

        [
            'attribute' => 'username',
            'value' => function (User $model) {
                return Html::a($model->username, ['details', 'id' => $model->id], ['data-pjax' => 0]);
            },
            'format' => 'raw',
        ],
        [
            'attribute' => 'gridRoleSearch',
            'filter' => ArrayHelper::map(Role::getAvailableRoles(Yii::$app->user->isSuperAdmin), 'name', 'description'),
            'value' => function (User $model) {
                return implode(', ', ArrayHelper::map($model->roles, 'name', 'description'));
            },
            'format' => 'raw',
            'visible' => User::hasPermission('viewUserRoles'),
        ],
        [
            'class' => 'webvimark\components\StatusColumn',
            'attribute' => 'status',
            'optionsArray' => [
                [User::STATUS_ACTIVE, UserManagementModule::t('back', 'Active'), 'success'],
                [User::STATUS_INACTIVE, UserManagementModule::t('back', 'Inactive'), 'warning'],
                [User::STATUS_BANNED, UserManagementModule::t('back', 'Banned'), 'danger'],
            ],
        ],
        [
            'value' => function (FrontUserSearch $model) {
                $out = '';
                $out .= GhostHtml::a(
                    Yii::t('admin', 'Revoke'),
                    ['revoke', 'nodeId' => Node::getCurrentNode()->id, 'userId' => $model->id],
                    [
                        'class' => 'btn btn-sm btn-warning',
                        'data-confirm' => Yii::t('admin', 'Are you sure?'),
                    ]
                );
                $out .= GhostHtml::a(
                    Yii::t('admin', 'Details'),
                    ['details', 'id' => $model->id],
                    [
                        'class' => 'btn btn-sm btn-primary',
                        'data-pjax' => 0,
                    ]
                );

                return $out;
            },
            'format' => 'raw',
            'contentOptions' => [
                'style' => 'width:1px; white-space:nowrap; padding:5px'
            ],
        ],
    ],
]); ?>

<?php Pjax::end() ?>
