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
            ->insert('ancestor_id',     'int')
            ->insert('descendant_id',   'int')
        ;
    }

    protected function _buildQueryWhere(KDatabaseQuery $query)
    {
        $state = $this->_state;

        parent::_buildQueryWhere($query);

        if($state->ancestor_id) {
            $query->where('tbl.ancestor_id', 'IN', $state->ancestor_id);
            $query->where('tbl.descendant_id', 'NOT IN', $state->ancestor_id);
        }

        if($state->descendant_id) {
            $query->where('tbl.descendant_id', 'IN', $state->descendant_id);
            $query->where('tbl.ancestor_id', 'NOT IN', $state->descendant_id);
        }
    }
}