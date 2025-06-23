<?php

namespace DogByteMarketing\ProjectPlot;

class Dependency_Loader {

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
    $this->load_frontend();
    $this->load_backend();
    $this->load_translations();
  }
  
  /**
   * Load Frontend
   *
   * @return void
   */
  public function load_frontend() {
    $frontend_loader = new Frontend_Loader($this->plugin, $this->version);
    $frontend_loader->init();
  }
  
  /**
   * Load Backend
   *
   * @return void
   */
  public function load_backend() {
    $backend_loader = new Backend_Loader($this->plugin, $this->version);
    $backend_loader->init();
  }
  
  /**
   * Load Translations
   *
   * @return void
   */
  public function load_translations() {
    $backend_loader = new Translations_Loader($this->plugin, $this->version);
    $backend_loader->init();
  }

}