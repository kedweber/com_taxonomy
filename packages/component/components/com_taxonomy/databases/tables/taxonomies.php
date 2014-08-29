<?php

class ComTaxonomyDatabaseTableTaxonomies extends KDatabaseTableDefault
{
    protected function  _initialize(KConfig $config)
    {
        $config->append(array(
            'behaviors' => array(
                'lockable',
                'creatable',
                'modifiable',
                'identifiable',
            )
        ));

        parent::_initialize($config);
    }
}
