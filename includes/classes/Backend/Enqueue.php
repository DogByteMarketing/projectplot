<?php

namespace DogByteMarketing\ProjectPlot\Backend;

class Enqueue {

  /**
	 * Full path and filename of plugin.
	 *
	 * @var string $version Full path and filename of plugin.
	 */
  private $plugin;

	/**
	 * The version of this plugin.
	 *
	 * @var string $version The current version of this plugin.
	 */
	private $version;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct($plugin, $version) {
    $this->plugin  = $plugin;
    $this->version = $version;
  }
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    add_action('admin_enqueue_scripts', array($this, 'enqueue'));
  }
  
  /**
   * Enqueue scripts and styles
   *
   * @return void
   */
  public function enqueue($hook_suffix) {
    if ($hook_suffix === 'task_page_projectplot') {
      $this->enqueue_styles();
      $this->enqueue_scripts();
      $this->enqueue_wordpress();
    }
  }
  
  /**
   * Enqueue styles
   *
   * @return void
   */
  private function enqueue_styles() {
    wp_enqueue_style('projectplot-settings', plugins_url('/css/backend/settings.css', $this->plugin), array(), $this->version);
    wp_enqueue_style('bootstrap', plugins_url('/css/backend/bootstrap-wrapper.min.css', $this->plugin), array(), $this->version);
    wp_enqueue_style('bootstrap-icons', plugins_url('/css/bootstrap-icons.min.css', $this->plugin), array(), $this->version);
    wp_enqueue_style('sweetalert2', plugins_url('/css/backend/sweetalert2.min.css', $this->plugin), array(), $this->version);
  }
  
  /**
   * Enqueue scripts
   *
   * @return void
   */
  private function enqueue_scripts() {
    wp_enqueue_script('projectplot-settings', plugins_url('/js/backend/settings.js', $this->plugin), array('jquery', 'wp-color-picker', 'wp-i18n'), $this->version, true);
    wp_set_script_translations('projectplot-settings', 'projectplot', plugin_dir_path($this->plugin) . '/languages/');
    wp_enqueue_script('bootstrap', plugins_url('/js/backend/bootstrap.min.js', $this->plugin), array('jquery', 'wp-color-picker'), $this->version, true);
    wp_enqueue_script('sweetalert2', plugins_url('/js/backend/sweetalert2.all.min.js', $this->plugin), array('jquery'), $this->version, true);
  }
  
  /**
   * Enqueue WordPress related styles and scripts
   *
   * @return void
   */
  private function enqueue_wordpress() {
    wp_enqueue_media();
    wp_enqueue_editor();
    wp_enqueue_style('wp-color-picker');
  }

}