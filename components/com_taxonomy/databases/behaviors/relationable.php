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
    protected $_ancestors;

    protected $_descendants;

    protected function _initialize(KConfig $config)
    {
        $config->append(array(
            'auto_mixin' => true,
        ));

        parent::_initialize($config);
    }

    public function __construct(KConfig $config)
    {
        parent::__construct($config);

        $this->_ancestors   = $config->ancestors;
        $this->_descendants = $config->descendants;
    }

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
            ->getItem();

        return $relation->getAncestors($config);
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

    public function getTaxonomy()
    {
        $cache  = JFactory::getCache('com_taxonomy', '');
        $key = $this->_getKey();

        if($data = $cache->get($key)) {
            $taxonomy = $this->getService('com://admin/taxonomy.database.row.taxonomy');
            $taxonomy->setData(unserialize($data));
        } else {
            $taxonomy = $this->getService('com://admin/taxonomy.model.taxonomies')
                ->row($this->id)
                ->table($this->getMixer()->getTable()->getBase())
                ->getItem();

            $cache->store(serialize($taxonomy->getData()), $key);
        }

        return $taxonomy;
    }

    /**
     * @param KCommandContext $context
     */
    protected function _beforeTableSelect(KCommandContext $context)
    {
        $table  = $context->caller;
        $query  = $context->query;

        if($query) {
            $query->select('taxonomies.taxonomy_taxonomy_id AS taxonomy_taxonomy_id');
            $query->join('INNER', '#__taxonomy_taxonomies AS taxonomies', array(
                'taxonomies.row = tbl.'.$table->getIdentityColumn().'',
                'taxonomies.table = LOWER("'.strtoupper($table->getBase()).'")'
            ));

            $query->select('GROUP_CONCAT(DISTINCT(crumbs.ancestor_id) ORDER BY crumbs.level DESC SEPARATOR \',\') AS ancestors');
            $query->join('inner', '#__taxonomy_taxonomy_relations AS crumbs', 'crumbs.descendant_id = taxonomies.taxonomy_taxonomy_id');
            $query->group('taxonomies.taxonomy_taxonomy_id');
        }
    }

    protected function _afterTableInsert(KCommandContext $context)
    {
        $identifier = clone $this->getMixer()->getIdentifier();

        //Fix for identity columns that are none incremental.
        $context->data->id = $context->data->id ? $context->data->id : $context->data->{$identifier->package.'_'.$identifier->name.'_id'};

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

        //TODO: Make it possible to save relation both ways.
        foreach($this->_ancestors as $ancestor) {
            if(isset($context->data->{$ancestor})) {
                $relations = $taxonomy->getAncestors(array('filter' => array('type' => KInflector::singularize($ancestor))));

                if($relations->getIds('taxonomy_taxonomy_id')) {
                    $this->getService('com://admin/taxonomy.model.taxonomy_relations')->ancestor_id($relations->getIds('taxonomy_taxonomy_id'))->descendant_id(array($taxonomy->id))->getList()->delete();
                }

                if(KInflector::isPlural($ancestor)) {
                    foreach($context->data->{$ancestor} as $relation) {
                        if($relation) {
                            $row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($relation)->getItem();

                            $taxonomy->append($row->id);
                        }
                    }
                } else {
                    if($context->data->{$ancestor}) {
                        $row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($context->data->{$ancestor})->getItem();

                        $taxonomy->append($row->id);
                    }
                }
            }
        }

        foreach($this->_descendants as $descendant) {
            if(isset($context->data->{$descendant})) {
                $relations = $taxonomy->getDescendants(array('filter' => array('type' => KInflector::singularize($descendant))));

                if($relations->getIds('taxonomy_taxonomy_id')) {
                    $this->getService('com://admin/taxonomy.model.taxonomy_relations')->ancestor_id($taxonomy->id)->descendant_id($relations->getIds('taxonomy_taxonomy_id'))->getList()->delete();
                }

                if(KInflector::isPlural($descendant)) {
                    foreach($context->data->{$descendant} as $relation) {
                        if($relation) {
                            $row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($relation)->getItem();

                            $row->append($taxonomy->id);
                        }
                    }
                } else {
                    if($context->data->{$descendant}) {
                        $row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($context->data->{$descendant})->getItem();

                        $row->append($taxonomy->id);
                    }
                }
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

    /**
     * Generate a cache key
     *
     * The key is based on the identity column, table.
     *
     * @return 	string
     */
    protected function _getKey()
    {
        $key = md5($this->id .':'. $this->getMixer()->getTable()->getBase());

        return $key;
    }

    /**
     * @return mixed
     */
    public function getRelations()
    {
        return $this->_ancestors;
    }
}