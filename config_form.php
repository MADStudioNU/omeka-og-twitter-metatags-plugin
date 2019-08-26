<?php $view = get_view(); ?>

<div>
  <div class="field">
    <?php echo $view->formLabel(OG_TWITTER_METATAGS_PLUGIN_OPTION, 'Twitter Handle for the site:'); ?>
    <div class="inputs">
      <?php echo $view->formText(OG_TWITTER_METATAGS_PLUGIN_OPTION, get_option(OG_TWITTER_METATAGS_PLUGIN_OPTION));?>
    </div>
  </div>
</div>



