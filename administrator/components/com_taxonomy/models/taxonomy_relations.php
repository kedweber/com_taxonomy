<?php
/**
 * ComTaxonomy
 *
 * @author      Dave Li <dave@moyoweb.nl>
 * @category    Nooku
 * @package     Socialhub
 * @subpackage  Taxonomy
 */
 
defined('KOOWA') or die('Protected resource');

class ComTaxonomyModelTaxonomy_relations extends ComDefaultModelDefault
{
    public function __construct(KConfig $config)
    {
        parent::__construct($config);

        $this->_state
            ->insert('ancestor_id',     'int', null, true)
            ->insert('descendant_id',   'int', null, true)
        ;
    }

    protected function _buildQueryWhere(KDatabaseQuery $query)
    {
        $state = $this->_state;

        parent::_buildQueryWhere($query);

        if(is_numeric($state->ancestor_id)) {
            $query->where('tbl.ancestor_id', '=', $state->ancestor_id);
            $query->where('tbl.descendant_id', '!=', $state->ancestor_id);
        }

        if(is_numeric($state->descendant_id)) {
            $query->where('tbl.descendant_id', '=', $state->descendant_id);
            $query->where('tbl.ancestor_id', '!=', $state->descendant_id);
        }
    }
}