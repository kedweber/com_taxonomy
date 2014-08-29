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
        $this->_ancestors = $config->ancestors ? $config->ancestors : new KConfig();
        $this->_descendants = $config->descendants ? $config->descendants : new KConfig();

		parent::__construct($config);
    }

    public function getRelation($config = array())
    {
        $config = new KConfig($config);

        $relation = $this->getService('com://admin/taxonomy.model.taxonomies')
            ->row($this->id)
            ->table($this->getMixer()->getTable()->getBase())
            ->type($this->type)
            ->getItem();

        return $relation->getRelatives($config);
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

    public function getDescendants($config = array())
    {
        $config = new KConfig($config);

        $relation = $this->getService('com://admin/taxonomy.model.taxonomies')
            ->row($this->id)
            ->table($this->getMixer()->getTable()->getBase())
            ->getItem();

        return $relation->getDescendants($config);
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
			foreach($query->order as $key => $order) {
				if($this->getRelations()->ancestors instanceof KConfig) {
					$ancestors = $this->getRelations()->ancestors;

					$columns = array_keys($ancestors->toArray());

					if(in_array(str_replace('tbl.', null, $order['column']), $columns)) {
						if($ancestors->{$order['column']}->sort) {
							$query->select($order['column'].'.'.$ancestors->{$order['column']}->sort. ' AS '. $order['column'].'_'.$ancestors->{$order['column']}->sort);
							$query->join('LEFT', $ancestors->{$order['column']}->table.' AS '.$order['column'], array(
								$order['column'].'.'.$ancestors->{$order['column']}->identity_column.' = SUBSTRING_INDEX(SUBSTR(ancestors,LOCATE(\'"'.strtoupper($order['column']).'":"\',ancestors)+CHAR_LENGTH(\'"'.strtoupper($order['column']).'":"\')),\'"\', 1)'
							));

							$query->order[$key]['column'] =  $order['column'].'_'.$ancestors->{$order['column']}->sort;
						} else {
							$query->select('SUBSTRING_INDEX(SUBSTR(ancestors,LOCATE(\'"'.strtoupper($order['column']).'":"\',ancestors)+CHAR_LENGTH(\'"'.strtoupper($order['column']).'":"\')),\'"\', 1) AS '.$order['column']);
							//TODO: Do we need to filter no values?
							$query->having($order['column'].' > ""');
						}
					}
				}

				if($this->getRelations()->descendants instanceof KConfig) {
					$descendants = $this->getRelations()->descendants;

					$columns = array_keys($descendants->toArray());

					if(in_array(str_replace('tbl.', null, $order['column']), $columns)) {
						if($descendants->{$order['column']}->sort) {
							$query->select($order['column'].'.'.$descendants->{$order['column']}->sort. ' AS '. $order['column'].'_'.$descendants->{$order['column']}->sort);
							$query->join('LEFT', $descendants->{$order['column']}->table.' AS '.$order['column'], array(
								$order['column'].'.'.$descendants->{$order['column']}->identity_column.' = SUBSTRING_INDEX(SUBSTR(ancestors,LOCATE(\'"'.strtoupper($order['column']).'":"\',ancestors)+CHAR_LENGTH(\'"'.strtoupper($order['column']).'":"\')),\'"\', 1)'
							));

							$query->order[$key]['column'] =  $order['column'].'_'.$descendants->{$order['column']}->sort;
						} else {
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
     * Check is the relations in the data are mentioned in the config. If not then they are not returned.
     *
     * @param $config, either ancestors or descendants from the config
     * @param $data, the post data
     * @return array, the new relations
     */
    private function __getNewRelations($config, $data)
    {
        $new_relations = array();
        foreach ($config as $name => $value) {
            if (isset($data[$name])) {
                $relation = $data[$name];
                // If relation name is plural, save as array
                if (KInflector::isPlural($name) && !is_array($relation)) {
                    $relation = array($relation);
                }
                // If relation name is singular, save as int
                else if (KInflector::isSingular($name) && is_array($relation) && count($relation) == 1) {
                    $relation = $relation[0];
                }

                $new_relations[$name] = $relation;
            }
        }

        return $new_relations;
    }

    /**
     * Saves the relations in both ancestors/descendants columns and taxonomy_relations table.
     *
     * @param KCommandContext $context
     */
    protected function _afterTableInsert(KCommandContext $context)
    {
        //Fix for identity columns that are none incremental.
        $identifier = clone $this->getMixer()->getIdentifier();
        $context->data->id = $context->data->id ? $context->data->id : $context->data->{$identifier->package.'_'.$identifier->name.'_id'};

        // Get the taxonomy of the caller
        $taxonomy = $this->getService('com://admin/taxonomy.model.taxonomies')
            ->row($context->data->id)
            ->table($context->caller->getBase())
            ->getItem();

        if($taxonomy->isNew()) {
            $taxonomy->setData(array(
                'row'   => $context->data->id,
                'table' => $context->caller->getBase(),
                'type'  => $context->data->type
            ));
        }

        // Check if relations to save are set in the config
        $post_data = $context->data->getData(); // The post data has the entity ids
        $new_ancestors = $this->__getNewRelations($this->_ancestors, $post_data);
        $new_descendants = $this->__getNewRelations($this->_descendants, $post_data);

        $taxonomy->ancestors = json_encode($new_ancestors, JSON_NUMERIC_CHECK); // Make sure ids are ints and not strings
        $taxonomy->descendants = json_encode($new_descendants, JSON_NUMERIC_CHECK); // Make sure ids are ints and not strings

        $taxonomy->save();
    }

    protected function _afterTableUpdate(KCommandContext $context)
    {
        $this->_afterTableInsert($context);
    }

    protected function _beforeTableDelete(KCommandContext $context)
    {
        $this->getService('com://admin/taxonomy.model.taxonomies')
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
}