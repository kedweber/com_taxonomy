<? defined('KOOWA') or die; ?>

<?= @helper('behavior.mootools'); ?>

<script src="media://lib_koowa/js/koowa.js" />

<form action="" method="post" class="-koowa-form">
    <div id="main" class="grid_8">
        <div class="panel title clearfix">
            <input class="inputbox required" type="text" name="title" id="title" size="40" maxlength="255" value="<?= $taxonomy->title ?>" placeholder="<?= @text('Title') ?>" />
            <label for="slug"><?= @text('Slug') ?></label>
            <input class="inputbox" type="text" name="slug" id="slug" size="40" maxlength="255" value="<?= $taxonomy->slug ?>" placeholder="<?= @text('Slug') ?>" />
        </div>

        <?= @editor(array(
            'name' => 'description',
            'id' => 'description',
            'width' => '100%',
            'height' => '391',
            'cols' => '100',
            'rows' => '20',
            'buttons' => array('pagebreak', 'readmore')
        )); ?>
    </div>
    <div id="panels" class="grid_4">
        <div class="panel">
            <h3><?= @text('Details') ?></h3>
            <table class="paramlist admintable">
                <tr>
                    <td class="paramlist_key">
                        <label><?= @text('Parent') ?></label>
                    </td>
                    <td>
                        <?= @helper('com://admin/taxonomy.template.helper.listbox.taxonomies', array(
                            'deselect' => true,
                            'check_access' => true,
                            'name' => 'parent_id',
                            'attribs' => array('data-placeholder' => JText::_('Select a '. ucfirst(KInflector::singularize(KRequest::get('get.view', 'string')))), 'class' => 'select2-listbox'),
                            'selected' => $parent ? $parent->id : null,
                            'ignore' => $taxonomy->id ? array_merge($taxonomy->getDescendants()->getColumn('id'), array($taxonomy->id)) : array(),
                            'filter' => array('section' => $state->section)
                        )); ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <input type="hidden" name="section" value="<?= $state->section; ?>" />
</form>