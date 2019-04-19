<?php

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class BackendAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        '//maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css',
        'css/lte/AdminLTE.min.css',
        'css/lte/lte_fix.less',
        'css/lte/skins/skin-blue.min.css',
        'js/fancytree/skin-lion/ui.fancytree.min.css',
        'css/default.css',
        'css/site.css',
    ];
    public $js = [
        'js/lte/app.js',
        '//code.jquery.com/ui/1.11.4/jquery-ui.min.js',
        'js/fancytree/jquery.fancytree-all.min.js',
        'js/front.js',
        'js/jquery.countdown.min.js',
        'js/scripts'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];
}
