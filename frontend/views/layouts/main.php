<?php

/* @var $this \yii\web\View */

/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use frontend\assets\AppAsset;
use common\widgets\Alert;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<div class="wrap">
    <nav id="w82" class="navbar-inverse navbar-fixed-top navbar">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#w82-collapse"><span
                        class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span></button>
            <?= Html::a('Monitoring Documentation', ['/site/index'], ['class' => "navbar-brand"]) ?>

        </div>
    </nav>
    <div id="search-resultbox" style="display: none;" class="modal-content">
        <ul id="search-results">
        </ul>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <?php echo $this->render('navigation'); ?>
            </div>
            <div class="col-md-9 api-content" role="main" id="apiContent">
                <?= $content ?>
            </div>

        </div>
    </div>
</div>

<footer class="footer">
    <p class="pull-right">
        <small>Page generated on Tue, 26 Mar 2019 11:15:29 +0000</small>
    </p>
    Powered by <a href="http://www.yiiframework.com/" rel="external">Yii Framework</a>
</footer>

<?php $this->endBody() ?>


<script type="text/javascript">
    /*<![CDATA[*/
    jQuery("a.toggle").on('click', function () {
        var $this = $(this);
        if ($this.hasClass('properties-hidden')) {
            $this.text($this.text().replace(/Show/, 'Hide'));
            $this.parents(".summary").find(".inherited").show();
            $this.removeClass('properties-hidden');
        } else {
            $this.text($this.text().replace(/Hide/, 'Show'));
            $this.parents(".summary").find(".inherited").hide();
            $this.addClass('properties-hidden');
        }

        return false;
    });
</script>

</body>
</html>
<?php $this->endPage() ?>
