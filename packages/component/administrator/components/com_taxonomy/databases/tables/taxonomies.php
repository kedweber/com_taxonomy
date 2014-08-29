<?php

class ComTaxonomyDatabaseTableTaxonomies extends KDatabaseTableDefault
{
    protected function  _initialize(KConfig $config)
    {
        $config->append(array(
            //'command_chain' => $this->getService('com://admin/taxonomy.command.chain'),
//            'relation_table' => 'taxonomy_taxonomy_relations',
            'behaviors' => array(
                'lockable',
                'creatable',
                'modifiable',
                'identifiable',
            ),
            'filters' => array(
                'description' => array('html', 'tidy')
            )
        ));

        parent::_initialize($config);
    }
}
