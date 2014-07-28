<?php

class ComTaxonomyDatabaseRowDefault extends KDatabaseRowDefault
{
	/**
	 * @param $column
	 * @return null
	 */
	private function __getRelation($type, $column)
	{
		$relations	= $this->getRelations();
		$taxonomies = json_decode($this->{$type});
		$identifier = new KServiceIdentifier($relations->{$type}->{$column}->identifier);

		if($this->getRelations()->{$type}->{$column}->fallback == 1) {
			$identity_column = KInflector::singularize($this->getIdentifier()->name).'_id';
			$id = $this->id;
		} else {
			$identity_column = 'id';
			$id = $taxonomies->{$column};
		}

		if(KInflector::isSingular($column)) {
			$result = $this->getService($identifier)->set($identity_column, $id)->getItem();

			if(!$result->id) {
				$result = null;
			}
		} else {
			$model = $this->getService($identifier);
			$state = $model->getState();

			if(isset($relations->{$type}->{$column}->{$column}->state)) {
				foreach($relations->{$type}->{$column}->state as $key => $value) {
					if($filter = $state[$key]->filter) {
						$state->remove($key)->insert($key, $filter, $value);
					}
				}
			}

			try {
				$result = $model->{$identity_column}($id)->getList();
			} catch (Exception $e) {
				return $result = null;
			}

			if($result->count() == 0) {
				$result = null;
			}
		}

		return $result;
	}

	public function __get($column)
	{
		$result = parent::__get($column);

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

			if((is_array($descendants) && array_key_exists($column, $descendants)) || (isset($this->getRelations()->descendants->{$column}) && $this->getRelations()->descendants->{$column}->fallback == 1)) {
				$result = $this->__getRelation('descendants', $column);

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