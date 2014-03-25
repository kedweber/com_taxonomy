<?php
/**
 * Com
 *
 * @author      Dave Li <dave@moyoweb.nl>
 * @category    Nooku
 * @package     Socialhub
 * @subpackage  ...
 * @uses        Com_
 */

defined('KOOWA') or die('Protected resource');

class ComTaxonomyViewJson extends KViewJson
{
	protected function _getItem()
	{
		$data = parent::_getItem();

		$convert = array();
		$convert['item'] = array_map(array($this, 'convert'), $data['item']);

		return array_merge($data, $convert);
	}

	protected function _getList()
	{
		$data = parent::_getList();

		$convert = array();
		$convert['items'] = array_map(array($this, 'convert'), $data['items']);

		return array_merge($data, $convert);
	}

	public function convert($data)
	{
		if(is_array($data)) {
			$values = array();

			foreach($data as $key => $value) {
				if($value instanceof KDatabaseRowsetDefault) {
					$values[$key] = array_values($value->toArray());
				} else {
					$values[$key] = $value;
				}
			}

			$data = $values;
		} else {
			if($data instanceof KDatabaseRowsetDefault) {
				$data = array_values($data->toArray());
			}
		}

		return $data;
	}
}