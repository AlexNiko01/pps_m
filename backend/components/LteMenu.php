<?php

namespace backend\components;

use webvimark\modules\UserManagement\components\GhostMenu;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

class LteMenu extends GhostMenu
{
    public $encodeLabels = false;
    public $activateParents = true;

    public $defaultIcon = '<i class="fa fa-caret-right"></i>';

    public $submenuTemplate = "\n<ul class='treeview-menu'>\n{items}\n</ul>\n";
    public $options = [
        'class' => 'sidebar-menu'
    ];

    /**
     * Renders the content of a menu item.
     * Note that the container and the sub-menus are not rendered here.
     * @param array $item the menu item to be rendered. Please refer to [[items]] to see what data might be in the item.
     * @return string the rendering result
     */
    protected function renderItem($item)
    {
        $icon = isset($item['icon']) ? $item['icon'] : $this->defaultIcon;

        $label = $icon . '<span>' . $item['label'] . '</span>';

        if (isset($item['url'])) {
            $template = ArrayHelper::getValue($item, 'template', $this->linkTemplate);

            return strtr($template, [
                '{url}' => Html::encode(Url::to($item['url'])),
                '{label}' => $label,
            ]);
        } else {
            $template = ArrayHelper::getValue($item, 'template', $this->labelTemplate);

            return strtr($template, [
                '{label}' => $label,
            ]);
        }
    }
}