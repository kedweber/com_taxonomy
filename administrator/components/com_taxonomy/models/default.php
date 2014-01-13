<?php defined('KOOWA') or die('Restricted Access');

class ComTaxonomyModelDefault extends ComDefaultModelDefault
{
    /**
     * @param KConfig $config
     */
    public function __construct(KConfig $config)
    {
        parent::__construct($config);

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

            $query->select('GROUP_CONCAT(DISTINCT(crumbs.ancestor_id) ORDER BY crumbs.level DESC SEPARATOR \',\') AS ancestors');
            $query->join('inner', '#__taxonomy_taxonomy_relations AS crumbs', 'crumbs.descendant_id = taxonomies.taxonomy_taxonomy_id');
            $query->group('taxonomies.taxonomy_taxonomy_id');
        }
    }

    /**
     * @param KDatabaseQuery $query
     */
    protected function _buildQueryHaving(KDatabaseQuery $query)
    {
        $state = $this->_state;

        parent::_buildQueryHaving($query);

        if(is_array($state->ancestors)) {
            $havings = array();

            $i = 0;
            foreach($state->ancestors as $ancestor)
            {
                if($i === 0) {
                    $havings[$i] = '(FIND_IN_SET('.$ancestor.', LOWER(ANCESTORS)))';
                } else {
                    $havings[$i] = 'AND (FIND_IN_SET('.$ancestor.', LOWER(ANCESTORS)))';
                }
                $i++;
            }

            $having = implode(' ', $havings);

            $query->having($having);
        }
    }
}