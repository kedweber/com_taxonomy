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

//    private function __array_diff_deep($array1, $array2)
//    {
//        $diff = array();
//
//        foreach ($array1 as $name => $value) {
//            if (!$array2[$name]) {
//                $diff[$name] = $value;
//            } else if (is_array($array2[$name])) { // plural
//                $diff[$name] = array_diff((array)$value, $array2[$name]);
//            } else if ($array1[$name] != $array2[$name]) { // single
//                $diff[$name] = $value;
//            }
//        }
//
//        return $diff;
//    }

    /**
     * Saves the relations.
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

        // If new? save to get an id.
        if (!$taxonomy->id) {
            $taxonomy->save();
        }

//        // Put the old relations in variables
//        $old_ancestors = json_decode($taxonomy->ancestors, true);
//        $old_descendants = json_decode($taxonomy->descendants, true);

        // Check if relations to save are set in the config
        $post_data = $context->data->getData();
        $new_ancestors = $this->__getNewRelations($this->_ancestors, $post_data);
        $new_descendants = $this->__getNewRelations($this->_descendants, $post_data);

//        /**
//         * Save new relations in taxonomy_taxonomy_relations
//         **/
//        // Ancestors:
//        $ancestors_to_save = $this->__array_diff_deep($new_ancestors, $old_ancestors);
//        foreach ($ancestors_to_save as $name => $taxonomy_taxonomy_id) {
//            $taxonomy_relation = $this->getService('com://admin/taxonomy.model.taxonomy_relations')->getItem();
//            $taxonomy_relation->setData(array(
//                'ancestor_id'   => $taxonomy_taxonomy_id,
//                'descendant_id' => $taxonomy->id,
//                'level'         => 1
//            ));
////            $taxonomy_relation->save();
//        }
//        // Descendants:
//        $descendants_to_save = $this->__array_diff_deep($new_descendants, $old_descendants);
//        foreach ($descendants_to_save as $name => $taxonomy_taxonomy_id) {
//            $taxonomy_relation = $this->getService('com://admin/taxonomy.model.taxonomy_relations')->getItem();
//            $taxonomy_relation->setData(array(
//                'ancestor_id'   => $taxonomy->id,
//                'descendant_id' => $taxonomy_taxonomy_id,
//                'level'         => 1
//            ));
////            $taxonomy_relation->save();
//        }
//
//        /**
//         * Delete old relations in taxonomy_taxonomy_relations
//         **/
//        // Ancestors:
//        $ancestors_to_remove = $this->__array_diff_deep($old_ancestors, $new_ancestors);
//        foreach ($ancestors_to_remove as $name => $taxonomy_taxonomy_id) {
//            $this->getService('com://admin/taxonomy.model.taxonomy_relations')->ancestor_id($taxonomy_taxonomy_id)->descendant_id($taxonomy->id)->level(1)->getItem()->delete();
//        }
//        // Descendants:
//        $descendants_to_remove = $this->__array_diff_deep($old_descendants, $new_descendants);
//        foreach ($ancestors_to_remove as $name => $taxonomy_taxonomy_id) {
//            $this->getService('com://admin/taxonomy.model.taxonomy_relations')->ancestor_id($taxonomy->id)->descendant_id($taxonomy_taxonomy_id)->level(1)->getItem()->delete();
//        }

        // Set the ids to their entity IDs instead of the taxonomyIDs
        $ancestors_to_save = array();
        foreach ($new_ancestors as $name => $value) {
            $rowIds = array();

            if (is_array($value)) {
                $relations = $this->getService('com://admin/taxonomy.model.taxonomies')->ids(array_values($value))->getList()->getColumn('row');
                $rowIds = array_merge($rowIds, array_keys($relations));
            } else if (is_numeric($value)) {
                $relation = $this->getService('com://admin/taxonomy.model.taxonomies')->id($value)->getItem();
                if ($relation->id) {
                    $rowIds = $relation->id;
                }
            }

            $ancestors_to_save[$name] = $rowIds;
        }

        $descendants_to_save = array();
        foreach ($new_descendants as $name => $value) {
            $rowIds = array();

            if (is_array($value)) {
                $relations = $this->getService('com://admin/taxonomy.model.taxonomies')->ids(array_values($value))->getList()->getColumn('row');
                $rowIds = array_merge($rowIds, array_keys($relations));
            } else if (is_numeric($value)) {
                $relation = $this->getService('com://admin/taxonomy.model.taxonomies')->id($value)->getItem();
                if ($relation->id) {
                    $rowIds = $relation->id;
                }
            }

            $descendants_to_save[$name] = $rowIds;
        }

        $taxonomy->ancestors = json_encode($ancestors_to_save, JSON_NUMERIC_CHECK); // Make sure ids are ints and not strings
        $taxonomy->descendants = json_encode($descendants_to_save, JSON_NUMERIC_CHECK); // Make sure ids are ints and not strings

        // Save the taxonomy with updated relations in ancestors/descendants columns
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

	// TODO: Improve naming!
	// TODO: Improve speed and complexity.
	public function updateRelations($config = array())
	{
		$config = new KConfig($config);

		$identifier = clone $this->getMixer()->getIdentifier();
		$identifier->path = array('model');
		$identifier->name = KInflector::pluralize($identifier->name);

		if($config->id) {
			$row = $this->getService($this->getRelations()->{$config->type}->{$config->name}->identifier)->id($config->id)->getItem();

			$type = $config->type == 'ancestors' ? 'descendants' : 'ancestors';

			$relation = json_decode($row->{$type}, true);

			if(KInflector::isPlural($config->name)) {
				if($relation[$identifier->name]) {
					array_push($relation[$identifier->name], $this->getMixer()->id);
				} else {
					$relation[$identifier->name] = array($this->getMixer()->id);
				}
			} else {
				$relation[$identifier->name] = $this->getMixer()->id;
			}

			$row->setData(array(
				$type => json_encode($relation, true)
			));

			$row->save();
		}
	}

	// TODO: Improve speed and complexity.
	public function removeRelation($config = array())
	{
		$config = new KConfig($config);

		$identifier = clone $this->getMixer()->getIdentifier();
		$identifier->path = array('model');
		$identifier->name = KInflector::pluralize($identifier->name);

		$data = $this->getService($this->getRelations()->ancestors->{$config->name}->identifier)->id($config->id)->getItem();

		$type = $config->type == 'ancestors' ? 'descendants' : 'ancestors';

		$current_relations = json_decode($data->descendants, true);

		$current_relations = array_unique($current_relations[KInflector::pluralize($this->getMixer()->getIdentifier()->name)]);
		$current_relations = array_flip($current_relations);

		unset($current_relations[$this->id]);

		$current_relations = array_values(array_flip($current_relations));

		$data->setData(array(
			$type => json_encode(array(KInflector::pluralize($identifier->name) => $current_relations), true)
		));

		$data->save();
	}
}