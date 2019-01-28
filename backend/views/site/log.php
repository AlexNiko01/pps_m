<?php

/**
 * @var $folders array
 * @var $active_folder string
 * @var $groups array
 */

use backend\models\Node;
use yii\helpers\Html;

$this->title = "Log viewer: {$file}.log". ($version ? ".$version": '');

?>

<?=Node::hideBreadcrumbs()?>

<div class="row">
    <div class="col-md-12">
        <div class="btn-group">
            <?php foreach ($folders as $folder):?>
                <?php if ($active_folder == $folder):?>
                    <?=Html::a(ucfirst($folder), ['site/log', 'folder' => $folder], ['class' => 'btn btn-info btn-xs active'])?>
                <?php else:?>
                    <?=Html::a(ucfirst($folder), ['site/log', 'folder' => $folder], ['class' => 'btn btn-info btn-xs'])?>
                <?php endif;?>

            <?php endforeach;?>
        </div>
        <button type="button" class="btn btn-default btn-xs" onclick="$('.files').toggle()">Show files</button>
    </div>
    <div class="col-md-12 files" style="display: none; margin-top: 8px;">
        <?php foreach ($groups as $group => $files):?>
            <div>
                <p><?=$group?></p>
                <?php foreach ($files as $name => $versions):?>
                    <?php if (empty($versions)):?>

                        <?= Html::a($name, ['site/log', 'folder' => $active_folder, 'file' => $name], ['class' => 'btn btn-default btn-xs' . ($file == $name ? ' active' : '')])?>

                    <?php else:?>
                        <div class="dropdown" style="display: inline-block;">
                            <button class="btn btn-default dropdown-toggle btn-xs<?=  $file == $name ? ' active' : ''?>" type="button" id="<?="m-{$name}"?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                <?=$name?>
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="<?="m-{$name}"?>">
                                <li class="<?=$file == $name && $version === null ? ' active' : ''?>"><?= Html::a("v-0", ['site/log', 'folder' => $active_folder, 'file' => $name], ['class' => 'btn-xs'])?></li>

                                <?php foreach ($versions as $v):?>
                                    <li class="<?=$file == $name && $version == $v ? ' active' : ''?>"><?= Html::a("v-{$v}", ['site/log', 'folder' => $active_folder, 'file' => $name, 'v' => $v], ['class' => 'btn-xs'])?></li>
                                <?php endforeach;?>
                            </ul>
                        </div>
                    <?php endif;?>

                <?php endforeach;?>
            </div>
        <?php endforeach;?>
    </div>
    <div class="col-md-12">
        <pre style="margin-top: 12px;">
            <?= Html::encode($content)?>
        </pre>
    </div>
</div>