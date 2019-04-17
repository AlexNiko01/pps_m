<?php

namespace common\components\behavior;


use yii\base\Behavior;
use yii\base\Model;

class AddAuthLog extends Behavior
{

    public function events()
    {
        return [
            Model::EVENT_BEFORE_VALIDATE => 'addAuthLog',
        ];
    }


}