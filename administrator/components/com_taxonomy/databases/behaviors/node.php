<?php

/**
 * Companion behavior for the node row
 *
 * This behavior is used for saving and deleting relations.
 * The reason for using a separate behavior is to make sure that other behaviors
 * like orderable can use methods like getAncestors, getParent.
 */
class ComTaxonomyDatabaseBehaviorNode extends KDatabaseBehaviorAbstract
{
    /**
     * We do not run afterDelete event for rows in this array
     * since they will be taken care of by their parent row.
     *
     * @var array
     */
    protected static $_to_be_deleted;

    protected function _initialize(KConfig $config)
    {
        $config->append(array(
            'priority'   => KCommand::PRIORITY_HIGHEST
        ));

        parent::_initialize($config);
    }

    protected function _afterTableInsert(KCommandContext $context)
    {
        if ($context->affected !== false) {
            $this->_saveRelations($context);
        }
    }

    protected function _afterTableUpdate(KCommandContext $context)
    {
        $this->_saveRelations($context);
    }

    protected function _beforeTableDelete(KCommandContext $context)
    {
        if (!self::$_to_be_deleted) {
            self::$_to_be_deleted = array();
        }

        if (!in_array($this->id, self::$_to_be_deleted)) {
            self::$_to_be_deleted += $this->getDescendants()->getColumn('id');
        }
    }

    /**
     * Deletes the row, its children and its node relations
     *
     * @param KCommandContext
     */
    protected function _afterTableDelete(KCommandContext $context)
    {
        if ($context->affected) {
            if (in_array($context->data->id, self::$_to_be_deleted)) {
                return;
            }

            $descendants = $this->getDescendants();
            $ids = array_merge($descendants->getColumn('id'), array($context->data->id));

            $descendants->delete();

            $db = $this->getTable()->getDatabase();
            $query = sprintf('DELETE FROM #__%s WHERE descendant_id IN (%s)', $this->getTable()->getRelationTable(), implode(', ', $ids));
            $db->execute($query);
        }
    }

    /**
     * Saves the row hierarchy to the relations table
     *
     * @param KCommandContext $context
     */
    protected function _saveRelations(KCommandContext $context)
    {
        $was_new = ($context->operation === KDatabase::OPERATION_INSERT);
        $parent  = $this->getParent();
        $move_it = $was_new || ($this->isModified('parent_id') && (!$parent || $context->data->parent_id != $parent->id));

        /*
         * If you only modify parent_id $result will be false since there is no modified columns
        * in the base table, so we still try to move the row anyhow if the row is not new
        */
        if ($move_it) {
            $parent_id = $this->getTable()->select(array('id' => (int) $context->data->parent_id), KDatabase::FETCH_FIELD);

            if ($this->isAncestorOf($parent_id)) {
                $this->setStatusMessage(JText::_('You cannot move a node under one of its descendants'));
                $this->setStatus(KDatabase::STATUS_FAILED);

                return false;
            }

            if ($was_new) {
                $query = 'INSERT INTO #__%s (ancestor_id, descendant_id, level, created_on, created_by)
                SELECT ancestor_id, %d, level+1, "'.gmdate('Y-m-d H:i:s').'" AS created_on, '.(int) JFactory::getUser()->get('id').' AS created_by FROM #__%1$s
                WHERE descendant_id = %d
                UNION ALL SELECT %2$d, %2$d, 0, "'.gmdate('Y-m-d H:i:s').'", '.(int) JFactory::getUser()->get('id').'
                ';

                $this->getTable()->getDatabase()->execute(sprintf($query, $this->getTable()->getRelationTable(), $context->data->id, (int) $parent_id));
            } else {
                //$this->move($parent_id);
                $this->append($parent_id);
            }
        }
    }
}
