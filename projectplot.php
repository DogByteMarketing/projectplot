<?php

/**
 * Plugin Name: ProjectPlot
 * Description: Bring task and team management to WordPress.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Dog Byte Marketing
 * Author URI: https://www.dogbytemarketing.com
 * Plugin URI: https://www.dogbytemarketing.com/contact/
 * Text Domain: projectplot
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace DogByteMarketing\ProjectPlot;

if (!defined('ABSPATH')) exit;

require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

class ProjectPlot
{

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
  public function __construct() {
    $this->plugin  = __FILE__;
    $this->version = $this->get_plugin_version();
  }
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    $this->load_dependencies();
  }
  
  /**
   * Load dependencies
   *
   * @return void
   */
  public function load_dependencies() {
    $dependency_loader = new Dependency_Loader($this->plugin, $this->version);
    $dependency_loader->init();
  }
  
  /**
   * Get the plugin version
   *
   * @return string $version The plugin version
   */
  private function get_plugin_version() {
    $plugin_data = get_file_data($this->plugin, array('Version' => 'Version'), false);
    $version     = $plugin_data['Version'];

    return $version;
  }

}

$projectplot = new ProjectPlot;
$projectplot->init();