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
        ))->append(array(
                'filter' => array(
                    'type' => $config->type
                )
            ));

        $template   = $this->getTemplate();
        $name       = $template->getView()->getName();
        $data       = $template->getData();
        $row        = $data[$name];

	    if($row instanceof KDatabaseRowAbstract && $row->isRelationable() && $row->id) {
		    if($config->attribs->multiple) {
			    $config->prompt = '- '.$this->translate('None').' -';

				if($config->table) {
					$filter = array('table' => $config->table);
				} else {
					$filter = array('type' => $config->filter->type);
				}

				$selected = $row->getRelation(array('type' => $config->relation, 'filter' => $filter))->getColumn('taxonomy_taxonomy_id');
		    } else {
			    $selected = $row->getParent(array('type' => $config->relation, 'filter' => array('type' => $config->filter->type)))->taxonomy_taxonomy_id;
		    }
	    }

		if($selected && $config->attribs['disabled']) {
			unset($config->attribs['disabled']);
		}

        $config->append(array(
            'model'    => 'taxonomies',
            'name'     => 'taxonomy_taxonomy_id',
            'value'    => 'taxonomy_taxonomy_id',
            'text'     => 'title',
            'selected' => $selected
        ));

        return parent::_render($config);
    }
}