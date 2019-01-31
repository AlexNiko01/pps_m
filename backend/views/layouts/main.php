<?php

use backend\assets\BackendAsset;
use backend\models\Node;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

/* @var $this \yii\web\View */
/* @var $content string */

BackendAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?= Html::csrfMetaTags() ?>

    <title><?= Html::encode($this->title) ?></title>

    <?php $this->head() ?>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<?php $this->beginBody() ?>

<div class="wrapper">
    <?= $this->render('partials_backend/_header') ?>

    <aside class="main-sidebar">
        <section class="sidebar">
            <?= $this->render('partials_backend/_sideMenu') ?>

        </section>
    </aside>

    <div class="content-wrapper" style="min-height: 100vh">
        <div class="tree-container-wrapper">

            <ul class="breadcrumb" id="node-breadcrumbs">
                <?= Node::getNodeBreadcrumbs() ?>
            </ul>

            <div id="node-tree-container" style="display: none">
                <input class="form-control" id="tree-search"
                       placeholder="<?php echo Yii::t('admin', "Search by name or #ID. Press ESC to close."); ?>"
                       type="text"/>
                <div id="node-tree">
                    <?= Node::getHtmlTree(); ?>
                </div>
            </div>

        </div>

        <section class="content-header">
            <h1>
                <?= Html::encode($this->title) ?>
            </h1>

            <?=
            Breadcrumbs::widget(
                [
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]
            ); ?>
        </section>

        <section class="content">
            <div class="panel panel-default">
                <div class="panel-body">
                    <?= $content ?>
                </div>
            </div>
        </section>

    </div>

</div>

<? //= \odaialali\yii2toastr\ToastrFlash::widget([
//    'options' => [
//        'positionClass' => 'toast-bottom-left'
//    ]
//]); ?>

<?php $this->endBody() ?>
<script>
    console.log(atob('JWMgR08gQVdBWSEhIQ=='), "font-size: 40px;color:#ff3939; text-shadow: -1px 0 black, 0 1px black, 1px 0 black, 0 -1px black;text-align:center");
</script>
</body>
</html>
<?php $this->endPage() ?>
