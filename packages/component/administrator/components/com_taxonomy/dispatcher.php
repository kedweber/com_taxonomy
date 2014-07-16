<?php

class ComTaxonomyDispatcher extends ComDefaultDispatcher
{
    protected function _initialize(KConfig $config)
    {
    	$config->append(array(
    		'controller' => 'taxonomies'
        ));

        parent::_initialize($config);
    }
}