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
	/**
	 * @var mixed
	 */
	protected $_ancestors;

	/**
	 * @var mixed
	 */
	protected $_descendants;

	/**
	 * @param KConfig $config
	 */
	public function __construct(KConfig $config)
    {
        parent::__construct($config);

        $this->_ancestors   = $config->ancestors;
        $this->_descendants = $config->descendants;
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
//	protected function _afterTableSelect(KCommandContext $context)
//	{
//		//TODO:: Dont fire on insert / update / delete.
//		if($context->data instanceof KDatabaseRowsetDefault) {
//			foreach($context->data as $row) {
//				if($this->_ancestors instanceof KConfig) {
//					$ancestors = json_decode($row->ancestors);
//
//					foreach($this->_ancestors as $key => $ancestor) {
//						if($ancestors->{$key}) {
//							if(KInflector::isSingular($key)) {
//								$row->{$key} = $this->getService($ancestor['identifier'])->id($ancestors->{$key})->getItem();
//							} else {
//								$row->{$key} = $this->getService($ancestor['identifier'])->id($ancestors->{$key})->getList();
//							}
//						}
//					}
//				}
//
//				if($this->_descendants instanceof KConfig) {
//					$descendants = json_decode($row->descendants);
//
//					foreach($this->_descendants as $key => $descendant) {
//						if($ancestors->{$key}) {
//							if(KInflector::isSingular($key)) {
//								$row->{$key} = $this->getService($descendant['identifier'])->id($descendants->{$key})->getItem();
//							} else {
//								$row->{$key} = $this->getService($descendant['identifier'])->id($descendants->{$key})->getList();
//							}
//						}
//					}
//				}
//			}
//		}
//	}

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