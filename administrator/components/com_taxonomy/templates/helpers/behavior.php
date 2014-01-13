<?php

class ComTaxonomyTemplateHelperBehavior extends ComDefaultTemplateHelperBehavior
{
    public function select2($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'element' => '.select2-listbox',
            'options' => array(
                'width' => 'resolve',
                'dropdownCssClass' => 'com_taxonomy'
            )
        ));

        $html ='';

        if (!isset(self::$_loaded['jquery'])) {
//            $html .= $this->jquery();
        }

        if (!isset(self::$_loaded['select2'])) {

            $html .= '<script src="media://com_taxonomy/js/select2.js" />';

            $html .= '<script>jQuery.noConflict()(function($){
                $("'.$config->element.'").select2('.$config->options.');
            });</script>';

            if(isset(self::$_loaded['validator']))
            {
                $html .= '<script src="media://com_taxonomy/js/select2.validator.js" />';

                $html .= '<script>jQuery.noConflict()(function($){
                    $("'.$config->element.'").select2(\'container\').removeClass(\'required\');
                });</script>';
            }

            self::$_loaded['select2'] = true;
        }

        return $html;
    }

    /*
     * Overriden to make the validator support Select2
     */
    public function validator($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'options'  => array(
                'fieldSelectors' => 'input, select, textarea, .select2-container'
            )
        ));

        return parent::validator($config);
    }

    public function modal($config = array())
    {
        $config = new KConfig($config);
        $config->append(array(
            'selector' => 'a.modal-button',
            'options'  => array('disableFx' => true)
        ));

        return JHTML::_('behavior.modal', $config->selector, $config->toArray());
    }
}
