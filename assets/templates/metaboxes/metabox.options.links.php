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

?><!-- assets/templates/metaboxes/metabox.options.links.php -->
<?php

/**
 * Before Links section.
 *
 * @since 5.34
 */
do_action('civicrm/metabox/links/pre');

?>
<p><?php _e('Below is a list of shortcuts to some CiviCRM admin pages that are important when you are setting up CiviCRM. When these settings are correctly configured, your CiviCRM installation should be ready for you to customise to your requirements.', 'civicrm'); ?></p>

<ul>
  <li>
    <a href="<?php echo $urls; ?>"><?php _e('Settings - Resource URLs', 'civicrm'); ?></a>
  </li>
  <li>
    <a href="<?php echo $uploads; ?>"><?php _e('Settings - Upload Directories', 'civicrm'); ?></a>
  </li>
  <li>
    <a href="<?php echo $permissions; ?>"><?php _e('WordPress Access Control', 'civicrm'); ?></a><br>
  </li>
  <li>
    <a href="<?php echo $extensions; ?>"><?php _e('CiviCRM Extensions', 'civicrm'); ?></a>
  </li>
  <?php

  /**
   * After "Useful Admin Links" list.
   *
   * @since 5.34
   */
  do_action('civicrm/metabox/links/admin/post');

  ?>
</ul>

<hr>

<p><?php _e('Shortcuts to some CiviCRM maintenance tasks.', 'civicrm'); ?></p>

<ul>
  <li>
    <a href="<?php echo $menu; ?>"><?php _e('Rebuild the CiviCRM menu', 'civicrm'); ?></a>
  </li>
  <li>
    <a href="<?php echo $triggers; ?>"><?php _e('Rebuild the CiviCRM database triggers', 'civicrm'); ?></a>
  </li>
  <li>
    <a href="<?php echo $upgrade; ?>"><?php _e('Upgrade CiviCRM', 'civicrm'); ?></a><br>
    <p class="description"><?php _e('Please note: you need to update the CiviCRM plugin directory first.', 'civicrm'); ?></p>
  </li>
  <?php

  /**
   * After "Maintenance Links" list.
   *
   * @since 5.34
   */
  do_action('civicrm/metabox/links/maintenance/post');

  ?>
</ul>

<?php

/**
 * After Links section.
 *
 * @since 5.34
 */
do_action('civicrm/metabox/links/post');
