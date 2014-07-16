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

class ComTaxonomyControllerBehaviorRelationable extends KControllerBehaviorAbstract
{
    protected function _beforeAdd(KCommandContext $context)
    {
        //TODO: Get id via slug and via getRedirect();
        //$this->getRedirect());

        // If the parent table has been overridden, we need to get the correct parent ID
        // from the taxonomy table.
        if(isset($context->data->parent) && isset($context->data->parent_table)) {

            $taxonomy = $this->getService('com://admin/taxonomy.model.taxonomies')
                ->row($context->data->parent)
                ->table($context->data->parent_table)
                ->getItem();

            $parent = $taxonomy->id;

        }else{

            $parent = $context->caller->getRequest()->parent ? $context->caller->getRequest()->parent : $context->data->parent;

        }

        $context->data->parent_id = $parent;
    }
}