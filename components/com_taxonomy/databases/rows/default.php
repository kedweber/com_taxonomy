<?php

class ComTaxonomyDatabaseRowDefault extends KDatabaseRowDefault
{
	/**
	 * @param $column
	 * @return null
	 */
	public function getRelation($type, $column)
	{
		$relations = $this->getRelations();

		$taxonomies = json_decode($this->{$type});

		if(KInflector::isSingular($column)) {
			return $this->getService($relations->{$type}->{$column}->identifier)->id($taxonomies->{$column})->getItem();
		}

		if(KInflector::isPlural($column)) {
			error_log($column.'  '.$relations->{$type}->{$column}->identifier);

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

		if(!empty($this->ancestors) && empty($result)) {
			$ancestors = json_decode($this->ancestors, true);

			if(is_array($ancestors) && array_key_exists($column, $ancestors)) {
				$result = $this->getRelation('ancestors', $column);

				$this->setData(array(
					$column => $result
				));

				return $result;
			}
		}

		if(!empty($this->descendants) && empty($result)) {
			$descendants = json_decode($this->descendants, true);

			if(is_array($descendants) && array_key_exists($column, $descendants)) {
				$result = $this->getRelation('descendants', $column);

				$this->setData(array(
					$column => $result
				));

				return $result;
			}
		}

		return $result;
	}

	public function getRelations()
	{
		if($this->isRelationable()) {
			return $this->getTable()->getBehavior('relationable')->getRelations();
		}
	}
}