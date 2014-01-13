<?php

class ComTaxonomyDatabaseRowTaxonomy extends ComTaxonomyDatabaseRowNode
{
    public function getRelation()
    {
        $parts = explode("_", $this->table, 2);

        $identifier = clone $this->getIdentifier();
        $identifier->package = $parts[0];
        $identifier->path = 'model';
        $identifier->name = $parts[1];

        return $this->getService($identifier)->id($this->row)->getItem();
    }
}