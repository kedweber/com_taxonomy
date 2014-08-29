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

class ComTaxonomyTemplateHelperListbox extends ComMoyoTemplateHelperListbox
{
    //TODO:: Improve!
    public function taxonomies($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'model'    => 'taxonomy',
            'value'    => 'id',
            'text'     => 'title',
            'prompt'   => ' - Select - ',
            'required' => false,
            'attribs' => array('data-placeholder' => $this->translate('Select&hellip;'), 'class' => 'select2-listbox'),
            'behaviors' => array('select2' => array('element' => '.select2-listbox')),
            'indent'    => '- '
        ));

        return $this->_treelistbox($config);
    }
}