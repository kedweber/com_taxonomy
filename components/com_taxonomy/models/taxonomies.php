<?php

class ComTaxonomyModelTaxonomies extends ComTaxonomyModelNodes
{
    /**
     * @param KConfig $config
     */
    public function __construct(KConfig $config)
    {
        parent::__construct($config);

        $this->_state
            ->insert('type'				, 'string')
            ->insert('ids'				, 'string')
        ;
    }

    /**
     * @param KDatabaseQuery $query
     */
    protected function _buildQueryJoins(KDatabaseQuery $query)
    {
        $state = $this->_state;

        parent::_buildQueryJoins($query);

        $iso_code = substr(JFactory::getLanguage()->getTag(), 0, 2);

        if($iso_code != 'en') {
            $prefix = $iso_code.'_';
        }

		if(is_array($state->type)) {
			$subquery = '(';
			$i = 1;
			foreach($state->type as $type) {
				$subquery .='SELECT '.KInflector::pluralize($type).'_'.KInflector::singularize($type).'_id AS id, LOWER("'.strtoupper(KInflector::pluralize($type)).'_'.strtoupper(KInflector::pluralize($type)).'") AS test FROM #__'.$prefix.KInflector::pluralize($type).'_'.KInflector::pluralize($type).' AS '.KInflector::pluralize($type).'
                WHERE enabled = 1 AND featured = 1';
				if(KInflector::singularize($type) == 'event') {
					$subquery .=' AND start_date >= CURDATE()';
				}
				if($i < count($state->type)) {
					$subquery .= ' UNION ALL ';
				}
				$i++;
			}
			$subquery .= ')';

			$query->join[]=array(
				'type' => 'INNER',
				'table' => $subquery. 'AS b',
				'condition' => array('tbl.row = b.id AND tbl.table = b.test'));
		}
    }

    /**
     * @param KDatabaseQuery $query
     */
    protected function _buildQueryWhere(KDatabaseQuery $query)
    {
        $state = $this->_state;

        parent::_buildQueryWhere($query);

        if($state->type) {
            if(!is_array($state->type)) {
                $query->where('tbl.type', '=', $state->type);
            }

            if(is_array($state->type)) {
                $query->where('tbl.type', 'IN', $state->type);
            }
        }

        if($state->search) {
            $query->where('tbl.title', 'LIKE', '%'.$state->search.'%');
        }

        if($state->ids) {
            $query->where('tbl.taxonomy_taxonomy_id','IN',$state->ids);
        }
    }
}