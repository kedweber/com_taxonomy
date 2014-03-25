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
	protected  $_ancestors;

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
            $query->select('taxonomies.taxonomy_taxonomy_id AS taxonomy_taxonomy_id');

//            $query->select('GROUP_CONCAT(DISTINCT(as.ancestor_id) ORDER BY as.level DESC SEPARATOR \',\') AS ancestors');
//            $query->select('GROUP_CONCAT(DISTINCT(ds.descendant_id) ORDER BY as.level DESC SEPARATOR \',\') AS descendants');
//            $query->join('inner', '#__taxonomy_taxonomy_relations AS as', 'as.descendant_id = taxonomies.taxonomy_taxonomy_id');
//            $query->join('inner', '#__taxonomy_taxonomy_relations AS ds', 'ds.ancestor_id = taxonomies.taxonomy_taxonomy_id');
//            $query->group('taxonomies.taxonomy_taxonomy_id');
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

        $taxonomy = $this->getService('com://admin/taxonomy.model.taxonomies')
            ->row($context->data->id)
            ->table($context->caller->getBase())
            ->getItem();

        $taxonomy->setData($data);
        $taxonomy->save();

        //TODO: Make it possible to save relation both ways.
		if($this->_ancestors) {
			foreach($this->_ancestors as $name => $ancestor) {
				if(isset($context->data->{$name})) {
					$relations = $taxonomy->getAncestors(array('filter' => array('type' => KInflector::singularize($name))));

					if($relations->getIds('taxonomy_taxonomy_id')) {
						$this->getService('com://admin/taxonomy.model.taxonomy_relations')->ancestor_id($relations->getIds('taxonomy_taxonomy_id'))->descendant_id(array($taxonomy->id))->getList()->delete();
					}

					if(KInflector::isPlural($name) && is_array($context->data->{$name})) {
						foreach($context->data->{$name} as $relation) {
							if(is_numeric($relation)) {
								$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($relation)->getItem();

								$taxonomy->append($row->id);
							} else {
								//TODO: Check if array or object convert etc.
								$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($relation['taxonomy_taxonomy_id'])->getItem();

								$taxonomy->append($row->id);
							}
						}
					} else {
						if($context->data->{$name}) {
							$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($context->data->{$name})->getItem();

							$taxonomy->append($row->id);
						}
					}
				}
			}
		}

		if($this->_descendants) {
			foreach($this->_descendants as $name => $ancestor) {
				if(isset($context->data->{$name})) {
					$relations = $taxonomy->getAncestors(array('filter' => array('type' => KInflector::singularize($name))));

					if($relations->getIds('taxonomy_taxonomy_id')) {
						$this->getService('com://admin/taxonomy.model.taxonomy_relations')->ancestor_id($relations->getIds('taxonomy_taxonomy_id'))->descendant_id(array($taxonomy->id))->getList()->delete();
					}

					if(KInflector::isPlural($name) && is_array($context->data->{$name})) {
						foreach($context->data->{$name} as $relation) {
							if(is_numeric($relation)) {
								$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($relation)->getItem();

								$row->append($taxonomy->id);
							} else {
								//TODO: Check if array or object convert etc.
								$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($relation['taxonomy_taxonomy_id'])->getItem();

								$row->append($taxonomy->id);
							}
						}
					} else {
						if($context->data->{$name}) {
							$row = $this->getService('com://admin/taxonomy.model.taxonomies')->id($context->data->{$name})->getItem();

							$row->append($taxonomy->id);
						}
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