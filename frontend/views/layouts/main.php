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
            <?= Html::a('Yii Framework 2.0 API Documentation', ['/site/index'], ['class'=>"navbar-brand"]) ?>

        </div>
        <div id="w82-collapse" class="collapse navbar-collapse">
            <ul id="w83" class="navbar-nav nav">
                <li>
                    <?= Html::a('Class reference', ['/site/index']) ?>
                </li>
            </ul>
            <div class="navbar-form navbar-left" role="search">
                <div class="form-group">
                    <input id="searchbox" type="text" class="form-control" placeholder="Search">
                </div>
            </div>
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
            <div class="col-md-9 api-content" role="main">
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

<script>jQuery(function ($) {
        var shiftWindow = function () {
            scrollBy(0, -50)
        };
        if (location.hash) setTimeout(shiftWindow, 1);
        window.addEventListener("hashchange", shiftWindow);
        var element = document.createElement("script");
        element.src = "/js/jssearch.index.js";
        document.body.appendChild(element);

        var searchBox = $('#searchbox');

// search when typing in search field
        searchBox.on("keyup", function (event) {
            var query = $(this).val();

            if (query == '' || event.which == 27) {
                $('#search-resultbox').hide();
                return;
            } else if (event.which == 13) {
                var selectedLink = $('#search-resultbox a.selected');
                if (selectedLink.length != 0) {
                    document.location = selectedLink.attr('href');
                    return;
                }
            } else if (event.which == 38 || event.which == 40) {
                $('#search-resultbox').show();

                var selected = $('#search-resultbox a.selected');
                if (selected.length == 0) {
                    $('#search-results').find('a').first().addClass('selected');
                } else {
                    var next;
                    if (event.which == 40) {
                        next = selected.parent().next().find('a').first();
                    } else {
                        next = selected.parent().prev().find('a').first();
                    }
                    if (next.length != 0) {
                        var resultbox = $('#search-results');
                        var position = next.position();

//              TODO scrolling is buggy and jumps around
//                resultbox.scrollTop(Math.floor(position.top));
//                console.log(position.top);

                        selected.removeClass('selected');
                        next.addClass('selected');
                    }
                }

                return;
            }
            $('#search-resultbox').show();
            $('#search-results').html('<li><span class="no-results">No results</span></li>');

            var result = jssearch.search(query);

            if (result.length > 0) {
                var i = 0;
                var resHtml = '';

                for (var key in result) {
                    if (i++ > 20) {
                        break;
                    }
                    resHtml = resHtml +
                        '<li><a href="' + result[key].file.u.substr(3) + '"><span class="title">' + result[key].file.t + '</span>' +
                        '<span class="description">' + result[key].file.d + '</span></a></li>';
                }
                $('#search-results').html(resHtml);
            }
        });

// hide the search results on ESC
        $(document).on("keyup", function (event) {
            if (event.which == 27) {
                $('#search-resultbox').hide();
            }
        });
// hide search results on click to document
        $(document).bind('click', function (e) {
            $('#search-resultbox').hide();
        });
// except the following:
        searchBox.bind('click', function (e) {
            e.stopPropagation();
        });
        $('#search-resultbox').bind('click', function (e) {
            e.stopPropagation();
        });

    });</script>
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
    /*
     $(".sourceCode a.show").toggle(function () {
     $(this).text($(this).text().replace(/show/,'hide'));
     $(this).parents(".sourceCode").find("div.code").show();
     },function () {
     $(this).text($(this).text().replace(/hide/,'show'));
     $(this).parents(".sourceCode").find("div.code").hide();
     });
     $("a.sourceLink").click(function () {
     $(this).attr('target','_blank');
     });
     */
    /*]]>*/
</script>

</body>
</html>
<?php $this->endPage() ?>
