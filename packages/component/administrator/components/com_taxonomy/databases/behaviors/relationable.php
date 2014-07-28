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
		if(isset($config->ancestors)) {
			$this->_ancestors = $config->ancestors;
		}

		if(isset($config->descendants)) {
			$this->_descendants = $config->descendants;
		}

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

    protected function _afterTableInsert(KCommandContext $context)
    {
        $identifier = clone $this->getMixer()->getIdentifier();

        //Fix for identity columns that are none incremental.
        $context->data->id = $context->data->id ? $context->data->id : $context->data->{$identifier->package.'_'.$identifier->name.'_id'};

        $data = array(
            'row'       => $context->data->id,
            'table'     => $context->caller->getBase(),
			'descendants' => $context->data->descendants
        );

        if ($context->data->type)       $data['type']       = $context->data->type;
        if ($context->data->parent_id)  $data['parent_id']  = $context->data->parent_id;

        $taxonomy = $this->getService('com://admin/taxonomy.model.taxonomies')
            ->row($context->data->id)
            ->table($context->caller->getBase())
            ->getItem();

		$taxonomy->setData($data);
		$taxonomy->save();

		$ancestors			= json_decode($taxonomy->ancestors, true);
		$descendants		= json_decode($taxonomy->descendants, true);
		$orignial_ancestors =  $ancestors;

		if($this->_ancestors) {
			foreach($this->_ancestors as $name => $ancestor) {
				if(isset($context->data->{$name})) {
					$relations = $taxonomy->getAncestors(array_merge_recursive($ancestor->toArray(), array('filter' => array('table' => $this->getService($ancestor->identifier)->getTable()->getBase()))));

					if($relations->getIds('taxonomy_taxonomy_id')) {
						$this->getService('com://admin/taxonomy.model.taxonomy_relations')->ancestor_id($relations->getIds('taxonomy_taxonomy_id'))->descendant_id(array($taxonomy->id))->getList()->delete();
					}

					unset($ancestors[$name]);

					if(KInflector::isPlural($name) && is_array($context->data->{$name})) {
						// Remove old relations
						foreach($orignial_ancestors[$name] as $orignial_ancestor) {
							$data = $this->getService($this->getRelations()->ancestors->{$name}->identifier)->id($orignial_ancestor)->getItem();

							$current_relations = json_decode($data->descendants, true);

							$current_relations = array_unique($current_relations[KInflector::pluralize($this->getMixer()->getIdentifier()->name)]);
							$current_relations = array_flip($current_relations);

							unset($current_relations[$context->data->id]);

							$current_relations = array_values(array_flip($current_relations));

							$data->setData(array(
								'descendants' => json_encode(array('articles' => $current_relations), true)
							));

							$data->save();
						}

						foreach($context->data->{$name} as $relation) {
							if(is_numeric($relation) && $relation > 0) {
								$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($relation)->getItem();
								$taxonomy->append($row->id);
							} else {
								if($relation['taxonomy_taxonomy_id']) {
									//TODO: Check if array or object convert etc.
									$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($relation['taxonomy_taxonomy_id'])->getItem();

									$taxonomy->append($row->id);
								}
							}

							$this->updateRelations(array(
								'type'	=> 'ancestors',
								'name'	=> $name,
								'id'	=> $row->row
							));

							$ancestors[$name][] = $row->row;
						}
					} else {
						if(is_numeric($context->data->{$name})) {
							$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($context->data->{$name})->getItem();

							$taxonomy->append($row->id);

							$ancestors[$name] = $row->row;

							$this->updateRelations(array(
								'type'	=> 'ancestors',
								'name'	=> $name,
								'id'	=> $row->row
							));
						}
					}
				}
			}
		}

		if($this->_descendants) {
			foreach($this->_descendants as $name => $descendant) {
				if(isset($context->data->{$name})) {
					$relations = $taxonomy->getDescendants(array_merge_recursive($descendant->toArray(), array('filter' => array('table' => $this->getService($descendant->identifier)->getTable()->getBase()))));

					if($relations->getColumn('id')) {
						$this->getService('com://admin/taxonomy.model.taxonomy_relations')->ancestor_id(array($taxonomy->id))->descendant_id($relations->getColumn('taxonomy_taxonomy_id'))->getList()->delete();
					}

					unset($descendants[$name]);

					if(KInflector::isPlural($name) && is_array($context->data->{$name})) {
						foreach($context->data->{$name} as $relation) {
							if(is_numeric($relation) && $relation > 0) {
								$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($relation)->getItem();

								$row->append($taxonomy->id);

								$descendants[$name][] = $row->row;
							} else {
								if($relation['taxonomy_taxonomy_id']) {
									//TODO: Check if array or object convert etc.
									$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($relation['taxonomy_taxonomy_id'])->getItem();

									$row->append($taxonomy->id);

									$descendants[$name][] = $row->row;
								}
							}
						}
					} else {
						if(is_numeric($context->data->{$name})) {
							$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($context->data->{$name})->getItem();

							$row->append($taxonomy->id);

							$descendants[$name] = $row->row;
						}
					}
				}
			}
		}

		if($ancestors) {
			$taxonomy->ancestors = json_encode($ancestors, JSON_NUMERIC_CHECK);
		} else {
			$taxonomy->ancestors = null;
		}

		if($descendants) {
			$taxonomy->descendants = json_encode($descendants, JSON_NUMERIC_CHECK);
		} else {
			$taxonomy->descendants = null;
		}

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
}