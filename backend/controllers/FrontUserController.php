<?php

namespace backend\controllers;

use backend\models\{
    FrontUser, Node, NodeHasUser
};
use backend\models\search\FrontUserSearch;
use webvimark\components\BaseController;
use webvimark\modules\UserManagement\models\rbacDB\Role;
use webvimark\modules\UserManagement\models\User;
use yii\web\{
    ConflictHttpException, NotFoundHttpException
};
use Yii;

/**
 * Class FrontUserController
 * @package backend\controllers
 */
class FrontUserController extends BaseController
{
    /**
     * Users on the current node
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @return string
     */
    public function actionOnBranch()
    {
        return $this->render('onBranch');
    }

    /**
     * @return string|\yii\web\Response
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionCreate()
    {
        $model = new FrontUser();
        $model->scenario = 'newUser';

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            NodeHasUser::assignUser(Node::getCurrentNode()->id, $model->id);

            User::assignRole($model->id, $model->role_name);

            return $this->redirect(['details', 'id' => $model->id]);
        }

        return $this->render('createOrEdit', compact('model'));
    }

    /**
     * @param int $id User ID
     * @throws \yii\web\NotFoundHttpException
     * @return string
     */
    public function actionEdit($id)
    {
        $model = FrontUser::findOne($id);

        if (!$model) {
            throw new NotFoundHttpException('User not found');
        }

        $currentRole = @array_keys(Role::getUserRoles($model->id))[0];
        $model->role_name = $currentRole;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if ($currentRole != $model->role_name) {
                User::revokeRole($model->id, $currentRole);
                User::assignRole($model->id, $model->role_name);
            }

            return $this->redirect(['details', 'id' => $model->id]);
        }

        return $this->render('createOrEdit', compact('model'));
    }

    /**
     * @param int $id User ID
     * @throws \yii\web\NotFoundHttpException
     * @return string
     */
    public function actionChangePassword($id)
    {
        /** @var FrontUser $model */
        $model = FrontUser::findOne($id);
        $model->scenario = 'changePassword';

        if (!$model) {
            throw new NotFoundHttpException('User not found');
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['details', 'id' => $model->id]);
        }

        return $this->render('createOrEdit', compact('model'));
    }

    /**
     * @param int $id User ID
     * @throws \yii\web\NotFoundHttpException
     * @return string
     */
    public function actionDetails($id)
    {
        /** @var FrontUserSearch $model */
        $model = FrontUserSearch::findOne($id);

        if (!$model) {
            throw new NotFoundHttpException('User not found');
        }

        return $this->render('details', compact('model'));
    }

    /**
     * @param int $id User ID
     * @throws \yii\web\NotFoundHttpException
     * @return string
     */
    public function actionDelete($id)
    {
        /** @var FrontUserSearch $model */
        $model = FrontUserSearch::findOne($id);

        if (!$model) {
            throw new NotFoundHttpException('User not found');
        }

        $model->delete();

        Yii::$app->session->setFlash('success', Yii::t('admin', 'Done'));

        return $this->redirect(['index']);
    }

    /**
     * Assign user to the current node
     * @return string|\yii\web\Response
     * @throws \yii\web\ConflictHttpException
     */
    public function actionAssign()
    {
        $model = new NodeHasUser();
        $model->scenario = 'frontAssign';

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            Node::ensureCurrentNodeHasNotBeenChanged($model->node_id);

            if ($model->user_id == Yii::$app->user->id) {
                throw new ConflictHttpException(Yii::t('admin', 'You can\'t assign yourself'));
            }

            $model->save(false);

            Yii::$app->session->setFlash('success', Yii::t('admin', 'Done'));

            return $this->redirect(['index']);
        }

        return $this->render('assign', compact('model'));
    }

    /**
     * Assign user to the current node
     * @param int $userId
     * @param int $nodeId
     * @throws \yii\web\ConflictHttpException
     * @return string|\yii\web\Response
     */
    public function actionRevoke($userId, $nodeId)
    {
        Node::ensureCurrentNodeHasNotBeenChanged($nodeId);

        if ($userId == Yii::$app->user->id) {
            throw new ConflictHttpException(Yii::t('admin', 'You can\'t revoke yourself'));
        }

        $numberOfAssignments = NodeHasUser::find()
            ->andWhere(['user_id' => $userId])
            ->limit(2)
            ->count();

        if ($numberOfAssignments > 1) {
            NodeHasUser::deleteIfExists([
                'node_id' => $nodeId,
                'user_id' => $userId,
            ]);

            Yii::$app->session->setFlash('success', Yii::t('admin', 'Done'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('admin', 'This is the last assignment for this user. Last assignment cannot be revoked'));
        }

        return $this->redirect(['index']);
    }
} 