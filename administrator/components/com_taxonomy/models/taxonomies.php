<?php

class ComTaxonomyModelTaxonomies extends ComTaxonomyModelNodes
{
    public function __construct(KConfig $config)
    {
        parent::__construct($config);

        $this->_state
            ->insert('type',    'string')
            ->insert('ids',     'string')
        ;
    }

    protected function _buildQueryWhere(KDatabaseQuery $query)
    {
        parent::_buildQueryWhere($query);

        if ($this->_state->type) {
            $query->where('tbl.type', 'IN', $this->_state->type);
        }

        if ($this->_state->search) {
            $query->where('tbl.title', 'LIKE', '%'.$this->_state->search.'%');
        }

        if($this->_state->ids) {
            $query->where('tbl.taxonomy_taxonomy_id','IN',$this->_state->ids);
        }
    }
}