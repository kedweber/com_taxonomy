<?php

/**
 * Provides ordering support for closure tables by the help of another table
 */
class ComTaxonomyDatabaseBehaviorOrderable extends KDatabaseBehaviorAbstract
{
    protected function _initialize(KConfig $config)
    {
        $config->append(array(
            'priority'   => KCommand::PRIORITY_LOWEST
        ));

        parent::_initialize($config);
    }

    protected function _afterTableInsert(KCommandContext $context)
    {
        $list = $context->data->getSiblings();

        $orderings = array(
            'title' => array(),
            'created_on' => array(),
            'custom' => array()
        );

        $orders = $this->getService('com://admin/taxonomy.model.taxonomy_orderings')
                    ->id($list->getColumn('id'))->sort('custom')->direction('asc')->getList();

        $custom_values = $orders->getColumn('custom');
        $next_order = ($custom_values ? max($custom_values) : 0)+1;

        foreach ($list as $child) {
            $orderings['title'][$child->id] = $child->title;
            $orderings['created_on'][$child->id] = $child->created_on;
            $orderings['custom'][$child->id] = isset($custom_values[$child->id]) ? $custom_values[$child->id] : $next_order++;
        }

        if ($this->order) {
            // Pre-sort custom values
            asort($orderings['custom']);

            $id = $context->data->id;
            $keys = array_keys($orderings['custom']);
            $position = array_search($id, $keys);

            if ($this->order == 1 && $position+1 < count($keys)) {
                $switch_id = $keys[$position+1];
            } elseif ($this->order == -1 && $position-1 >= 0) {
                $switch_id = $keys[$position-1];
            }

            if (isset($switch_id)) {
                $tmp = $orderings['custom'][$switch_id];
                $orderings['custom'][$switch_id] = $orderings['custom'][$id];
                $orderings['custom'][$id] = $tmp;
            }
        }

        // Sort before saving orders
        foreach ($orderings as &$array) {
            asort($array);
        }

        foreach ($list as $item) {
            $order = $orders->find($item->id);
            if (!$order) {
                $order = $orders->getRow();
                $order->id = $item->id;
            }

            foreach (array_keys($orderings) as $key) {
                $order->{$key} = array_search($item->id, array_keys($orderings[$key])) + 1;
            }

            $order->save();
        }
    }

    protected function _afterTableUpdate(KCommandContext $context)
    {
        return $this->_afterTableInsert($context);
    }

    protected function _afterTableDelete(KCommandContext $context)
    {
        $this->getService('com://admin/taxonomy.model.taxonomy_orderings')
        ->id($context->data->id)
        ->getItem()
        ->delete();
    }
}
