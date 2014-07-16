<?php

class ComTaxonomyViewTaxonomyHtml extends ComDefaultViewHtml
{
    public function display()
    {
        $this->assign('parent', $this->getModel()->getItem()->getParent());

        return parent::display();
    }
}
