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

		$join_taxonomy = true;

		//Check if the from table is the same as the current table.
		foreach($query->from as $from) {
			if (strpos($from, $table->getBase()) === false) {
				$join_taxonomy = false;
			}
		}

		if($query && $join_taxonomy) {
			$query->join('INNER', '#__taxonomy_taxonomies AS taxonomies', array(
				'taxonomies.row = tbl.'.$table->getIdentityColumn().'',
				'taxonomies.table = LOWER("'.strtoupper($table->getBase()).'")'
			));
			$query->select('taxonomies.ancestors AS ancestors');
			$query->select('taxonomies.descendants AS descendants');
			$query->select('taxonomies.taxonomy_taxonomy_id AS taxonomy_taxonomy_id');

            $query->select('GROUP_CONCAT(DISTINCT(as.ancestor_id) ORDER BY as.level DESC SEPARATOR \',\') AS ancestor_ids');
            $query->select('GROUP_CONCAT(DISTINCT(ds.descendant_id) ORDER BY as.level DESC SEPARATOR \',\') AS descendant_ids');
            $query->join('inner', '#__taxonomy_taxonomy_relations AS as', 'as.descendant_id = taxonomies.taxonomy_taxonomy_id');
            $query->join('inner', '#__taxonomy_taxonomy_relations AS ds', 'ds.ancestor_id = taxonomies.taxonomy_taxonomy_id');
            $query->group('taxonomies.taxonomy_taxonomy_id');
		}
    }

	protected function _afterTableSelect(KCommandContext $context)
	{
//		if($context->data instanceof KDatabaseRowsetDefault) {
//			foreach($context->data as $row) {
//				$ancestors = json_decode($row->ancestors);
//
//				foreach($this->_ancestors as $key => $ancestor) {
//					if($ancestors->{$key}) {
//						$row->{$key} = $this->getService($ancestor['identifier'])->id($ancestors->{$key})->getList();
//					}
//				}
//			}
//		}

		if($context->data instanceof KDatabaseRowDefault) {
			$ancestors = json_decode($context->data->ancestors);

			foreach($this->_ancestors as $key => $ancestor) {
				if(is_object($ancestors->{$key})) {

					$identifier = new KServiceIdentifier($ancestor->identifier);
					$identifier->path = array('database', 'row');
					$identifier->name = KInflector::singularize($identifier->name);

					$context->data->{$key} = $this->getService($identifier)->setData($ancestors->{$key})->toArray();
				} elseif($ancestors->{$key}) {
					$context->data->{$key} = $this->getService($ancestor['identifier'])->id($ancestors->{$key})->getList();
				}
			}
		}

//		if($context->data instanceof KDatabaseRowsetDefault) {
//			foreach($context->data as $row) {
//				foreach($this->_ancestors as $name => $ancestor) {
//					$taxonomy = $this->getService('com://admin/taxonomy.model.taxonomies')->id($row->taxonomy_taxonomy_id)->getItem();
//
//					$ids = $taxonomy->getAncestors(array('filter' => array('type' => KInflector::singularize($name))))->getIds();
//
//					if($ids) {
//						$row->{$name} = $this->getService($ancestor['identifier'])->id($ids)->getList();
//					}
//				}
//			}
//		}

//		if($context->data instanceof KDatabaseRowDefault) {
//			foreach($this->_ancestors as $name => $ancestor) {
//
//				$taxonomy = $this->getService('com://admin/taxonomy.model.taxonomies')->id($context->data->taxonomy_taxonomy_id)->getItem();
//
//				$ids = $taxonomy->getAncestors(array('filter' => array('type' => KInflector::singularize($name))))->getIds();
//
//				if($ids) {
//					$context->data->{$name} = $this->getService($ancestor['identifier'])->id($ids)->getList();
//				}
//			}
//		}
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