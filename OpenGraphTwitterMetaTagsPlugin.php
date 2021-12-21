<?php

define('OG_TWITTER_METATAGS_PLUGIN_DIR', __DIR__);
define('OG_TWITTER_METATAGS_PLUGIN_OPTION', 'twitter_handle');

class OpenGraphTwitterMetatagsPlugin extends Omeka_Plugin_AbstractPlugin
{
  protected $_hooks =array(
    'uninstall',
    'public_head',
    'config',
    'config_form'
  );

  public function hookUninstall()
  {
    delete_option(OG_TWITTER_METATAGS_PLUGIN_OPTION);
  }

  public function hookPublicHead($args)
  {
    /**
     * In general, we need to figure out
     * how to populate 3 fields, all strings:
     *
     * 1. Title
     * 2. Description
     * 3. Representative image URL
     */

    // Initialize variables to hold eventual values
    $title = '';
    $description = '';
    $imageUrl = '';

    // Set couple parameters
    define('TITLE_MAX_LENGTH', 80);
    define('DESCRIPTION_MAX_LENGTH', 160);

    /**
     * Now, it depends on the page type what data to grab.
     *
     * 1. We can be dealing with a single record:
     * - collection overview page
     * - single item page
     * - exhibit page + exhibit landing page
     * - file page
     *
     * 2. or/and a collection of records:
     * - list of items
     * - list of collections
     * - list of exhibits
     *
     * 3. It could be something else
     * - search results
     * - simple page
     */

    // 1.
    $collection = get_current_record('collection', false);
    $item = get_current_record('item', false);
    $exhibit = get_current_record('exhibit', false);
    $exhibitPage = get_current_record('exhibit_page', false);
    $file = get_current_record('file', false);
    $page = get_current_record('simple_pages_page', false);

    if ($collection || $item || $exhibit || $file || $page) {

      // Collection overview page (collection/show/:id)
      if ($collection) {
        // todo: prepend with "Collection:"?
        $title = metadata(
          'collection',
          array('Dublin Core', 'Title'),
          array('no_escape' => true, 'snippet' => TITLE_MAX_LENGTH)
        );

        $description = metadata(
          'collection',
          array('Dublin Core', 'Description'),
          array('no_escape' => true, 'snippet' => DESCRIPTION_MAX_LENGTH)
        );

        // This gets representative file for collection
        $collectionFile = $collection->getFile();

        if ($collectionFile && $collectionFile->hasFullsize()) {
          $imageUrl = file_display_url($collectionFile);
        }
      }

      // Single item and not a collection (because collections/show/:id
      // has both 'collection' and 'item' set as current record; todo: why?
      if ($item && !$collection) {
        $title = metadata(
          'item',
          array('Dublin Core', 'Title'),
          array('no_escape' => true, 'snippet' => TITLE_MAX_LENGTH)
        );

        $description = metadata(
          'item',
          array('Dublin Core', 'Description'),
          array('no_escape' => true, 'snippet' => DESCRIPTION_MAX_LENGTH)
        );

        $itemFile = $item->getFile();

        if ($itemFile && $itemFile->hasFullsize()) {
          $imageUrl = file_display_url($itemFile);
        }
      }

      // Exhibit page or exhibit landing page
      if ($exhibit) {
        $title = metadata(
          $exhibit,
          'title',
          array('no_escape' => true, 'snippet' => TITLE_MAX_LENGTH)
        );

        $description = metadata(
          $exhibit,
          'description',
          array('no_escape' => true, 'snippet' => DESCRIPTION_MAX_LENGTH)
        );

        $exhibitFile = $exhibit->getFile();

        if ($exhibitFile && $exhibitFile->hasFullsize()) {
          $imageUrl = file_display_url($exhibitFile);
        }
      }

      if ($exhibitPage) {
        $title = metadata(
          $exhibitPage,
          'title',
          array('no_escape'=> true, 'snippet' => TITLE_MAX_LENGTH)
        );

        $description = 'An Open Door Archive exhibit page';

        // Get page blocks
        $pageBlocks = $exhibitPage->getPageBlocks();
        shuffle($pageBlocks);

        foreach ($pageBlocks as $block) {
          $attachments = $block->getAttachments();

          // We're interested only in those with attachments...
          if ($attachments) {
            foreach ($attachments as $attachment) {
              $attachmentFile = $attachment->getFile();

              // ...that have representative image
              if ($attachmentFile) {
                $imageUrl = file_display_url($attachmentFile);
                break;
              }
            }
          }

          if ($imageUrl) {
            break;
          }
        }
      }

      // File page
      if ($file) {
        $title = metadata(
          $file,
          'display_title',
          array('no_escape' => true, 'snippet' => TITLE_MAX_LENGTH)
        );

        $description = 'A file from the Open Door Archive collection.';

        $fileFile = $file->getFile();

        if ($fileFile && $fileFile->hasFullsize()) {
          $imageUrl = file_display_url($fileFile);
        }
      }

      // Simple page
      if ($page) {
        $title = metadata(
          'simple_pages_page',
          'title',
          array('no_escape' => true, 'snippet' => TITLE_MAX_LENGTH)
        );

        $description = 'Open Door Archive page.';
      }

      // 2.
    } else if (
      has_loop_records('collection') ||
      has_loop_records('item') ||
      has_loop_records('exhibit')
    ) {
      // TODO: add case for the lists
      // echo 'HAS LOOP RECORDS';
    } else {
      // TODO: what else is possible?
      // 3.
      // echo 'NO RECORDS FOUND';
    }

    // If no title found above, then fall back to the defaults for the site...
    if (!$title) {
      $title = option('site_title');
      $description = option('description');

      if (!$imageUrl) {

        // ...and use one of the featured units image
        $featuredItems = get_random_featured_items(5, true);

        if (count($featuredItems) > 0) {
          foreach ($featuredItems as $featuredItem) {
            $featuredItemFile = $featuredItem->getFile();

            if ($featuredItemFile && $featuredItemFile->hasFullsize()) {
              $imageUrl = file_display_url($featuredItemFile);
              break;
            }
          }
        }
      }
    }

    // Finally, writing meta tags
    echo '<meta name="twitter:card" content="summary_large_image">';
    echo '<meta name="twitter:site" content="@', get_option(OG_TWITTER_METATAGS_PLUGIN_OPTION), '">';

    if ($title) {
      $title = strip_tags($title);
      echo '<meta name="twitter:title" content="', $title, '">';
      echo '<meta name="og:title" content="', $title, '">';

      if ($description) {
        $description = strip_tags($description);
        echo '<meta name="twitter:description" content="', $description, '">';
        echo '<meta name="og:description" content="', $description, '">';
      }

      if ($imageUrl) {
        echo '<meta name="twitter:image" content="', $imageUrl, '">';
        echo '<meta name="og:image" content="', $imageUrl, '">';
      }
    }
  }

  public function hookConfig($args)
  {
    $post = $args['post'];
    set_option(
      OG_TWITTER_METATAGS_PLUGIN_OPTION,
      $post[OG_TWITTER_METATAGS_PLUGIN_OPTION]
    );
  }

  public function hookConfigForm()
  {
    include OG_TWITTER_METATAGS_PLUGIN_DIR . '/config_form.php';
  }
}
