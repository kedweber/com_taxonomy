<?php

class ComTaxonomyAliases extends KObject
{
    protected $_loaded = false;

    public function setLoaded($loaded)
    {
        $this->_loaded = $loaded;

        return $this;
    }

    public function setAliases()
    {
        if (!$this->_loaded) {
            $maps = array(
                'com://site/taxonomy.model.default'	    			=> 'com://admin/taxonomy.model.default',
                'com://site/taxonomy.model.nodes'	     			=> 'com://admin/taxonomy.model.nodes',
                'com://site/taxonomy.model.taxonomies'		     	=> 'com://admin/taxonomy.model.taxonomies',
				'com://site/taxonomy.database.rows.default'	     	=> 'com://admin/taxonomy.database.rows.default',
//                'com://site/taxonomy.database.table.events'	     	=> 'com://admin/events.database.table.events',
//                'com://site/events.database.table.categories'    	=> 'com://admin/docman.database.table.categories',
//                'com://site/events.database.table.documents'     	=> 'com://admin/docman.database.table.documents',
            );

            foreach ($maps as $alias => $identifier) {
                KService::setAlias($alias, $identifier);
            }

            $this->setLoaded(true);
        }

        return $this;
    }
}