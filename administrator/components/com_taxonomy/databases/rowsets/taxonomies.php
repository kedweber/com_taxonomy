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

class ComTaxonomyDatabaseRowsetTaxonomies extends KDatabaseRowsetDefault
{
    /**
     * @var array
     */
    protected $_relation_methods = array();

    public function __construct(KConfig $config)
    {
        parent::__construct($config);

        $this->_relation_methods = $this->getTable()->getBehavior('relationable')->getMixableMethods();
    }

    /**
     * @param $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        if(isset($this->_relation_methods)) {
            if(in_array($method, $this->_relation_methods)) {
                return $this->getTable()->getBehavior('relationable')->{$method}($arguments);
            }
        }

        return parent::__call($method, $arguments);
    }

    /**
     * @param array $data
     * @param bool $new
     * @return ComTaxonomyDatabaseRowsetTaxonomies|KDatabaseRowsetAbstract
     */
    public function addData(array $data, $new = true)
    {

        if($new) {
            return parent::addData($data, $new);
        }

        //TODO:: Check on save!
        foreach($data as $k => $values)
        {
            $parts = explode("_", $values['table'], 2);

            $identifier = clone $this->getIdentifier();
            $identifier->package = $parts[0];
            $identifier->path = 'model';
            $identifier->name = $parts[1];

            try {
                $row = $this->getService($identifier)->id($values['row'])->getItem();
                $row->taxonomy_taxonomy_id = $values['id'];
                if (isset($values['draft'])) {
                    $row->draft = $values['draft'];
                }
                $this->insert($row);
            } catch (Exception $e) {
                $options = array(
                    'data'   => $values,
                    'status' => $new ? NULL : KDatabase::STATUS_LOADED,
                    'new'    => $new,
                );

                $this->insert($this->getRow($options));
            }
        }

        return $this;
    }
}