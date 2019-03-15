<?php
/**
 * @var $this yii\web\View
 */

use backend\models\Node;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

$this->title = Yii::t('admin', 'Assign user to node') . ' - ' . Node::getCurrentNode()->getPrettyName();
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>


<?php $form = ActiveForm::begin([
    'id' => 'game-provider-form',
    'layout' => 'horizontal',
    'validateOnBlur' => false,
    'validateOnChange' => false,
]); ?>

<?= $form->field($model, 'node_id', ['template' => '{input}'])->hiddenInput([
    'value' => Node::getCurrentNode()->id,
]) ?>

<?= $form->field($model, 'user_id')
    ->dropDownList(
        Node::getCurrentNode()->getListOfUsersNotAssignedToNode(),
        ['prompt' => '']
    ) ?>


<div class="form-group">
    <div class="col-sm-offset-3 col-sm-9">

        <?= Html::submitButton(
            '<i class="fa fa-check"></i> ' . Yii::t('admin', 'Assign'),
            ['class' => 'btn btn-info']
        ) ?>

        <?= Html::a(Yii::t('admin', 'Cancel'), ['index'], ['class' => 'btn btn-default']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>
