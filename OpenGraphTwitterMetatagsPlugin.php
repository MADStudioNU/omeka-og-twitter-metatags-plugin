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
    $file = get_current_record('file', false);

    if ($collection || $item || $exhibit || $file) {

      // Collection overview page (collection/show/:id)
      if ($collection) {
        $title = metadata('collection', array('Dublin Core', 'Title'));
        $description = metadata('collection', array('Dublin Core', 'Description'));

        // This gets representative file for collection
        $collectionFile = $collection->getFile();

        if ($collectionFile && $collectionFile->hasFullsize()) {
          $imageUrl = file_display_url($collectionFile);
        }
      }

      // Single item and not a collection (because collections/show/:id
      // has both 'collection' and 'item' set as current record; todo: why?)
      if ($item && !$collection) {
        $title = metadata('item', array('Dublin Core', 'Title'));
        $description = metadata('item', array('Dublin Core', 'Description'));

        $itemFile = $item->getFile();

        if ($itemFile && $itemFile->hasFullsize()) {
          $imageUrl = file_display_url($itemFile);
        }
      }

      // Exhibit page or exhibit landing page
      if ($exhibit) {
        $title = metadata($exhibit, 'title', array('no_escape' => false));
        $description = metadata($exhibit, 'description');

        $exhibitFile = $exhibit->getFile();

        if ($exhibitFile && $exhibitFile->hasFullsize()) {
          $imageUrl = file_display_url($exhibitFile);
        }
      }

      // File page
      if ($file) {
        $title = metadata($file, 'display_title');
        $description = 'A file from the Open Door Archive collection.';

        $fileFile = $file->getFile();

        if ($fileFile && $fileFile->hasFullsize()) {
          $imageUrl = file_display_url($fileFile);
        }
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

    // Finally, writing meta tags
    echo '<meta name="twitter:card" content="summary_large_image">';
    echo '<meta name="twitter:site" content="@', get_option(OG_TWITTER_METATAGS_PLUGIN_OPTION), '">';

    if ($title) {
      echo '<meta name="twitter:title" content="', strip_tags($title), '">';
      echo '<meta name="og:title" content="', htmlentities(strip_tags(html_entity_decode($title))), '">';

      if ($description) {
        echo '<meta name="twitter:description" content="', strip_tags($description), '">';
        echo '<meta name="og:description" content="', htmlentities(strip_tags(html_entity_decode($description))), '">';
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
