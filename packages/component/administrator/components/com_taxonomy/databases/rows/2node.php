<?php

class ComTaxonomyDatabaseRowNode extends KDatabaseRowDefault
{
    /**
     * Constant to fetch all levels in traverse methods
     *
     * @var int
     */
    const FETCH_ALL_LEVELS = 0;

    /**
     * Table name for main storage
     *
     * @var string
     */
    protected $_table_name;

    /**
     * Table name for node relations
     *
     * @var string
     */
    protected $_relation_table_name;

    public function __construct(KConfig $config)
    {
        parent::__construct($config);

        $this->_table_name = $this->getTable()->getName();
        $this->_relation_table_name = $this->getTable()->getRelationTable();

        if (empty($this->_relation_table_name)) {
            throw new KDatabaseRowException('Relation table cannot be empty');
        }

//        $this->mixin(clone $this->getTable()->getBehavior('node'));
    }

    /**
     *
     * Move the row and all its descendants to a new position
     *
     * @link http://www.mysqlperformanceblog.com/2011/02/14/moving-subtrees-in-closure-table/
     *
     * @param  int     $target_id Target to move the subtree under
     * @return boolean Result of the operation
     */
    public function move($target_id)
    {
        $query = 'DELETE a FROM #__%1$s AS a'
            . ' JOIN #__%1$s AS d ON a.descendant_id = d.descendant_id'
            . ' LEFT JOIN #__%1$s AS x ON x.ancestor_id = d.ancestor_id AND x.descendant_id = a.ancestor_id'
            . ' WHERE d.ancestor_id = %2$d AND x.ancestor_id IS NULL';

        $result = $this->getTable()->getDatabase()->execute(sprintf($query, $this->_relation_table_name, $this->id));

        $query = 'INSERT INTO #__%1$s (ancestor_id, descendant_id, level)'
            . ' SELECT a.ancestor_id, b.descendant_id, a.level+b.level+1'
            . ' FROM #__%1$s AS a'
            . ' JOIN #__%1$s AS b'
            . ' WHERE b.ancestor_id = %2$d AND a.descendant_id = %3$d';

        $result = $this->getTable()->getDatabase()->execute(sprintf($query, $this->_relation_table_name, $this->id, $target_id));

        return $result;
    }

    //TODO: Optimize Levels.
    public function append($target_id, $draft = 0)
    {
        $query = 'INSERT INTO #__%1$s (ancestor_id, descendant_id, level, draft, created_on, created_by)'
            . ' VALUES (%2$d, %3$d, 1, '. $draft.', "'.gmdate('Y-m-d H:i:s').'", '.(int) JFactory::getUser()->get('id').')';

        $result = $this->getTable()->getDatabase()->execute(sprintf($query, $this->_relation_table_name, $target_id, $this->id));

        return $result;
    }

    public function deleteRelation($ancestor_id, $descendant_id)
    {
        $query = 'DELETE FROM #__%1$s'
            . ' WHERE ancestor_id = %2$d AND descendant_id = %3$d';

        $result = $this->getTable()->getDatabase()->execute(sprintf($query, $this->_relation_table_name, $ancestor_id, $descendant_id));
    }

    public function deleteDescendants($filter = false)
    {
        $database = $this->getTable()->getDatabase();
        $query = 'DELETE FROM #__'.$this->_relation_table_name.' WHERE ancestor_id = '.(int)$this->id;


        if (is_array($filter)) {
            $query .= ' AND descendant_id IN (SELECT taxonomy_taxonomy_id FROM #__taxonomy_taxonomies';
            $where = array();

            foreach($filter AS $key => $val) {
                $where[] = $database->quoteName($key)." = ".$database->quoteValue($val);
            }

            if (count($where)) {
                $query .= ' WHERE '.implode(' AND ', $where);
            }

            $query .= ')';
        }

        $result = $database->execute($query);

        return $result;
    }

    /**
     * Get relatives of the row
     *
     * @param string $type  ancestors or descendants
     * @param int    $level Filters results by the level difference between ancestor and the row, ComTaxonomyDatabaseRowNode::FETCH_ALL_LEVELS for all
     *
     * @return KDatabaseRowsetAbstract
     */
    public function getRelatives($config = array())
    {
        $config = new KConfig($config);

        $config->append(array(
            'level' => ComTaxonomyDatabaseRowNode::FETCH_ALL_LEVELS,
            'limit' => 0,
        ));

        if (empty($config->type) || !in_array($config->type, array('ancestors', 'descendants'))) {
            throw new InvalidArgumentException('Unknown type value');
        }

        if (!$this->id && $config->type === 'ancestors') {
            return $this->getTable()->getRowset();
        }

        $id_column = $this->getTable()->getIdentityColumn();

        $join_column  = $config->type === 'ancestors' ? 'r.ancestor_id'   : 'r.descendant_id';
        $where_column = $config->type === 'ancestors' ? 'r.descendant_id' : 'r.ancestor_id';

        $query = $this->getTable()->getDatabase()->getQuery();

        $query->select('tbl.*')
            ->select('COUNT(crumbs.ancestor_id) AS level')
            ->from('#__'.$this->_table_name.' AS tbl')
            ->join('inner', '#__'.$this->_relation_table_name.' AS crumbs', 'crumbs.descendant_id = tbl.'.$id_column)
            ->order('crumbs.created_on', 'DESC')
            ->limit($config->limit)
        ;

//        $query->select('GROUP_CONCAT(DISTINCT(crumbs.descendant_id) ORDER BY crumbs.level DESC SEPARATOR \',\') AS descendants');
//        $query->select('GROUP_CONCAT(DISTINCT(crumbs.descendant_id) WHERE crumbs.type = "reply" ORDER BY crumbs.level DESC SEPARATOR \',\') AS descendants');
//        $query->select('GROUP_CONCAT(DISTINCT(crumbs.ancestor_id) ORDER BY crumbs.level DESC SEPARATOR \',\') AS ancestors');

        $query->group($config->groupby ? 'crumbs.'.$config->groupby : 'tbl.'.$id_column);

        if(isset($config->filter)) {
            foreach($config->filter as $key => $value) {
                if(isset($value)) {
                    if(is_object($value)) {
                        $value = $value->toArray();
                    }
                    $query->where('tbl.'.$key, 'IN', $value);
                }
            }
        }

        //TODO: Make dynamic!
        if($config->ancestors) {
            //TODO: Filter own id?
            $query->select('GROUP_CONCAT(DISTINCT(crumbs.ancestor_id) ORDER BY crumbs.level DESC SEPARATOR \',\') AS ancestors');

            foreach($config->ancestors as $key => $ancestor) {
                $not = $key == 'not' ? 'NOT' : '';
                $havings = array();

                $i = 0;
                foreach($ancestor as $value) {
                    if($i === 0) {
                        $havings[$i] = ''.$not.' (FIND_IN_SET('.$value.', LOWER(ANCESTORS)))';
                    } else {
                        $havings[$i] = 'AND '.$not.' (FIND_IN_SET('.$value.', LOWER(ANCESTORS)))';
                    }
                    $i++;
                }

                $having = implode(' ', $havings);

                $query->having($having);
            }
        }

        if ($config->level !== ComTaxonomyDatabaseRowNode::FETCH_ALL_LEVELS) {
            if ($this->id) {
                $query->where('r.level', 'IN', $config->level);
            } else {
                $query->having('level IN ('.implode(',', (array) $config->level).')');
            }
        }

        if ($this->id) {
            $query->select('r.draft AS draft');
            $query->join('inner', '#__'.$this->_relation_table_name.' AS r', $join_column.' = crumbs.descendant_id')
                ->where($where_column, 'IN', $this->id)
                ->where('tbl.taxonomy_taxonomy_id', 'NOT IN', $this->id);
        }

        return $this->getTable()->select($query, KDatabase::FETCH_ROWSET);
    }

    /**
     * Returns the siblings of the row
     *
     * @return KDatabaseRowAbstract
     */
    public function getSiblings($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'level' => 1,
        ));

        $parent = $this->getParent($config);

        return $parent ? $parent->getDescendants($config) : $this->getTable()->getRow()->getDescendants($config);
    }

    /**
     * Returns the first ancestor of the row
     *
     * @return KDatabaseRowAbstract|null Parent row or null if there is no parent
     */
    public function getParent($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'type' => 'ancestors',
            'level' => 1,
        ));

        return $this->getRelatives($config)->top();
    }

    /**
     * Get ancestors of the row
     *
     * @param int $level Filters results by the level difference between ancestor and the row, ComTaxonomyDatabaseRowNode::FETCH_ALL_LEVELS for all
     *
     * @return KDatabaseRowsetAbstract A rowset containing all ancestors
     */
    public function getAncestors($config)
    {
        $config = new KConfig($config);
        $config->append(array(
            'type' => 'ancestors',
            'level' => ComTaxonomyDatabaseRowNode::FETCH_ALL_LEVELS,
        ));

        return $this->getRelatives($config);
    }

    /**
     * Get descendants of the row
     *
     * @param int|array $level Filters results by the level difference between descendant and the row, ComTaxonomyDatabaseRowNode::FETCH_ALL_LEVELS for all
     *
     * @return KDatabaseRowsetAbstract A rowset containing all descendants
     */
    public function getDescendants($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'type' => 'descendants',
            'level' => ComTaxonomyDatabaseRowNode::FETCH_ALL_LEVELS,
        ));

        return $this->getRelatives($config);
    }

    /**
     * Checks if the given row is a descendant of this one
     *
     * @param  int|object $target Either an integer or an object with id property
     * @return boolean
     */
    public function isDescendantOf($target)
    {
        $target_id = is_object($target) ? $target->id : $target;

        return $this->_checkRelationship($this->id, $target_id);
    }

    /**
     * Checks if the given row is an ancestor of this one
     *
     * @param  int|object $target Either an integer or an object with id property
     * @return boolean
     */
    public function isAncestorOf($target)
    {
        $target_id = is_object($target) ? $target->id : $target;

        return $this->_checkRelationship($target_id, $this->id);
    }

    /**
     * Checks if an ID is descendant of another
     *
     * @param int $descendant Descendant ID
     * @param int $ancestor   Ancestor ID
     *
     * @return boolean True if descendant is a child of the ancestor
     */
    protected function _checkRelationship($descendant, $ancestor)
    {
        if (empty($this->id)) {
            return false;
        }

        $query = $this->getTable()->getDatabase()->getQuery();
        $query->select('COUNT(*)')
            ->from('#__'.$this->_relation_table_name.' AS r')
            ->where('r.descendant_id', '=', (int) $descendant)
            ->where('r.ancestor_id', '=', (int) $ancestor);

        return (bool) $this->getTable()->select($query, KDatabase::FETCH_FIELD);
    }

    public function __get($property)
    {
        if ($property === 'parent_ids') {
            $pieces = array_map('intval', explode('/', $this->path));
            array_pop($pieces);

            return $pieces;
        }

        if ($property === 'parent_path') {
            return substr($this->path, 0, strrpos($this->path, '/'));
        }

        if ($property === 'slug_path' && empty($this->_data['slug_path'])) {
            $this->_data['slug_path'] = $this->getSlugPath();
        }

        return parent::__get($property);
    }

    public function getSlugPath()
    {
        $query = $this->getTable()->getDatabase()->getQuery();
        $query->select('GROUP_CONCAT(c.title SEPARATOR \'/\')')
            ->from('#__'.$this->_relation_table_name.' AS r')
            ->join('left', $this->_table_name.' AS c', 'c.taxonomy_taxonomy_id = r.ancestor_id')
            ->where('r.descendant_id', '=', (int) $this->id)
            ->order('r.level', 'desc')
        ;

        return $this->getTable()->select($query, KDatabase::FETCH_FIELD);
    }
}