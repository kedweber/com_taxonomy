<?php defined('KOOWA') or die('Restricted Access');

class ComTaxonomyModelDefault extends ComDefaultModelDefault
{
	/**
	 * @param KConfig $config
	 */
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		//Dynamic state injection based on relations.
		if($this->getTable()->hasBehavior('relationable')) {
			$relations = $this->getTable()->getBehavior('relationable')->getRelations();

			foreach($relations as $children) {
				foreach($children as $name => $relation) {
					if(KInflector::isPlural($name)) {
						$this->_state->insert($name, 'raw');
					}
					$this->_state->insert(KInflector::singularize($name), 'int');
				}
			}
		}

		$this->_state
			->insert('ancestors', 'raw')
		;
	}

	/**
	 * @param KDatabaseQuery $query
	 */
	protected function _buildQueryJoins(KDatabaseQuery $query)
	{
		if(!$this->getTable()->hasBehavior('relationable')) {
			$state = $this->_state;

			parent::_buildQueryJoins($query);

			$query->join('INNER', '#__taxonomy_taxonomies AS taxonomies', array(
				'taxonomies.row = tbl.'.$this->getTable()->getIdentityColumn().'',
				'taxonomies.table = LOWER("'.strtoupper($this->getTable()->getBase()).'")'
			));
			$query->select('taxonomies.taxonomy_taxonomy_id AS taxonomy_taxonomy_id');
		}
	}

	/**
	 * @param KDatabaseQuery $query
	 */
	protected function _buildQueryHaving(KDatabaseQuery $query)
	{
//		$state = $this->_state;
//
//		parent::_buildQueryHaving($query);
//
//
//		if(is_array($state->ancestors)) {
//			$havings = array();
//
//			$i = 0;
//			foreach($state->ancestors as $ancestor)
//			{
//				if($i === 0) {
//					$havings[$i] = '(FIND_IN_SET('.$ancestor.', LOWER(ANCESTOR_IDS)))';
//				} else {
//					$havings[$i] = 'AND (FIND_IN_SET('.$ancestor.', LOWER(ANCESTOR_IDS)))';
//				}
//				$i++;
//			}
//
//			$having = implode(' ', $havings);
//
//			$query->having($having);
//		}
//
//		if($this->getTable()->hasBehavior('relationable')) {
//			$relations = $this->getTable()->getBehavior('relationable')->getRelations();
//
//			$havings = array();
//
//			$i = 0;
//			//TODO: Check and implement new relations system.
//			foreach($relations as $relation) {
//				if($state->{KInflector::singularize($relation)}) {
//					if($i === 0) {
//						$havings[$i] = '(FIND_IN_SET('.$state->{KInflector::singularize($relation)}.', LOWER(ANCESTOR_IDS)))';
//					} else {
//						$havings[$i] = 'AND (FIND_IN_SET('.$ancestor.', LOWER(ANCESTOR_IDS)))';
//					}
//					$i++;
//				}
//
//				if($state->{KInflector::pluralize($relation)}) {
//					$havings = array();
//
//					foreach($state->{KInflector::pluralize($relation)} as $value) {
//						if($i === 0) {
//							$havings[$i] = '(FIND_IN_SET('.$value.', LOWER(ANCESTOR_IDS)))';
//						} else {
//							$havings[$i] = 'AND (FIND_IN_SET('.$value.', LOWER(ANCESTOR_IDS)))';
//						}
//						$i++;
//					}
//				}
//			}
//
//			$having = implode(' ', $havings);
//
//			if($having) {
//				$query->having($having);
//			}
//		}
	}

	/**
	 * Specialized to NOT use a count query since all the inner joins get confused over it
	 *
	 * @see KModelTable::getTotal()
	 */
	public function getTotal()
	{
		// Get the data if it doesn't already exist
		if (!isset($this->_total)) {
			if ($this->isConnected()) {
				$query = $this->getTable()->getDatabase()->getQuery();

				$this->_buildQueryColumns($query);
				$this->_buildQueryFrom($query);
				$this->_buildQueryJoins($query);
				$this->_buildQueryWhere($query);
				$this->_buildQueryGroup($query);
				$this->_buildQueryHaving($query);

				$total = count($this->getTable()->select($query, KDatabase::FETCH_FIELD_LIST));
				$this->_total = $total;
			}
		}

		return $this->_total;
	}
}