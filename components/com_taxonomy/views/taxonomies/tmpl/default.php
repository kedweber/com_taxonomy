<?php
/**
 * Com
 *
 * @author      Dave Li <dave@moyoweb.nl>
 * @category    Nooku
 * @package     Socialhub
 * @subpackage  ...
 * @uses        Com_
 */
 
defined('KOOWA') or die('Protected resource'); ?>

<? $i = 1; ?>
<? foreach($taxonomies as $taxonomy) : ?>
    <article>
        <header>
            <h1><a href="<?= @route('option='.$taxonomy->option.'&view='.KInflector::singularize($taxonomy->view).'&id='.$taxonomy->id.'&format=html'); ?>"><?= $taxonomy->title; ?></a></h1>
        </header>
        <div class="meta">
            <span class="small"><span class="clementine"><?= @text('Posted'); ?>:</span> <?= date('l, d F Y', strtotime($taxonomy->created_on)); ?></span>
        </div>
        <div class="body row no-gutter">
            <div class="col-md-4">
                <?= @service('com://admin/cloudinary.controller.image')->path($taxonomy->image)->width(350)->height(200)->quality(80)->attribs(array('class' => 'img-responsive'))->cache(0)->display(); ?>
            </div>
            <div class="col-md-8">
                    <span style="font-family: 'Open Sans';">
                        <?= $taxonomy->introtext; ?>
                        <a class="readmore" href="<?= @route('option='.$taxonomy->option.'&view='.KInflector::singularize($taxonomy->view).'&id='.$taxonomy->id.'&format=html'); ?>"><?= @text('Read more'); ?></a>
                    </span>
            </div>
        </div>
        <footer class="clearfix" <? if($i === count($taxonomies)) echo 'style="border-bottom: none;"' ?>>
            <span class="comment-count pull-right">Comments: <a class="readmore" data-disqus-identifier="<?= $taxonomy->uuid; ?>" href="<?= @route('view=article&id='.$taxonomy->id.'#disqus_thread'); ?>">Loading</a></span>
        </footer>
    </article>
    <? $i++; ?>
<? endforeach; ?>