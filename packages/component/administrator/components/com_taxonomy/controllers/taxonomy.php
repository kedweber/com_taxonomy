<?php

class ComTaxonomyControllerTaxonomy extends ComDefaultControllerDefault
{
    protected function _initialize(KConfig $config)
    {
        $config->append(array(
            'model' => 'com://admin/taxonomy.model.taxonomies',
            'persistent' => true,
        ));

        parent::_initialize($config);
    }

    protected function _actionGet(KCommandContext $context)
    {
        $view = $this->getView();

        //Set the layout
        if($view instanceof KViewTemplate)
        {
            if(KInflector::isPlural($view->getName())) {
                $view->getIdentifier()->path[1] = 'taxonomies';
            } else {
                $view->getIdentifier()->path[1] = 'taxonomy';
            }

            $layout = clone $view->getIdentifier();
            $layout->package  = 'taxonomy';
            $layout->name     = $view->getLayout();

            //Force re-creation of the filepath to load the category templates
            $layout->filepath = '';

            $view->setLayout($layout);
        }

        return parent::_actionGet($context);
    }
}