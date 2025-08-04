<?php

namespace DogByteMarketing\ProjectPlot;

use DogByteMarketing\ProjectPlot\Frontend\Enqueue;
use DogByteMarketing\ProjectPlot\Frontend\UI;
use DogByteMarketing\ProjectPlot\Frontend\WooCommerce;
use DogByteMarketing\ProjectPlot\Frontend\Shortcodes;

class Frontend_Loader {

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
    $enabled = Utils::get_option('general', 'enabled');

    if ($enabled) {
      $this->load_enqueue();
    }
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

}