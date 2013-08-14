<?php defined('KOOWA') or die;

class ComTaxonomyTemplateHelperListbox extends ComDefaultTemplateHelperListbox
{
    public function taxonomies($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'model'    => 'taxonomies',
            'name'     => 'taxonomy_taxonomy_id',
            'value'    => 'id',
            'text'     => 'title',
        ));

        return parent::_render($config);
    }
}