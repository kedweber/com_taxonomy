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
		}

		if($query && !$query->count) {
			foreach($query->order as $order) {
				foreach($this->getRelations() as $relation) {
					if($relation instanceof KConfig) {
						$columns = array_keys($relation->toArray());

						if(in_array(str_replace('tbl.', null, $order['column']), $columns)) {
							$query->select('SUBSTRING_INDEX(SUBSTR(ancestors,LOCATE(\'"'.strtoupper($order['column']).'":"\',ancestors)+CHAR_LENGTH(\'"'.strtoupper($order['column']).'":"\')),\'"\', 1) AS '.$order['column']);
							//TODO: Do we need to filter no values?
							$query->having($order['column'].' > ""');
						}
					}
				}
			}
		}
    }

	/**
	 * @param KCommandContext $context
	 */
	protected function _afterTableSelect(KCommandContext $context)
	{
		// TODO: Magic Call this?
		if($context->data instanceof KDatabaseRowsetDefault) {
			foreach($context->data as $row) {
				$this->__setRelations($row);
			}
		}

		if($context->data instanceof KDatabaseRowDefault) {
			$this->__setRelations($context->data);
		}
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
	 * @return KConfig
	 */
	public function getRelations()
	{
		$config = new KConfig();
		$config->append(array(
			'ancestors' => $this->_ancestors,
			'descendants' => $this->_descendants
		));

		return $config;
	}

	//TODO: Change name?
	private function __setRelations($row)
	{
		foreach($this->getRelations() as $type => $relation) {
			$data = json_decode($row->{$type});

			foreach($relation as $name => $params) {
				if($data->{$name}) {
					$model = $this->getService($params['identifier']);

					if(KInflector::isSingular($name)) {
						$row->{$name} = $model->id($data->{$name})->getItem();
					} else {
						if($params['state']) {
							$state = $model->getState();
							foreach($params['state'] as $key => $value) {
								if($filter = $state[$key]->filter) {
									$state->remove($key)->insert($key, $filter, $value);
								}
							}
						}

						$row->{$name} = $model->id($data->{$name})->getList();

						unset($state);
					}

					unset($model);
				}
			}
		}
	}
}