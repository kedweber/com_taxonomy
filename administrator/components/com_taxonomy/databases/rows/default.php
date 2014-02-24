<?php

class ComTaxonomyDatabaseRowDefault extends KDatabaseRowDefault
{
	/**
	 * @param $data
	 * @param bool $modified
	 * @return $this|KDatabaseRowAbstract
	 */
	public function setData($data, $modified = true)
	{
		parent::setData($data, $modified);

		$table = $this->getTable();

		if($table instanceof KDatabaseTableDefault) {
			$relations = $table->getBehavior('relationable')->getRelations();

			foreach($relations as $type => $children) {
				foreach($children as $name => $relation) {
					if($this->isRelationable() && !$this->{$name}) {
						if($ids = explode(',', $this->{$type})) {
							$model = $this->getService($relation->identifier);
							$state = $model->getState();

							foreach($relation->state as $key => $value) {
								if($filter = $state[$key]->filter) {
									$state->remove($key)->insert($key, $filter, $value);
								}
							}

							$this->{$name} = array_values($model->taxonomy_taxonomy_id($ids)->getList()->toArray());
						}
					}
				}
			}
		}

		return $this;
	}
}