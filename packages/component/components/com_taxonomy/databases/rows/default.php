<?php

class ComTaxonomyDatabaseRowDefault extends KDatabaseRowDefault
{
	/**
	 * @param $column
	 * @return null
	 */
	private function __getRelation($type, $column)
	{
		$relations = $this->getRelations();

		$taxonomies = json_decode($this->{$type});

		if($this->getRelations()->{$type}->{$column}->fallback == 1) {
			$identity_column = '';
			$id = '';
		} else {

		}

		if(KInflector::isSingular($column)) {
			return $this->getService($relations->{$type}->{$column}->identifier)->id($taxonomies->{$column})->getItem();
		}

		if(KInflector::isPlural($column)) {
			$model = $this->getService($relations->{$type}->{$column}->identifier);
			$state = $model->getState();

			if($relations->{$type}->{$column}->{$column}->state) {
				foreach($relations->{$type}->{$column}->state as $key => $value) {
					if($filter = $state[$key]->filter) {
						$state->remove($key)->insert($key, $filter, $value);
					}
				}
			}

			return $model->id($taxonomies->{$column})->getList();
		}
	}

	public function __get($column)
	{
		$result = parent::__get($column);

		if($this->isRelationable()) {
			if(!empty($this->ancestors) && empty($result)) {
				$ancestors = json_decode($this->ancestors, true);

				if(is_array($ancestors) && array_key_exists($column, $ancestors)) {
					$result = $this->__getRelation('ancestors', $column);

					$this->setData(array(
						$column => $result
					));

					return $result;
				}
			}

			if(!empty($this->descendants) && empty($result)) {
				$descendants = json_decode($this->descendants, true);

				if((is_array($descendants) && array_key_exists($column, $descendants)) || $this->getRelations()->descendants->{$column}->fallback == 1) {
					$result = $this->__getRelation('descendants', $column);

					$this->setData(array(
						$column => $result
					));

					return $result;
				}
			}

			return $result;
		}
	}

	public function getRelations()
	{
		return $this->getTable()->getBehavior('relationable')->getRelations();
	}
}