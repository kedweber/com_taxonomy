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
        }
    }

	protected function _afterTableSelect(KCommandContext $context)
	{
		if($context->data instanceof KDatabaseRowsetDefault) {
			foreach($context->data as $row) {
				$ancestors = json_decode($row->parentz);

				foreach($this->_ancestors as $key => $ancestor) {
					if($ancestors->{$key}) {
						$row->{$key} = $this->getService($ancestor['identifier'])->id($ancestors->{$key})->getList();
					}
				}
			}
		}

		if($context->data instanceof KDatabaseRowDefault) {
			$ancestors = json_decode($context->data->parentz);

			foreach($this->_ancestors as $key => $ancestor) {
				if($ancestors->{$key}) {
					$context->data->{$key} = $this->getService($ancestor['identifier'])->id($ancestors->{$key})->getList();
				}
			}
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
     * @return mixed
     */
    public function getRelations()
    {
        return $this->_ancestors;
    }
}