<?php

namespace DogByteMarketing\ProjectPlot;

use DogByteMarketing\ProjectPlot\Backend\API;
use DogByteMarketing\ProjectPlot\Backend\Admin;
use DogByteMarketing\ProjectPlot\Backend\Enqueue;
use DogByteMarketing\ProjectPlot\Backend\Custom_Post_Type;
use DogByteMarketing\ProjectPlot\Backend\Widget;

class Backend_Loader {

  /**
	 * Full path and filename of plugin.
	 *
	 * @var string $version Full path and filename of plugin.
	 */
  private $plugin;

	/**
	 * The version of this plugin.
	 *
	 * @var   string $version The current version of this plugin.
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
    $this->load_enqueue();
    $this->load_admin();
    $this->load_custom_post_type();
    $this->load_widget();
    $this->load_api();
  }
  
  /**
   * Load UI
   *
   * @return void
   */
  public function load_enqueue() {
    $gui = new Enqueue($this->plugin, $this->version);
    $gui->init();
  }
  
  /**
   * Load Admin
   *
   * @return void
   */
  public function load_admin() {
    $admin = new Admin();
    $admin->init();
  }
  
  /**
   * Load Custom Post Type
   *
   * @return void
   */
  public function load_custom_post_type() {
    $custom_post_type = new Custom_Post_Type();
    $custom_post_type->init();
  }
  
  /**
   * Load Custom Post Type
   *
   * @return void
   */
  public function load_widget() {
    $widget = new Widget();
    $widget->init();
  }
  
  /**
   * Load API
   *
   * @return void
   */
  public function load_api() {
    $api = new API($this->plugin, $this->version);
    $api->init();
  }

}