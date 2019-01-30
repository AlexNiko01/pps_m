<?php

namespace backend\models;

use webvimark\modules\UserManagement\models\User;
use Yii;
use yii\caching\TagDependency;
use yii\helpers\{
    ArrayHelper, Html
};
use yii\web\{
    ConflictHttpException, ForbiddenHttpException
};

/**
 * This is the model class for table "node".
 * @property integer $id
 * @property integer $parent_id
 * @property integer $active
 * @property integer $type
 * @property string $name
 * @property string $domain
 * @property integer $verified
 * @property string $primary_email
 * @property string $note
 * @property string $prettyName
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $is_api_responses_validated
 * @property Node $parent
 * @property Node[] $nodes
 */
class Node extends \webvimark\components\BaseActiveRecord
{
    const TYPE_ROOT = 100;
    const TYPE_DOMAIN = 1;
    const TYPE_CLIENT = 2;
    const TYPE_TECHNICAL = 3;

    protected $_timestamp_enabled = true;
    protected $_enable_common_cache = true;

    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb() {
        return Yii::$app->db2;
    }
    /**
     * @return string
     */
    public static function hideBreadcrumbs()
    {
        return "<style>#node-breadcrumbs{display:none;}</style>";
    }

    /**
     * @return bool
     */
    public function isCurrentUserHasAccessToNode()
    {
        if (Yii::$app->user->isSuperadmin) {
            return true;
        }

        return NodeHasUser::mainCache(function () {
            return NodeHasUser::find()
                ->andWhere([
                    'user_id' => Yii::$app->user->id,
                    'node_id' => $this->getParentIds(),
                ])
                ->exists();
        });
    }

    /**
     * Check if node can be deleted. I.e. don't have children and assigned users and not root
     * @return bool
     */
    public function canBeDeleted()
    {
        if ($this->type == static::TYPE_ROOT || $this->hasChildren() || $this->hasAssignments()/* || $this->isVerified()*/) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isVerified()
    {
        return $this->verified == 1;
    }

    /**
     * @return bool
     */
    public function isApiValidated()
    {
        return $this->is_api_responses_validated == 1;
    }

    /**
     * Send request to the specified domain to verify that this is casexe casino
     * @return bool
     */
    public function sendVerificationRequest()
    {
        if (!$this->isVerified()) {
            $merchant = Merchant::find()
                ->where(['node_id' => $this->id,])
                ->asArray()
                ->one();

            $notify = Yii::$app->notify;

            if ($notify->query($merchant)) {
                $this->verified = 1;
                $this->save(false);
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this node has at least 1 assigned user
     * @return bool
     */
    public function hasAssignments()
    {
        return NodeHasUser::mainCache(function () {
            return NodeHasUser::find()
                ->andWhere([
                    'node_id' => $this->id,
                ])
                ->exists();
        });
    }

    /**
     * Check if this node has at least 1 child
     * @return bool
     */
    public function hasChildren()
    {
        return Node::mainCache(function () {
            return Node::find()
                ->andWhere([
                    'parent_id' => $this->id,
                ])
                ->exists();
        });
    }

    /**
     * List of nodes that can be parent for edited node. Used in front-node/createOrEdit
     * @return array
     */
    public static function getListOfAvailableParentNodes()
    {
        $node = Node::getCurrentNode();
        /** @var Node[] $nodes */
        $nodes = Node::mainCache(function () use ($node) {
            return Node::find()
                ->andWhere(['<>', 'type', Node::TYPE_DOMAIN])
                ->andWhere(['!=', 'type', $node->id])
                ->all();
        });

        $res = [];

        foreach ($nodes as $node) {
            $res[$node->id] = $node->getPrettyName();
        }

        return $res;
    }

    /**
     * If current node has been changed (let say in another tab) throw exception
     * @param int $nodeId
     * @throws \yii\web\ConflictHttpException
     */
    public static function ensureCurrentNodeHasNotBeenChanged($nodeId)
    {
        if ($nodeId != Node::getCurrentNode()->id) {
            throw new ConflictHttpException(Yii::t('admin', 'Your current node has been changed'));
        }
    }

    /**
     * Return recursively list children nodes in format ['node_id' => 'node_name']
     * @param bool $withCurrentNode
     * @param bool $onlyDomains
     * @throws \BadMethodCallException
     * @return array
     */
    public function getChildrenList($withCurrentNode = true, $onlyDomains = false)
    {
        if ($this->isNewRecord) {
            throw new \BadMethodCallException('Method not allowed for the new records');
        }

        $cacheKey = [
            __CLASS__,
            __FUNCTION__,
            $this->id,
            $withCurrentNode,
            $onlyDomains,
        ];

        $nodes = Yii::$app->cache->get($cacheKey);

        if ($nodes === false) {
            $nodes = [];

            if ($withCurrentNode) {
                $nodes[$this->id] = sprintf('[#%s] %s', $this->id, $this->name);
            }

            $this->_getChildrenListRecursive($this->id, $nodes, $onlyDomains);

            Yii::$app->cache->set($cacheKey, $nodes, Node::COMMON_CACHE_TIME, new TagDependency(['tags' => Node::getCacheTag()]));
        }

        return $nodes;
    }

    /**
     * @see Node::getChildrenList()
     * @param int $nodeId
     * @param array $nodes
     * @param bool $onlyDomains
     */
    protected function _getChildrenListRecursive($nodeId, array &$nodes, $onlyDomains)
    {
        /*$children = Node::mainCache(function () use ($nodeId, $onlyDomains) {
            $query = Node::find()
                ->active()
                ->andWhere([
                    'parent_id' => $nodeId
                ])
                ->asArray();

            if ($onlyDomains) {
                $query->andWhere(['type' => Node::TYPE_DOMAIN]);
            }

            return $query->all();
        });*/

        $children = Yii::$app->cache->getOrSet(['_getChildrenListRecursive', $nodeId, $onlyDomains], function () use ($nodeId, $onlyDomains) {
            $query = Node::find()
                ->active()
                ->andWhere([
                    'parent_id' => $nodeId
                ])
                ->asArray();

            if ($onlyDomains) {
                $query->andWhere(['type' => Node::TYPE_DOMAIN]);
            }

            return $query->all();
        }, Node::COMMON_CACHE_TIME, new TagDependency(['tags' => "node-{$nodeId}"]));

        foreach ($children as $child) {
            $nodes[$child['id']] = sprintf('[#%s] %s', $child['id'], $child['name']);

            $this->_getChildrenListRecursive($child['id'], $nodes, $onlyDomains);
        }
    }

    /**
     * @param bool $withCurrentNode
     * @return array
     * @throws \BadMethodCallException
     */
    public function getParentIds($withCurrentNode = true)
    {
        if ($this->isNewRecord) {
            throw new \BadMethodCallException('Method not allowed for the new records');
        }

        $cacheKey = __CLASS__ . __FUNCTION__ . $this->id;

        $nodes = Yii::$app->cache->get($cacheKey);

        if ($nodes === false) {
            $nodes = [];

            if ($withCurrentNode) {
                $nodes[$this->id] = $this->id;
            }

            $this->_getParentIdsRecursive($this->parent_id, $nodes);

            Yii::$app->cache->set($cacheKey, $nodes, Node::COMMON_CACHE_TIME, new TagDependency(['tags' => Node::getCacheTag()]));
        }

        return $nodes;
    }

    /**
     * @see Node::getParentIds()
     * @param int $parentId
     * @param array $nodes
     */
    protected function _getParentIdsRecursive($parentId, array &$nodes)
    {
        if ($parentId) {
            $nodes[$parentId] = $parentId;

            $newParentId = Node::mainCache(function () use ($parentId) {
                return Node::find()
                    ->select('parent_id')
                    ->active()
                    ->andWhere([
                        'id' => $parentId
                    ])
                    ->asArray()
                    ->scalar();
            });

            $this->_getParentIdsRecursive($newParentId, $nodes);
        }
    }

    /**
     * Get breadcrumbs string
     * @return string
     */
    public static function getNodeBreadcrumbs()
    {
        $result = '';

        $node = Node::getCurrentNode();

        if ($node) {
            $arrayOfNodes[$node['id']] = $node;

            static::_getBreadcrumbsRecursive($node['id'], $arrayOfNodes);

            $arr = array_reverse($arrayOfNodes, true);

            $tmp = array_keys($arr);
            $activeId = end($tmp);

            foreach ($arr as $nodeId => $node) {
                $class = ($nodeId == $activeId) ? 'active fancytree-breadcrumbs' : 'fancytree-breadcrumbs';

                $options = [
                    'class' => $class,
                    'data-node-id' => $nodeId,
                ];
                $result .= Html::beginTag('li', $options);
                $iconClass = ($node['type'] == Node::TYPE_DOMAIN) ? 'fancytree-ico-c' : 'fancytree-ico-cf';
                $result .= '<span class="' . $iconClass . '"><span class="fancytree-icon"></span></span>';
                $result .= Html::encode($node['name']);
                $result .= Html::endTag('li');
            }

        } else {
            $result .= Html::beginTag('li', ['class' => 'active fancytree-breadcrumbs', 'data-node-id' => $node['id']]);
            $result .= Yii::t('admin', "Select node");
            $result .= Html::endTag('li');
        }

        return $result;
    }

    /**
     * Helper for getBreadcrumbs()
     * @param int $parentId
     * @param array $arrayOfNodes
     */
    protected static function _getBreadcrumbsRecursive($parentId, &$arrayOfNodes)
    {
        /** @var Node $node */
        $node = Node::mainCache(function () use ($parentId) {
            return Node::find()
                ->active()
                ->andWhere(['id' => $parentId])
                ->one();
        });

        //		$node = Yii::app()->db->createCommand()
        //			->from('node')
        //			->andWhere('id = :node_id', array(':node_id'=>$parentId))
        //			->andWhere(['in', 'id', Yii::app()->session->get(Node::SESSION_PREFIX_AVAILABLE_NODE_ID, [])])
        //			->queryRow();

        if ($node && $node->isCurrentUserHasAccessToNode()) {
            $arrayOfNodes[$node['id']] = $node;

            static::_getBreadcrumbsRecursive($node['parent_id'], $arrayOfNodes);
        }
    }


    /**
     * Get ul > li Html tree of nodes
     *
     * @return string
     */
    public static function getHtmlTree()
    {
        $fullAccess = Yii::$app->user->isSuperadmin;

        $relations = [];
        $availableNodeIds = [];

        if (!$fullAccess) {
            $relations = NodeHasUser::mainCache(function () {
                return NodeHasUser::find()
                    ->asArray()
                    ->select('node_id')
                    ->andWhere(['user_id' => Yii::$app->user->id])
                    ->column();
            });

        }

        $result = '<ul>';
        static::getHtmlTreeRecursive($result, static::getTree(), $fullAccess, $relations, $availableNodeIds);
        $result .= '</ul>';

        //		Yii::app()->session->add(Node::SESSION_PREFIX_AVAILABLE_NODE_ID, $availableNodeIds);

        return $result;
    }

    /**
     * Used by getHtmlTree() function
     * @param string $result
     * @param array $nodes
     * @param bool $fullAccess
     * @param array $relations
     * @param array $availableNodeIds
     */
    protected static function getHtmlTreeRecursive(&$result, $nodes, $fullAccess, $relations, &$availableNodeIds)
    {
        foreach ($nodes as $node) {
            if ($fullAccess OR in_array($node['id'], $relations)) {
                $availableNodeIds[] = $node['id'];

                $result .= Html::beginTag('li', [
                    'id' => 'id' . $node['id'],
                    'data-node-type' => $node['type'],
                    //					'data-node-status' => $node['status'],
                    'class' => ($node['type'] == Node::TYPE_DOMAIN) ? '' : 'folder',
                    'data-node-binded' => ($node['type'] == static::TYPE_ROOT) ? 1 : $node['verified'],
                ]);
                $name = '[#' . $node['id'] . '] ' . Html::encode($node['name']);

                $result .= Html::a($name, [
                    '/front-node/switch-node',
                    'nodeId' => $node['id'],
                    'returnUrl' => Yii::$app->request->absoluteUrl,
                ]);

                if (isset($node['children'])) {
                    $result .= '<ul>';
                    static::getHtmlTreeRecursive($result, $node['children'], true, $relations, $availableNodeIds);
                    $result .= '</ul>';
                }
                $result .= Html::endTag('li');
            } else {
                if (isset($node['children'])) {
                    static::getHtmlTreeRecursive($result, $node['children'], $fullAccess, $relations, $availableNodeIds);
                }
            }

        }
    }

    /**
     * @return array
     */
    public static function getTree()
    {
        //		Yii::$app->cache->flush();
        $models = Node::mainCache(function () {
            return Node::find()
                ->active()
                ->asArray()
                ->orderBy('type DESC, id ASC')
                ->all();
        });

        $levels = array();
        $tree = array();
        $cur = array();

        foreach ($models as $model) {
            $cur = &$levels[$model['id']];

            $cur['parent_id'] = $model['parent_id'];
            $cur['id'] = $model['id'];
            $cur['name'] = $model['name'];
            $cur['type'] = $model['type'];
            $cur['verified'] = $model['verified'];

            if ($model['parent_id'] == 0)
                $tree[$model['id']] = &$cur;
            else
                $levels[$model['parent_id']]['children'][$model['id']] = &$cur;
        }

        return $tree;
    }

    /**
     * @return Node
     */
    public static function getRoot()
    {
        return Node::mainCache(function () {
            return Node::find()
                ->andWhere(['type' => Node::TYPE_ROOT])
                ->one();
        });
    }

    /**
     * @return string
     */
    public function getPrettyName()
    {
        return sprintf('[#%s] %s', $this->id, $this->name);
    }

    /**
     * Get current node by ID stored in session.
     * If ID not stored, than find node where user belongs to and store it's ID
     * @throws \yii\web\ForbiddenHttpException
     * @return Node
     */
    public static function getCurrentNode(): Node
    {
        $nodeId = Yii::$app->session->get('current_node_id');

        if ($nodeId === null) {
            if (Yii::$app->user->isSuperadmin) {
                $nodeId = self::getRoot()->id;
            } else {
                $nodeId = NodeHasUser::find()
                    ->select('node_id')
                    ->andWhere(['user_id' => Yii::$app->user->id])
                    ->limit(1)
                    ->asArray()
                    ->scalar();

                if (!$nodeId) {
                    throw new ForbiddenHttpException(Yii::t('admin', 'You are not assigned to any node'));
                }
            }
            self::setCurrentNode($nodeId);
        }

        /** @var Node $calledClass */
        $calledClass = get_called_class(); // For cache dependency on FrontNode

        $node = Node::mainCache(function () use ($nodeId, $calledClass) {
            return $calledClass::find()
                ->active()
                ->andWhere(['id' => $nodeId])
                ->one();
        });

        if (!$node) {
            Yii::$app->session->remove('current_node_id');

            return static::getCurrentNode();
        }

        return $node;
    }

    /**
     * @param int $id
     */
    public static function setCurrentNode($id)
    {
        Yii::$app->session->set('current_node_id', $id);
    }

    /**
     * @return array
     */
    public function getListOfUsersNotAssignedToNode()
    {
        $usersOnTheNode = NodeHasUser::find()
            ->select('user_id')
            ->andWhere(['node_id' => $this->id])
            ->asArray()
            ->column();

        $usersOnTheBranch = NodeHasUser::find()
            ->select('user_id')
            ->andWhere(['node_id' => array_keys($this->getChildrenList(false))])
            ->asArray()
            ->column();

        $users = User::find()
            ->select(['id', 'username'])
            ->andWhere(['not in', 'id', $usersOnTheNode])
            ->andWhere(['in', 'id', $usersOnTheBranch])
            ->andWhere(['<>', 'id', Yii::$app->user->id])
            ->andWhere([
                'superadmin' => 0,
                'status' => User::STATUS_ACTIVE,
            ])
            ->asArray()
            ->all();

        return ArrayHelper::map($users, 'id', 'username');
    }

    /**
     * @return array
     */
    public function getListOfUsersAssignedToNode()
    {
        $usersOnTheNode = NodeHasUser::mainCache(function () {
            return NodeHasUser::find()
                ->select('user_id')
                ->andWhere(['node_id' => $this->id])
                ->asArray()
                ->column();
        });

        $users = User::find()
            ->select(['id', 'username'])
            ->andWhere(['id' => $usersOnTheNode])
            ->andWhere([
                'superadmin' => 0,
                'status' => User::STATUS_ACTIVE,
            ])
            ->asArray()
            ->all();

        return ArrayHelper::map($users, 'id', 'username');
    }

    /**
     * @return Node[]
     */
    public static function getSideMenuItems()
    {
        $nodes = Node::find()
            ->andWhere([
                'type' => Node::TYPE_DOMAIN,
                'active' => 1,
            ])
            ->orderBy('sorter ASC')
            ->all();

        return $nodes;
    }

    /**
     * @return array
     */
    public static function getMenuItems()
    {
        $items = [];
        $nodes = Node::find()
            ->andWhere([
                'type' => Node::TYPE_DOMAIN,
                'active' => 1,
            ])
            ->orderBy('sorter ASC')
            ->all();

        foreach ($nodes as $node) {
            $items[] = [
                'label' => $node->name,
                'url' => ['/site/details', 'nodeId' => $node->id],
                'active' => Yii::$app->request->get('nodeId') == $node->id,
            ];
        }

        return $items;
    }

    /**
     * getTypeList
     * @return array
     */
    public static function getTypeList()
    {
        return array(
            static::TYPE_CLIENT => Yii::t('admin', 'Client'),
            static::TYPE_TECHNICAL => Yii::t('admin', 'Technical'),
            static::TYPE_DOMAIN => Yii::t('admin', 'Brand'),
            static::TYPE_ROOT => Yii::t('admin', 'Root'),
        );
    }

    /**
     * getTypeValue
     * @param string $val
     * @return string
     */
    public static function getTypeValue($val)
    {
        $ar = self::getTypeList();

        return isset($ar[$val]) ? $ar[$val] : $val;
    }


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'node';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'active', 'type', 'verified'], 'integer'],
            [['type', 'name'], 'required'],
            [['name', 'domain', 'primary_email'], 'string', 'max' => 255],
            [['name', 'domain', 'primary_email', 'note'], 'trim'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => 'Parent',
            'active' => 'Active',
            'type' => 'Type',
            'name' => 'Name',
            'domain' => 'Domain',
            'verified' => 'Verified',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Node::className(), ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNodes()
    {
        return $this->hasMany(Node::className(), ['parent_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMerchant()
    {
        return $this->hasMany(Merchant::className(), ['node_id' => 'id']);
    }


    /**
     * @inheritdoc
     * @return NodeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new NodeQuery(get_called_class());
    }

    /**
     * Used in flush cache after save and delete. So you can always can be sure that your cache is valid
     *
     * @return string
     */
    public static function getCacheTag()
    {
        return 'main_node_class_common_cache';
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->domain = rtrim($this->domain, '/');
            $this->name = rtrim($this->name, '/');

            return true;
        }

        return false;
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        TagDependency::invalidate(Yii::$app->cache, ["node-{$this->id}"]);
    }
}
