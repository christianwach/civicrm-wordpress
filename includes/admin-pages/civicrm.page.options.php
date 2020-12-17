<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 */

// This file must not accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Define CiviCRM_For_WordPress_Admin_Page_Options Class.
 *
 * @since 5.34
 */
class CiviCRM_For_WordPress_Admin_Page_Options {

  /**
   * @var object
   * Plugin object reference.
   * @since 5.34
   * @access public
   */
  public $civi;

  /**
   * @var object
   * Admin object reference.
   * @since 5.34
   * @access public
   */
  public $admin;

  /**
   * Instance constructor.
   *
   * @since 5.34
   */
  public function __construct() {

    // Store reference to CiviCRM plugin object.
    $this->civi = civi_wp();

    // Store reference to admin object.
    $this->admin = civi_wp()->admin;

    // Wait for admin class to register hooks.
    add_action('civicrm/admin/hooks/registered', [$this, 'register_hooks']);

  }

  /**
   * Register hooks.
   *
   * @since 5.34
   */
  public function register_hooks() {

    // Add items to the CiviCRM admin menu.
    add_action('admin_menu', [$this, 'add_menu_items'], 9);

    // Add our meta boxes.
    add_action('add_meta_boxes', [$this, 'meta_boxes_options_add']);

    // Add AJAX handlers.
    add_action('wp_ajax_civicrm_basepage', [$this, 'ajax_save_basepage']);
    add_action('wp_ajax_civicrm_email_sync', [$this, 'ajax_save_email_sync']);
    add_action('wp_ajax_civicrm_clear_caches', [$this, 'ajax_clear_caches']);

  }

  /**
   * Adds CiviCRM sub-menu items to WordPress admin menu.
   *
   * @since 5.34
   */
  public function add_menu_items() {

    // Add Settings submenu item.
    $options_page = add_submenu_page(
      'CiviCRM',
      __('CiviCRM Settings for WordPress', 'civicrm'),
      __('Settings', 'civicrm'),
      'access_civicrm',
      'civi_options',
      [$this, 'page_options']
    );

    // Add resources prior on page load.
    add_action('admin_head-' . $options_page, [$this, 'admin_head']);
    add_action('admin_print_styles-' . $options_page, [$this, 'admin_css']);

  }

  /**
   * Enqueue scripts on the pages that need them.
   *
   * @since 5.34
   */
  public function admin_head() {

    // Enqueue WordPress scripts.
    wp_enqueue_script('common');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('dashboard');

    // Enqueue Javascript.
    wp_enqueue_script(
      'civicrm-options-script',
      CIVICRM_PLUGIN_URL . 'assets/js/civicrm.options.js',
      ['jquery'],
      CIVICRM_PLUGIN_VERSION
    );

    // Init settings and localisation array.
    $vars = [
      'settings' => [
        'ajax_url' => admin_url('admin-ajax.php'),
      ],
      'localisation' => [
        'saving' => __('Saving...', 'civicrm'),
        'saved' => __('Saved', 'civicrm'),
        'update' => __('Update', 'civicrm'),
        'cache' => __('Clear Caches', 'civicrm'),
        'clearing' => __('Clearing...', 'civicrm'),
        'cleared' => __('Cleared', 'civicrm'),
      ],
    ];

    // Localise the WordPress way.
    wp_localize_script(
      'civicrm-options-script',
      'CiviCRM_Options_Vars',
      $vars
    );

  }

  /**
   * Enqueue stylesheet on this page.
   *
   * @since 5.34
   */
  public function admin_css() {

    // Enqueue common CSS.
    wp_enqueue_style(
      'civicrm-admin-styles',
      CIVICRM_PLUGIN_URL . 'assets/css/civicrm.admin.css',
      NULL,
      CIVICRM_PLUGIN_VERSION,
      'all'
    );

  }

  // ---------------------------------------------------------------------------
  // Page Loader
  // ---------------------------------------------------------------------------

  /**
   * Render the CiviCRM Settings page.
   *
   * @since 5.34
   */
  public function page_options() {

    // Get the current screen object.
    $screen = get_current_screen();

    /**
     * Allow meta boxes to be added to this screen.
     *
     * The Screen ID to use is: "civicrm_page_cwps_settings".
     *
     * @since 5.34
     *
     * @param str $screen_id The ID of the current screen.
     */
    do_action('add_meta_boxes', $screen->id, NULL);

    // Get the column CSS class.
    $columns = absint($screen->get_columns());
    $columns_css = '';
    if ($columns) {
      $columns_css = " columns-$columns";
    }

    // Include template file.
    include CIVICRM_PLUGIN_DIR . 'assets/templates/pages/page.options.php';

  }

  // ---------------------------------------------------------------------------
  // Meta Box Loaders
  // ---------------------------------------------------------------------------

  /**
   * Register Integration Page meta boxes.
   *
   * @since 5.34
   *
   * @param str $screen_id The Admin Page Screen ID.
   */
  public function meta_boxes_options_add($screen_id) {

    // Define valid Screen IDs.
    $screen_ids = [
      'civicrm_page_civi_options',
    ];

    // Bail if not the Screen ID we want.
    if (!in_array($screen_id, $screen_ids)) {
      return;
    }

    // Bail if user cannot access CiviCRM.
    if (!current_user_can('access_civicrm')) {
      return;
    }

    // Init data.
    $data = [];

    // Create "WordPress Base Page" metabox.
    add_meta_box(
      'civicrm_options_basepage',
      __('WordPress Base Page', 'civicrm'),
      [$this, 'meta_box_options_basepage_render'],
      $screen_id,
      'normal',
      'core',
      $data
    );

    // Create "Email Sync" metabox.
    add_meta_box(
      'civicrm_options_email',
      __('Contact Email to User Email Sync', 'civicrm'),
      [$this, 'meta_box_options_email_render'],
      $screen_id,
      'normal',
      'core',
      $data
    );

    // Create "Clear Cache" metabox.
    add_meta_box(
      'civicrm_options_cache',
      __('Clear Caches', 'civicrm'),
      [$this, 'meta_box_options_cache_render'],
      $screen_id,
      'normal',
      'core',
      $data
    );

    // Create "Useful Links" metabox.
    add_meta_box(
      'civicrm_options_emailinks',
      __('Useful Links', 'civicrm'),
      [$this, 'meta_box_options_links_render'],
      $screen_id,
      'side',
      'core',
      $data
    );

  }

  // ---------------------------------------------------------------------------
  // Meta Box Renderers
  // ---------------------------------------------------------------------------

  /**
   * Render "WordPress Base Page" meta box.
   *
   * @since 5.34
   *
   * @param mixed $unused Unused param.
   * @param array $metabox Array containing id, title, callback, and args elements.
   */
  public function meta_box_options_basepage_render($unused = NULL, $metabox) {

    // Get the setting.
    $basepage_slug = civicrm_api3('Setting', 'getvalue', [
      'name' => 'wpBasePage',
      'group' => 'CiviCRM Preferences',
    ]);

    // Did we get a value?
    if (!empty($basepage_slug)) {

      // Define the query for our Base Page.
      $args = [
        'post_type' => 'page',
        'name' => strtolower($basepage_slug),
        'post_status' => 'publish',
        'posts_per_page' => 1,
      ];

      // Do the query.
      $pages = get_posts($args);

    }

    // Default error message.
    $message = __('Could not find the WordPress Base Page.', 'civicrm');

    // Find the Base Page object.
    $basepage = NULL;
    if (!empty($pages) && is_array($pages)) {
      $basepage = array_pop($pages);
    }

    /*
    $e = new \Exception();
    $trace = $e->getTraceAsString();
    error_log(print_r([
      'method' => __METHOD__,
      'basepage' => $basepage,
      //'backtrace' => $trace,
    ], TRUE));
    */

    // Define the params for the Pages dropdown.
    $params = [
      'post_type' => 'page',
      'sort_column' => 'menu_order, post_title',
      'show_option_none' => __('- Select a Base Page -'),
    ];

    // If the Base Page is set, add its ID.
    if ($basepage instanceof WP_Post) {
      $params['selected'] = $basepage->ID;
    }

    // Set the value of the submit button.
    $value = esc_html__('Update', 'civicrm');
    if ($basepage instanceof WP_Post) {
      $value = esc_html__('Saved', 'civicrm');
    }

    // Determine whether the notice should be hidden.
    $hidden = '';
    if ($basepage instanceof WP_Post) {
      $hidden = ' display: none;';
    }

    // Set submit button options.
    $options = [
      'style' => 'float: right;',
      'data-security' => esc_attr(wp_create_nonce('civicrm_basepage')),
      'disabled' => NULL,
    ];

    // Include template file.
    include CIVICRM_PLUGIN_DIR . 'assets/templates/metaboxes/metabox.options.basepage.php';

  }

  /**
   * Render "Contact Email to User Email Sync" meta box.
   *
   * @since 5.34
   *
   * @param mixed $unused Unused param.
   * @param array $metabox Array containing id, title, callback, and args elements.
   */
  public function meta_box_options_email_render($unused = NULL, $metabox) {

    if (!$this->civi->initialize()) {
      return;
    }

    // Get the setting.
    $email_sync_select = civicrm_api3('Setting', 'getvalue', [
      'name' => 'syncCMSEmail',
      'group' => 'CiviCRM Preferences',
    ]);

    // Set selected attributes.
    $selected_yes = $email_sync_select ? 'selected="selected"' : '';
    $selected_no = $email_sync_select ? '' : 'selected="selected"';

    /*
    $e = new \Exception();
    $trace = $e->getTraceAsString();
    error_log(print_r([
      'method' => __METHOD__,
      'email_sync_select' => $email_sync_select ? 'yes' : 'no',
      //'backtrace' => $trace,
    ], TRUE));
    */

    // Set submit button options.
    $options = [
      'style' => 'float: right;',
      'data-security' => esc_attr(wp_create_nonce('civicrm_email_sync')),
      'disabled' => NULL,
    ];

    // Include template file.
    include CIVICRM_PLUGIN_DIR . 'assets/templates/metaboxes/metabox.options.email.php';

  }

  /**
   * Render "Clear Cache" meta box.
   *
   * @since 5.34
   *
   * @param mixed $unused Unused param.
   * @param array $metabox Array containing id, title, callback, and args elements.
   */
  public function meta_box_options_cache_render($unused = NULL, $metabox) {

    // Set submit button options.
    $options = [
      'style' => 'float: right;',
      'data-security' => esc_attr(wp_create_nonce('civicrm_clear_caches')),
    ];

    // Include template file.
    include CIVICRM_PLUGIN_DIR . 'assets/templates/metaboxes/metabox.options.cache.php';

  }

  /**
   * Render "Useful Links" meta box.
   *
   * @since 5.34
   *
   * @param mixed $unused Unused param.
   * @param array $metabox Array containing id, title, callback, and args elements.
   */
  public function meta_box_options_links_render($unused = NULL, $metabox) {

    if (!$this->civi->initialize()) {
      return;
    }

    // Construct URLs.
    $menu = $this->civi->admin->get_admin_link('civicrm/menu/rebuild', 'reset=1');
    $triggers = $this->civi->admin->get_admin_link('civicrm/menu/rebuild', 'reset=1&triggerRebuild=1');
    $upgrade = $this->civi->admin->get_admin_link('civicrm/upgrade', 'reset=1');
    $extensions = $this->civi->admin->get_admin_link('civicrm/admin/extensions', 'reset=1');
    $urls = $this->civi->admin->get_admin_link('civicrm/admin/setting/url', 'reset=1');
    $uploads = $this->civi->admin->get_admin_link('civicrm/admin/setting/path', 'reset=1');
    $permissions = $this->civi->admin->get_admin_link('civicrm/admin/access/wp-permissions', 'reset=1');

    // Include template file.
    include CIVICRM_PLUGIN_DIR . 'assets/templates/metaboxes/metabox.options.links.php';

  }

  // ---------------------------------------------------------------------------
  // AJAX Handlers
  // ---------------------------------------------------------------------------

  /**
   * Save the CiviCRM Basepage Setting.
   *
   * @since 5.34
   */
  public function ajax_save_basepage() {

    // Default response.
    $data = [
      'section' => 'basepage',
      'result' => '',
      'notice' => __('Unable to save the WordPress Base Page.', 'civicrm'),
      'message' => __('Please select a Page from the drop-down for CiviCRM to use as its Base Page. If CiviCRM was able to create one automatically, there should be one with the title "CiviCRM". If not, please select another suitable WordPress Page.', 'civicrm'),
      'saved' => FALSE,
    ];

    // Since this is an AJAX request, check security.
    $result = check_ajax_referer('civicrm_basepage', FALSE, FALSE);
    if ($result === FALSE) {
      $data['notice'] = __('Authentication failed. Unable to save the WordPress Base Page.', 'civicrm');
      wp_send_json($data);
    }

    /*
    $e = new \Exception();
    $trace = $e->getTraceAsString();
    error_log(print_r([
      'method' => __METHOD__,
      'POST' => $_POST,
      //'backtrace' => $trace,
    ], TRUE));
    */

    // Bail if there's no valid Post ID.
    $post_id = empty($_POST['value']) ? 0 : (int) trim($_POST['value']);
    if ($post_id === 0) {
      $data['notice'] = __('No Page ID detected. Unable to save the WordPress Base Page.', 'civicrm');
      wp_send_json($data);
    }

    // Bail if we don't find a post object.
    $post = get_post($post_id);
    if (!($post instanceof WP_Post)) {
      $data['notice'] = __('Could not find selected Page. Unable to save the WordPress Base Page.', 'civicrm');
      wp_send_json($data);
    }

    // Save the setting.
    civicrm_api3('Setting', 'create', [
      'wpBasePage' => $post->post_name,
    ]);

    // Retrieve the setting in case hook callbacks have altered it.
    // TODO: find out why this *doesn't* change when hooks *do* change it.
    $actual = civicrm_api3('Setting', 'getvalue', [
      'name' => 'wpBasePage',
      'group' => 'CiviCRM Preferences',
    ]);

    /*
    $e = new \Exception();
    $trace = $e->getTraceAsString();
    error_log(print_r([
      'method' => __METHOD__,
      'actual' => $actual,
      //'backtrace' => $trace,
    ], TRUE));
    */

    // Query for our Base Page.
    $pages = get_posts([
      'post_type' => 'page',
      'name' => strtolower($actual),
      'post_status' => 'publish',
      'posts_per_page' => 1,
    ]);

    // Bail if the Base Page was not found.
    if (empty($pages) || !is_array($pages)) {
      $data['notice'] = __('Could not get data for the selected Page.', 'civicrm');
      wp_send_json($data);
    }

    // Grab what should be the only item.
    $basepage = array_pop($pages);

    // Data response.
    $data = [
      'section' => 'basepage',
      'result' => $basepage->ID,
      'message' => __('It appears that your Base Page has been set. Looking good.', 'civicrm'),
      'saved' => TRUE,
    ];

    // Return the data.
    wp_send_json($data);

  }

  /**
   * Save the CiviCRM Email Sync Setting.
   *
   * @since 5.34
   */
  public function ajax_save_email_sync() {

    // Default response.
    $data = [
      'section' => 'email_sync',
      'message' => __('Could not save the selected setting.', 'civicrm'),
      'saved' => FALSE,
    ];

    // Since this is an AJAX request, check security.
    $result = check_ajax_referer('civicrm_email_sync', FALSE, FALSE);
    if ($result === FALSE) {
      $data['notice'] = __('Authentication failed. Could not save the selected setting.', 'civicrm');
      wp_send_json($data);
    }

    // Bail if there is no valid chosen value.
    $chosen = isset($_POST['value']) ? trim($_POST['value']) : 0;
    if ($chosen === 0) {
      $data['notice'] = __('Unrecognised parameter. Could not save the selected setting.', 'civicrm');
      wp_send_json($data);
    }

    // Setting is actually a boolean.
    $sync_email = $chosen === 'no' ? FALSE : TRUE;

    // Save the setting.
    civicrm_api3('Setting', 'create', [
      'syncCMSEmail' => $sync_email,
    ]);

    // Retrieve the setting in case hook callbacks have altered it.
    // TODO: find out why this *doesn't* change when hooks *do* change it.
    $actual = civicrm_api3('Setting', 'getvalue', [
      'name' => 'syncCMSEmail',
      'group' => 'CiviCRM Preferences',
    ]);

    /*
    $e = new \Exception();
    $trace = $e->getTraceAsString();
    error_log(print_r([
      'method' => __METHOD__,
      'result' => $result,
      'actual' => $actual,
      'result2' => $result2,
      //'backtrace' => $trace,
    ], TRUE));
    */

    // Data response.
    $data = [
      'section' => 'email_sync',
      'result' => $actual ? 'yes' : 'no',
      'message' => __('Setting saved.', 'civicrm'),
      'saved' => TRUE,
    ];

    // Return the data.
    wp_send_json($data);

  }

  /**
   * Clear the CiviCRM caches.
   *
   * @since 5.34
   */
  public function ajax_clear_caches() {

    // Default response.
    $data = [
      'section' => 'clear_caches',
      'notice' => __('Could not clear the CiviCRM caches.', 'civicrm'),
      'saved' => FALSE,
    ];

    // Since this is an AJAX request, check security.
    $result = check_ajax_referer('civicrm_clear_caches', FALSE, FALSE);
    if ($result === FALSE) {
      $data['notice'] = __('Authentication failed. Could not clear the CiviCRM caches.', 'civicrm');
      wp_send_json($data);
    }

    // Bail if there is no valid value.
    $chosen = isset($_POST['value']) ? (int) trim($_POST['value']) : 0;

    /*
    $e = new \Exception();
    $trace = $e->getTraceAsString();
    error_log(print_r([
      'method' => __METHOD__,
      '_POST' => $_POST,
      'chosen' => $chosen,
      //'backtrace' => $trace,
    ], TRUE));
    */

    if ($chosen !== 1) {
      $data['notice'] = __('Unrecognised parameter. Could not clear the CiviCRM caches.', 'civicrm');
      wp_send_json($data);
    }

    // Go ahead and clear the caches.
    $this->civi->admin->clear_caches();

    // Data response.
    $data = [
      'section' => 'clear_caches',
      'notice' => __('CiviCRM caches cleared.', 'civicrm'),
      'saved' => TRUE,
    ];

    // Return the data.
    wp_send_json($data);

  }

}
