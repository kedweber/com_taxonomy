<?php
/**
 * ComTaxonomy
 *
 * @author      Dave Li <dave@moyoweb.nl>
 * @category    Nooku
 * @package     Socialhub
 * @subpackage  Taxonomy
 */

defined('KOOWA') or die('Protected resource');

class ComTaxonomyDatabaseBehaviorRelationable extends KDatabaseBehaviorAbstract
{
    public function getRelation($config = array())
    {
        $config = new KConfig($config);

        $relation = $this->getService('com://admin/taxonomy.model.taxonomies')
            ->row($this->id)
            ->table($this->getMixer()->getTable()->getBase())
            ->type($this->type)
            ->getItem();

        return $relation->getDescendants($config);
    }

    public function getAncestors($config = array())
    {
        $config = new KConfig($config);

        $relation = $this->getService('com://admin/taxonomy.model.taxonomies')
            ->row($this->id)
            ->table($this->getMixer()->getTable()->getBase())
            ->type($this->type)
            ->getItem();

        return $relation->getAncestors($config);
    }

    public function getSiblings($config = array())
    {
        $config = new KConfig($config);

        $relation = $this->getService('com://admin/taxonomy.model.taxonomies')
            ->row($this->id)
            ->table($this->getMixer()->getTable()->getBase())
            ->type($this->type)
            ->getItem();

        return $relation->getSiblings($config);
    }

    public function getTopics($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'filter' => array('type' => 'topic')
        ));

        return $this->getRelation($config);
    }

    public function getUsers($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'filter' => array('type' => 'user')
        ));

        return $this->getRelation($config);
    }

    public function getGroups($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'filter' => array('type' => 'group')
        ));

        return $this->getRelation($config);
    }

    public function getConversations($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'filter' => array('type' => 'conversation')
        ));

        return $this->getRelation($config);
    }

    public function getReplies($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'filter' => array('type' => 'reply')
        ));

        return $this->getRelation($config);
    }

    public function getAddresses($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'filter' => array('type' => 'address')
        ));

        return $this->getRelation($config);
    }

    public function getEvents($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'filter' => array('type' => 'event')
        ));

        return $this->getRelation($config);
    }

    public function getParent($config = array())
    {
        $config = new KConfig($config);

        $relation = $this->getService('com://admin/taxonomy.model.taxonomies')
            ->row($this->id)
            ->table($this->getMixer()->getTable()->getBase())
            ->getItem();


        return $relation->getParent($config);
    }

    public function getTaxonomy(/* $type = false */)
    {
        $taxonomies = $this->getService('com://admin/taxonomy.model.taxonomies')
            ->row($this->id)
            ->table($this->getMixer()->getTable()->getBase());

        // if ($type) {
        //     $taxonomies->type($type);
        // }

        $taxonomy = $taxonomies->getItem();

        if ($taxonomy->isNew() && $this->id) {
            try {
                $taxonomy->setData(array(
                    'row' => $this->id,
                    'table' => $this->getMixer()->getTable()->getBase(),
                    // 'type' => (string)$type,
                ))
                ->save();
            } catch ( KDatabaseException $e ) {
                if ( $e->getCode() == 1062 ) {
                    // duplicate entry
                    // this happens when there is a missing _taxonomy_relations record
                    $taxonomy->delete();
                    // re-add
                    $taxonomy->save();
                } else {
                    throw $e;
                }
            }
        }

        return $taxonomy;
    }

    protected function _afterTableInsert(KCommandContext $context)
    {
        $data = array(
            'row'       => $context->data->id,
            'table'     => $context->caller->getBase(),
        );

        if ($context->data->type)       $data['type']       = $context->data->type;
        if ($context->data->parent_id)  $data['parent_id']  = $context->data->parent_id;

        $taxonomy = $this->getService('com://admin/taxonomy.model.taxonomy')
            ->row($context->data->id)
            ->table($context->caller->getBase())
            ->getItem();

        $taxonomy->setData($data);
        $taxonomy->save();

        //TODO: Issue moving this in a special database behavior.
        if(is_numeric($context->data->parent_id) && $context->data->isRelationable()) {
            if($context->data->type == 'reply') {
                $parent = $context->data->getParent();
                $parent->comments = $parent->comments + 1;
                $parent->comment_on = $context->data->created_on;
                $parent->comment_by = $context->data->created_by;
                $parent->save();
            }

            if($context->data->type == 'user') {
                $parent = $context->data->getParent();
                $parent->users = $parent->users + 1;
                $parent->save();
            }
        }

        if(isset($this->getMixer()->getTable()->taxonomy_parent_types)){

            $types = $this->getMixer()->getTable()->taxonomy_parent_types;

        }else{

            $identifier = clone $this->getMixer()->getIdentifier();
            $identifier->path = array('model');
            $identifier->name = 'types';

            $types = $this->getService($identifier)->getList();

        }

        foreach($types as $type) {
            if(!$context->data->{$type->slug}) continue;

            $table = isset($type->table) ? $type->table : $this->getMixer()->getTable()->getBase();

            $parent = $this->getService('com://admin/taxonomy.model.taxonomy')
                ->row($context->data->{$type->slug})
                ->table($table)
                ->getItem();

            if(!$taxonomy->isAncestorOf($parent->id)) {
                $taxonomy->append($parent->id);
            }
        }
    }

    protected function _afterTableUpdate(KCommandContext $context)
    {
        $this->_afterTableInsert($context);
    }

    protected function _beforeTableDelete(KCommandContext $context)
    {
        $this->getService('com://admin/taxonomy.model.taxonomy')
             ->row($context->data->id)
             ->table($context->caller->getBase())
             ->getItem()
             ->delete();
    }
}
