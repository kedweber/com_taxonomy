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
    protected static $_loaded = array();

    //TODO:: Improve!
    public function taxonomies($config = array())
    {
        if (!isset(self::$_loaded['taxonomy'])) {

            $script = '<script src="media://com_taxonomy/js/taxonomy.js" />';

            self::$_loaded['taxonomy'] = true;
        }

        $config = new KConfig($config);
        $config->append(array(
            'model'    => 'taxonomy',
            'value'    => 'id',
            'text'     => 'title',
            'prompt'   => ' - Select - ',
            'required' => false,
            'attribs' => array('data-placeholder' => $this->translate('Select&hellip;'), 'class' => 'select2-listbox taxonomy'),
            'behaviors' => array('select2' => array('element' => '.select2-listbox')),
            'indent'    => '- '
        ));

        return $script.$this->_treelistbox($config);
    }
}