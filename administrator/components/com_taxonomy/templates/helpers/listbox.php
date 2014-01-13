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

class ComTaxonomyTemplateHelperListbox extends ComDefaultTemplateHelperListbox
{
    //TODO:: Improve!
    public function taxonomies($config = array())
    {
	    $config     = new KConfig($config);
        $config->append(array(
           'relation' => 'descendants'
        ));

        $template   = $this->getTemplate();
        $name       = $template->getView()->getName();
        $data       = $template->getData();
        $row        = $data[$name];

	    if($row instanceof KDatabaseRowAbstract && $row->isRelationable() && $row->id) {
		    if($config->attribs->multiple) {
			    $config->prompt = '- '.$this->translate('None').' -';

			    $selected = $row->getRelation(array('type' => $config->relation, 'filter' => array('type' => $config->type)))->getIds('taxonomy_taxonomy_id');
		    } else {
			    $selected = $row->getParent(array('type' => $config->relation, 'filter' => array('type' => $config->type)))->taxonomy_taxonomy_id;
		    }
	    }

        $config->append(array(
            'model'    => 'taxonomies',
            'name'     => 'taxonomy_taxonomy_id',
            'value'    => 'taxonomy_taxonomy_id',
            'text'     => 'title',
            'selected' => $selected,
            'filter'   => array('type' => $config->type),
        ));

        return parent::_render($config);
    }
}