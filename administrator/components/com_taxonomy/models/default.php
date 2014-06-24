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