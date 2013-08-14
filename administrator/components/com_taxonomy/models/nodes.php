<?php

class ComTaxonomyModelNodes extends ComDefaultModelDefault
{
    public function __construct(KConfig $config)
    {
        parent::__construct($config);

        $this->_state
            ->remove('sort')->insert('sort', 'cmd', 'path')
            ->insert('draft', 'int')
            ->insert('parent_id', 'int')
            ->insert('include_self', 'boolean', false)
            ->insert('level', 'int')
            ->insert('created_by', 'int')
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

    protected function _buildQueryColumns(KDatabaseQuery $query)
    {
        $query->select('COUNT(crumbs.ancestor_id) AS level')
            ->select('GROUP_CONCAT(crumbs.ancestor_id ORDER BY crumbs.level DESC SEPARATOR \'/\') AS path');

        if ($this->getTable()->hasBehavior('orderable')) {
            if (!$query->count) {
                $query->select('o2.custom AS ordering');
            }

            if (in_array($this->_state->sort, array('title', 'created_on', 'custom'))) {
                $column = sprintf('GROUP_CONCAT(LPAD(`o`.`%s`, 5, \'0\') ORDER BY crumbs.level DESC  SEPARATOR \'/\') AS order_path', $this->_state->sort);
                $query->select($column);
            }
        }

        parent::_buildQueryColumns($query);
    }

    protected function _buildQueryJoins(KDatabaseQuery $query)
    {
        $relation = $this->getTable()->getRelationTable();
        $id_column = $this->getTable()->getIdentityColumn();

        $query->join('inner', '#__'.$relation.' AS crumbs', 'crumbs.descendant_id = tbl.'.$id_column);

        if ($this->getTable()->hasBehavior('orderable')) {
            // This one is to have a breadcrumbs style order like 1/3/4
            if (in_array($this->_state->sort, array('title', 'created_on', 'custom'))) {
                $query->join('inner', '#__taxonomy_taxonomy_orderings AS o', 'crumbs.ancestor_id = o.'.$id_column);
            }

            // This one is to display the custom ordering in backend
            if (!$query->count) {
                $query->join('left', '#__taxonomy_taxonomy_orderings AS o2', 'tbl.'.$id_column.' = o2.'.$id_column);
            }

        }

        if ($this->_state->parent_id) {
            $query->join('inner', '#__'.$relation.' AS r', 'r.descendant_id = tbl.'.$id_column);
        }

        //TODO: Invites via special table for easier and more flexable approach!
        if($this->_state->draft === 1) {
            $join_column  = $this->created_by ? 'rel.descendant_id' : 'rel.ancestor_id';

            $query->join('inner', '#__'.$relation.' AS rel', array(
                    $join_column.' = tbl.'.$id_column,
                    'rel.draft = 1',
                )
            );
        }

        parent::_buildQueryJoins($query);
    }

    protected function _buildQueryWhere(KDatabaseQuery $query)
    {
        parent::_buildQueryWhere($query);

        $state = $this->_state;

        if($state->draft === 1 && !$state->created_by) {
            $query->where('rel.descendant_id', 'IN', $state->parent_id);
        }

        if(is_numeric($state->created_by)) {
            $query->where('tbl.created_by', '=', $state->created_by);
        }

        if($state->parent_id && !$state->draft) {
            $id_column = $this->getTable()->getIdentityColumn();

            $query->where('r.ancestor_id', 'IN', $state->parent_id);
            
            if (empty($state->include_self)) {
                $query->where('tbl.'.$id_column, 'NOT IN', $state->parent_id);
            }
            
            if ($state->level !== null) {
                $query->where('r.level', 'IN', $state->level);
            }
        }
    }

    protected function _buildQueryGroup(KDatabaseQuery $query)
    {
        $query->group('tbl.'.$this->getTable()->getIdentityColumn());

        parent::_buildQueryGroup($query);
    }

    protected function _buildQueryHaving(KDatabaseQuery $query)
    {
        // If we have a parent id level is set using the where clause
        if (!$this->_state->parent_id && $this->_state->level !== null) {
            // Query object does not support operators in having clauses
            // So we need to build the string ourselves
            $query->having('level IN ('.implode(',', (array) $this->_state->level).')');
        }

        parent::_buildQueryHaving($query);
    }

    protected function _buildQueryOrder(KDatabaseQuery $query)
    {
        if ($this->getTable()->hasBehavior('orderable')
                && in_array($this->_state->sort, array('title', 'created_on', 'custom')))
        {
            $query->order('order_path', 'ASC');
        } else {
            $query->order('created_on', 'DESC');
        }
    }
}
