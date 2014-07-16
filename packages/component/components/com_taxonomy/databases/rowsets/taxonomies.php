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

            $identifier->application = 'site';
            $identifier->package = $parts[0];
            $identifier->path = 'model';
            $identifier->name = $parts[1];

            try {
                $cache  = JFactory::getCache('com_taxonomy', '');
                $key = $this->_getKey($identifier, $values['row']);

                if($data = $cache->get($key)) {
                    $identifier->path = array('database', 'row');
                    $identifier->name = KInflector::singularize($identifier->name);

                    $row = $this->getService($identifier);
                    $row->setData(unserialize($data));
                } else {
                    $row = $this->getService($identifier)->id($values['row'])->getItem();
                    $row->taxonomy_taxonomy_id = $values['id'];
                    $row->option = 'com_'.$parts[0];
                    $row->view  = $parts[1];

                    $cache->store(serialize($row->getData()), $key);
                }

                $this->_identity_column = 'taxonomy_taxonomy_id';

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

    public function getIds($column = 'id')
    {
        $result = array();

        foreach($this as $key => $row) {
            $result[] = $row->{$column};
        }

        return $result;
    }

    protected function _getKey($identifier, $id)
    {
        $key = md5($identifier .':'. $id);

        return $key;
    }
}