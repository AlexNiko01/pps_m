<?php

use backend\models\Node;
use backend\models\search\FrontUserSearch;
use webvimark\modules\UserManagement\components\GhostHtml;
use webvimark\modules\UserManagement\models\rbacDB\Role;
use webvimark\modules\UserManagement\models\User;
use webvimark\modules\UserManagement\UserManagementModule;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\Pjax;
use webvimark\extensions\GridPageSize\GridPageSize;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var FrontUserSearch $searchModel
 */

$this->title = Yii::t('app', 'Users on the branch') . ' - ' . Node::getCurrentNode()->getPrettyName();
$this->params['breadcrumbs'][] = $this->title;

$searchModel = new FrontUserSearch();
?>


<div class="row">
    <div class="col-sm-6">
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
    'dataProvider' => $searchModel->searchOnBranch(Yii::$app->request->getQueryParams()),
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
            'attribute' => 'assignments',
            'filter' => Node::getCurrentNode()->getChildrenList(),
            'value' => function (FrontUserSearch $model) {
                return $model->getAssignments();
            },
            'format' => 'raw',
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
