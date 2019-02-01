<?php

namespace backend\controllers;

use backend\models\Node;
use webvimark\components\BaseController;
use Yii;
use yii\web\{
    Response, ForbiddenHttpException
};

/**
 * Class FrontNodeController
 * @package backend\controllers
 */
class FrontNodeController extends BaseController
{
    const PRIVATE_KEY_LENGTH = 32;
    const PUBLIC_KEY_LENGTH = 16;

    public $freeAccessActions = ['switch-node'];


    /**
     * @param $nodeId
     * @param $returnUrl
     * @return Response
     * @throws ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionSwitchNode($nodeId, $returnUrl)
    {
        $node = Node::findOneOrException($nodeId);

        if (!$node->isCurrentUserHasAccessToNode()) {
            throw new ForbiddenHttpException(Yii::t('admin', 'You have no access to this node'));
        }

        Node::setCurrentNode($nodeId);

        return $this->redirect($returnUrl);
    }


}
