<?php
/**
 * @var $this yii\web\View
 * @var $model FrontNode
 */

use backend\models\{
    FrontNode, Node
};
use webvimark\modules\UserManagement\models\rbacDB\Role;
use yii\bootstrap\ActiveForm;
use yii\helpers\{
    ArrayHelper, Html
};

if ($model->isNewRecord) {
    $this->title = Yii::t('app', 'Create user on the node') . ' - ' . Node::getCurrentNode()->getPrettyName();
} else {
    $this->title = Yii::t('app', 'Edit user') . ' - ' . $model->username;
}
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>


<?php $form = ActiveForm::begin([
    'id' => 'create-user-form',
    'layout' => 'horizontal',
    'validateOnBlur' => false,
    'validateOnChange' => false,
]); ?>

<?php if ($model->scenario === 'changePassword'): ?>

    <?= $form->field($model, 'password')->passwordInput() ?>
    <?= $form->field($model, 'repeat_password')->passwordInput() ?>

<?php else: ?>


    <?= $form->field($model, 'username')->textInput(['maxlength' => 255, 'autofocus' => $model->isNewRecord ? true : false]) ?>

    <?php if ($model->isNewRecord): ?>
        <?= $form->field($model, 'password')->passwordInput() ?>
        <?= $form->field($model, 'repeat_password')->passwordInput() ?>
        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
                <button class="btn btn-xs btn-info" type="button" onclick="generatePassword()">Generate password</button>
                &nbsp;<span id="password"></span>
            </div>
        </div>
        <script>
            function generatePassword() {
                var password = '';
                var letters = 'abcdevghijklmnopqrstuvwxyxABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_';
                var passwordContainer = document.getElementById('password');

                for (var i = 0; i < 16; i++) {
                    password += letters.charAt((Math.random()*letters.length).toFixed()-1);
                }

                passwordContainer.innerHTML = password;
            }
        </script>
    <?php endif; ?>

    <?= $form->field($model, 'role_name')->radioList(ArrayHelper::map(Role::find()->all(), 'name', 'description')) ?>

<?php endif; ?>


<div class="form-group">
    <div class="btn-group col-sm-offset-3 col-sm-9">

        <?php if ($model->isNewRecord): ?>
            <?= Html::submitButton(
                '<i class="fa fa-check"></i> ' . Yii::t('admin', 'Create'),
                ['class' => 'btn btn-success']
            ) ?>

        <?php else: ?>
            <?= Html::submitButton(
                '<i class="fa fa-check"></i> ' . Yii::t('admin', 'Update'),
                ['class' => 'btn btn-info']
            ) ?>
        <?php endif; ?>

        <?php if ($model->isNewRecord): ?>
            <?= Html::a(Yii::t('admin', 'Cancel'), ['index'], ['class' => 'btn btn-default']) ?>

        <?php else: ?>
            <?= Html::a(Yii::t('admin', 'Cancel'), ['details', 'id' => $model->id], ['class' => 'btn btn-default']) ?>

        <?php endif; ?>
    </div>
</div>

<?php ActiveForm::end(); ?>
