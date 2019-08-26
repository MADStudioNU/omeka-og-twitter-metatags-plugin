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
    $image_url = '';

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

    //
    $collection = get_current_record('collection', false);
    $item = get_current_record('item', false);
    $exhibit = get_current_record('exhibit', false);
    $file = get_current_record('file', false);

    echo $collection ? 'collection-YES' : 'collection-NO';
    echo $item ? 'item-YES' : 'item-NO';
    echo $exhibit ? 'exhibit-YES' : 'exhibit-NO';
    echo $file ? 'file-YES' : 'file-NO';

    // 1.
    if ($collection || $item || $exhibit || $file) {
      echo 'COL-ITE-EXH-FIL';

      // Collection overview page (collection/show/:id)
      if ($collection) {
        $title = metadata('collection', array('Dublin Core', 'Title'));
        $description = metadata('collection', array('Dublin Core', 'Description'));

        // This gets representative file for collection
        $file = $collection->getFile();

        if ($file) {
          $image_url = file_display_url($file, 'fullsize');
        }
      }

      // Single item
      if ($item) {
        $title = metadata('item', array('Dublin Core', 'Title'));
        $description = metadata('item', array('Dublin Core', 'Description'));

        if (strlen($title) > 0 && strlen($description) > 0) {
          foreach (loop('files', $item->Files) as $file) {
            if ($file->hasThumbnail()){
              $image_url = file_display_url($file, 'thumbnail');
              var_dump($image_url);
              break;
            }
          }
        }
      }

      // Exhibit page or exhibit landing page
      if ($exhibit) {
        $title = metadata($exhibit, 'title', array('no_escape' => false));
        $description = metadata($exhibit, 'description', array('no_escape' => false));

        $file = $exhibit->getFile();

        if ($file) {
          $image_url = file_display_url($file, 'fullsize');
          print_r($image_url);
        }
      }

      // File page
      if ($file) {
        $title = metadata($file, 'original_filename');
        $description = 'A file from the Open Door Archive.';

        if ($f = $file->getFile()) {
          $image_url = file_display_url($f);
        }
      }

      // 2.
    } else if (
      has_loop_records('collection') ||
      has_loop_records('item') ||
      has_loop_records('exhibit')
    ) {
      echo 'SOME KIND OF LIST';
    } else {

      // 3.
      echo 'Something else';
    }

    var_dump(has_loop_records('collection'));
    var_dump(has_loop_records('item'));
    var_dump(has_loop_records('exhibit'));
    var_dump(has_loop_records('file'));
    var_dump(has_loop_records('page'));

    var_dump(count(get_loop_records('collection', false)));
    var_dump(count(get_loop_records('item', false)));
    var_dump(count(get_loop_records('exhibit', false)));

    // Default to the site settings if we didn't find anything else to use
    if ($title === '' || $description === '') {
      $title = option('site_title');
      $description = option('description');
      $items = get_random_featured_items(1, true);

      if (isset($items[0])){
        foreach (loop('files', $items[0]->Files) as $file){
          if($file->hasThumbnail()){
            $image_url = file_display_url($file, 'thumbnail');
            break;
          }
        }
      }
    }

    // Now let's write the tags
    if (strlen($title) > 0 && strlen($description) > 0) {

      // Twitter Card
      echo '<meta property="twitter:card" content="summary" />';
      echo '<meta property="twitter:site" content="'.get_option(OG_TWITTER_METATAGS_PLUGIN_OPTION).'" />';
      echo '<meta property="twitter:title" content="'.strip_tags(html_entity_decode($title)).'" />'."\n";
      echo '<meta property="twitter:description" content="'.strip_tags(html_entity_decode($description)).'" />'."\n";

      if (strlen($image_url) > 0) {
        echo '<meta property="twitter:image" content="'.$image_url.'" />'."\n";
      }

      // OpenGraph
      echo '<meta property="og:title" content="'.strip_tags(html_entity_decode($title)).'" />'."\n";
      echo '<meta property="og:description" content="'.strip_tags(html_entity_decode($description)).'" />'."\n";

      if (strlen($image_url) > 0){
        echo '<meta property="og:image" content="'.$image_url.'" />'."\n";
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
