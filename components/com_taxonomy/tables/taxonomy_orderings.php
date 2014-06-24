<?php

class ComTaxonomyDatabaseTableTaxonomy_Orderings extends KDatabaseTableDefault
{
    protected function _initialize(KConfig $config)
    {
        $config->append(array(
            'identity_column' => 'taxonomy_taxonomy_id'
        ));

        parent::_initialize($config);
    }
}
