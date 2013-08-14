<? defined('KOOWA') or die; ?>

<?= @helper('behavior.mootools'); ?>

<script src="media://lib_koowa/js/koowa.js" />
<style src="media://lib_koowa/css/koowa.css" />

<form action="<?= @route() ?>" method="get" class="-koowa-grid">
    <table class="adminlist">
        <thead>
            <tr>
                <th style="text-align: center;" width="1">
                    <?= @helper('grid.checkall')?>
                </th>
                <th>
                    <?= @helper('grid.sort', array('column' => 'title', 'title' => @text('Title'), 'direction' => 'asc')) ?>
                </th>
                <th style="text-align: center;" width="5%">
                    <?= @helper('grid.sort', array('column' => 'enabled', 'title' => @text('Enabled'), 'direction' => 'asc')) ?>
                </th>
                <th style="text-align: right;" width="10%">
                    <?= @helper('grid.sort', array('column' => 'custom', 'title' => @text('Ordering'), 'direction' => 'asc')) ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <? foreach($taxonomies as $taxonomy) : ?>
            <tr>
                <td style="text-align: center;">
                    <?= @helper('grid.checkbox', array('row'=> $taxonomy)); ?>
                </td>
                <td>
                    <span style="padding-left: <?= ($taxonomy->level) * 15 ?>px" class="editlinktip hasTip"
                        title="<?= @text('Edit')?> <?= @escape($taxonomy->title); ?>">
                        <a href="<?= @route('view=taxonomy&id='.$taxonomy->id)?>">
                            <?= @escape($taxonomy->title) ?>
                        </a>
                    </span>
                </td>
                <td style="text-align: center;">
                    <?= @helper('grid.enable', array('row' => $taxonomy)) ?>
                </td>
                <td style="text-align: right;">
                    <?= @helper('grid.order', array('row' => $taxonomy, 'total' => $total)) ?>
                </td>
            </tr>
            <? endforeach ?>
            <? if(!count($taxonomies)) : ?>
            <tr>
                <td colspan="4" style="text-align: center;">
                    <?= @text('No ' . KRequest::get('get.view', 'string') . ' found.') ?>
                </td>
            </tr>
            <? endif ?>
        </tbody>

        <? if (count($taxonomies)): ?>
        <tfoot>
            <tr>
                <td colspan="4">
                    <?= @helper('paginator.pagination', array('total' => $total)) ?>
                </td>
            </tr>
        </tfoot>
        <? endif; ?>
    </table>
</form>