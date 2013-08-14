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

        /**
         * Delete the taxonomy record
         * @return bool
         */
        public function delete()
        {
            $result = false;

            if ($this->isConnected()) {

                if (!is_null($this->row) && !is_null($this->table)) {

                    $query = " DELETE
                                FROM #__" . $this->getTable()->getName() . "
                                WHERE 1
                                 AND `row` = " . (int)$this->row . "
                                 AND `table` = '" . (string)$this->table . "'
                                LIMIT 1
                             ";

                    $result = $this->getTable()->getDatabase()->execute($query);

                    if ($result !== false) {
                        if (((integer)$result) > 0) {
                            $this->_new = true;
                        }
                    }
                }
            }

            return (bool)$result;
        }


}
