<?php

namespace DogByteMarketing\ProjectPlot\Backend;

use DogByteMarketing\ProjectPlot\Log;
use DogByteMarketing\ProjectPlot\Utils;

class Admin {
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'settings_init'));
    add_action('admin_init', array($this, 'maybe_setup_clickup_webhook'));
  }

  /**
	 * Add admin menu to backend
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_submenu_page('edit.php?post_type=task', __('User Task Settings', 'projectplot'), __('Settings', 'projectplot'), 'manage_options', 'projectplot', array($this, 'options_page'));
	}
  
	/**
	 * Initialize Settings
	 *
	 * @return void
	 */
	public function settings_init() {
    $is_first_run = get_option('projectplot_settings_dogbytemarketing');

    // Check if first run and set empty options so the first save isn't empty
    if (!$is_first_run) {
      update_option('projectplot_settings_dogbytemarketing', array());
    }

    // Register the setting page
    register_setting(
      'projectplot_dogbytemarketing',          // Option group
      'projectplot_settings_dogbytemarketing', // Option name
      array($this, 'sanitize')
    );

    // Add a section to assign an ID for admin.js to target switching between tabs
    add_settings_section(
      'projectplot_general_section_dogbytemarketing',
      '',
      null,
      'projectplot_general_dogbytemarketing'
    );

    // Add a setting under general section
		add_settings_field(
			'enabled',                                            // Setting Id
			__('Enabled?', 'projectplot'),                 // Setting Label
			array($this, 'enabled_render'),                       // Setting callback
			'projectplot_general_dogbytemarketing',        // Setting page
			'projectplot_general_section_dogbytemarketing' // Setting section
		);

    // Add a setting under general section
		add_settings_field(
			'debug',                                            // Setting Id
			__('Debug Mode', 'projectplot'),                 // Setting Label
			array($this, 'debug_render'),                       // Setting callback
			'projectplot_general_dogbytemarketing',        // Setting page
			'projectplot_general_section_dogbytemarketing' // Setting section
		);

    // Add a section to assign an ID for admin.js to target switching between tabs
    add_settings_section(
      'projectplot_clickup_section_dogbytemarketing',
      '',
      null,
      'projectplot_clickup_dogbytemarketing'
    );

    // Add a setting under clickup section
		add_settings_field(
			'email',                                            // Setting Id
			__('Email', 'projectplot'),                 // Setting Label
			array($this, 'email_render'),                       // Setting callback
			'projectplot_clickup_dogbytemarketing',        // Setting page
			'projectplot_clickup_section_dogbytemarketing' // Setting section
		);

    // Add a setting under clickup section
		add_settings_field(
			'token',                                            // Setting Id
			__('Token', 'projectplot'),                 // Setting Label
			array($this, 'token_render'),                       // Setting callback
			'projectplot_clickup_dogbytemarketing',        // Setting page
			'projectplot_clickup_section_dogbytemarketing' // Setting section
		);

    // Add a setting under clickup section
		add_settings_field(
			'workspace_id',                                            // Setting Id
			__('Workspace ID', 'projectplot'),                 // Setting Label
			array($this, 'workspace_id_render'),                       // Setting callback
			'projectplot_clickup_dogbytemarketing',        // Setting page
			'projectplot_clickup_section_dogbytemarketing' // Setting section
		);

    // Add a setting under clickup section
		add_settings_field(
			'list_id',                                            // Setting Id
			__('List ID', 'projectplot'),                 // Setting Label
			array($this, 'list_id_render'),                       // Setting callback
			'projectplot_clickup_dogbytemarketing',        // Setting page
			'projectplot_clickup_section_dogbytemarketing' // Setting section
		);
	}

  /**
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function enabled_render() {
    $option = Utils::get_option('general', 'enabled');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="projectplot_settings_dogbytemarketing[general][enabled]" class="form-check-input" id="enabled" role="switch" <?php checked(1, $option, true); ?> /> <?php echo esc_html__('Yes', 'projectplot'); ?>
    </div>
		<?php
	}

  /**
	 * Render Enabled Field
	 *
	 * @return void
	 */
	public function debug_render() {
    $option = Utils::get_option('general', 'debug');
    ?>
    <div class="form-check form-switch">
      <input type="checkbox" name="projectplot_settings_dogbytemarketing[general][debug]" class="form-check-input" id="debug" role="switch" <?php checked(1, $option, true); ?> /> <?php echo esc_html__('Yes', 'projectplot'); ?>
    </div>
    <p><?php esc_html_e("Logs can be found under", 'projectplot'); ?> /wp-content/upload/logs/</p>
		<?php
	}

  /**
	 * Render Email Field
	 *
	 * @return void
	 */
	public function email_render() {
    $option = Utils::get_option('clickup', 'email');
    ?>
    <input type="email" name="projectplot_settings_dogbytemarketing[clickup][email]" id="email" value="<?php echo esc_html($option); ?>" />
    <p><?php esc_html_e("Enter the email address associated with your Dog Byte Marketing account.", 'projectplot'); ?></p>
		<?php
	}

  /**
	 * Render Token Field
	 *
	 * @return void
	 */
	public function token_render() {
    $option = Utils::get_option('clickup', 'token');
    ?>
    <input type="password" name="projectplot_settings_dogbytemarketing[clickup][token]" id="token" value="<?php echo esc_html($option); ?>" />
    <p><?php esc_html_e("Enter the Token found in your ", 'projectplot'); ?><a href="https://www.dogbytemarketing.com/my-account/projectplot/" target="_blank"><?php esc_html_e("ProjectPlot", 'projectplot'); ?></a> <?php esc_html_e("dashboard.", 'projectplot'); ?></p>
		<?php
	}

  /**
	 * Render Workspace ID Field
	 *
	 * @return void
	 */
	public function workspace_id_render() {
    $option = Utils::get_option('clickup', 'workspace_id');
    ?>
    <input type="text" name="projectplot_settings_dogbytemarketing[clickup][workspace_id]" id="workspace_id" value="<?php echo esc_html($option); ?>" />
    <p><?php esc_html_e("Enter the workspace id of the workspace you want to feed tasks into, this will be used to create a Webhook to sync task updates. To find the Workspace ID, right-click a list in your Sidebar, select Copy link, and paste the link in your address bar. The first set of numbers in the URL is your Workspace ID.", 'projectplot'); ?></p>
		<?php
	}

  /**
	 * Render List ID Field
	 *
	 * @return void
	 */
	public function list_id_render() {
    $option = Utils::get_option('clickup', 'list_id');
    ?>
    <input type="text" name="projectplot_settings_dogbytemarketing[clickup][list_id]" id="list_id" value="<?php echo esc_html($option); ?>" />
    <p><?php esc_html_e("Enter the list id of the list you want to feed tasks into. To find the List ID, right-click the list in your Sidebar, select Copy link, and paste the link in your address bar. The last set of numbers in the URL is your List ID.", 'projectplot'); ?></p>
		<?php
	}

	/**
	 * Render options page
	 *
	 * @return void
	 */
	public function options_page() {
    ?>
    <form action='options.php' method='post'>
      <div class="bootstrap-wrapper">
        <div class="container">
          <div class="row">
            <div class="col-3"></div>
            <div class="col-6">
              <h1><?php echo esc_html__('ProjectPlot Settings', 'projectplot'); ?></h1>
            </div>
            <div class="col-3"></div>
          </div>
          <div class="row">
            <div class="nav-links col-12 col-md-6 col-xl-3">
              <ul>
                <li>
                  <a href="javascript:void(0);" class="active" data-section="general">
                    <i class="bi bi-gear-fill"></i>
                    <?php echo esc_html__('General', 'projectplot'); ?>
                  </a>
                </li>
                <li>
                  <a href="javascript:void(0);" data-section="clickup">
                    <i class="bi bi-list-task"></i>
                    <?php echo esc_html__('ClickUp', 'projectplot'); ?>
                  </a>
                </li>
              </ul>
            </div>
            <div class="tabs col-12 col-md-6 col-xl-6">
              <div class="tab general">
                <?php
                do_settings_sections('projectplot_general_dogbytemarketing');
                ?>
              </div>
              <div class="tab clickup" style="display: none;">
              <div class="subscription-notice"><?php echo esc_html__('These options require a ', 'projectplot') . '<a href="https://www.dogbytemarketing.com/shop/projectplot-suite/" target="_blank">' . esc_html__('ProjectPlot Suite', 'projectplot') . '</a> ' . esc_html__('subscription.', 'projectplot'); ?></div>
                <?php
                do_settings_sections('projectplot_clickup_dogbytemarketing');
                ?>
              </div>
            
              <?php
              settings_fields('projectplot_dogbytemarketing');
              submit_button();
              ?>
              
            </div>

            <div class="ctas col-12 col-md-12 col-xl-3">
              <div class="cta">
                <h2 style="color: #fff;">Something Not Working?</h2>
                <p>We pride ourselves on quality, so if something isn't working or you have a suggestion, feel free to call or email us. We're based out of Tennessee in the USA.
                <p><a href="tel:+14237248922" class="button button-primary" style="text-decoration: none; color: #fff; font-weight: 700; text-transform: uppercase; background-color: #333; border-color: #333;" target="_blank">Call Us</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://www.dogbytemarketing.com/contact/" class="button button-primary" style="text-decoration: none; color: #fff; font-weight: 700; text-transform: uppercase; background-color: #333; border-color: #333;" target="_blank">Email Us</a></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  <?php
  }

  /**
   * Sanitize Options
   *
   * @param  array $input Array of option inputs
   * @return array $sanitary_values Array of sanitized options
   */
  public function sanitize($input) {
		$sanitary_values = array();

    if (isset($input['general']['enabled']) && $input['general']['enabled']) {
      $sanitary_values['general']['enabled'] = $input['general']['enabled'] === 'on' ? true : false;
    } else {
      $sanitary_values['general']['enabled'] = false;
    }

    if (isset($input['general']['debug']) && $input['general']['debug']) {
      $sanitary_values['general']['debug'] = $input['general']['debug'] === 'on' ? true : false;
    } else {
      $sanitary_values['general']['debug'] = false;
    }

    if (isset($input['clickup']['email']) && $input['clickup']['email']) {
			$sanitary_values['clickup']['email'] = sanitize_text_field($input['clickup']['email']);
		}

    if (isset($input['clickup']['token']) && $input['clickup']['token']) {
			$sanitary_values['clickup']['token'] = sanitize_text_field($input['clickup']['token']);
		}

    if (isset($input['clickup']['workspace_id']) && $input['clickup']['workspace_id']) {
			$sanitary_values['clickup']['workspace_id'] = sanitize_text_field($input['clickup']['workspace_id']);
		}

    if (isset($input['clickup']['list_id']) && $input['clickup']['list_id']) {
			$sanitary_values['clickup']['list_id'] = sanitize_text_field($input['clickup']['list_id']);
		}

    return $sanitary_values;
  }

  /**
   * Maybe setup ClickUp webhook
   *
   * @return void
   */
  public function maybe_setup_clickup_webhook() {
    $workspace_id = Utils::get_option('clickup', 'workspace_id');

    if ($workspace_id) {
      Log::debug("Workspace ID Found");

      $webhook_token = get_option('projectplot_webhook_token_dogbytemarketing');

      if (!$webhook_token) {
        Log::debug("No Webhook Token Found");
        
        $clickup       = new ClickUp();
        $webhook_token = $clickup->create_clickup_webhook();

        if ($webhook_token) {
          Log::debug("Adding webhook token");

          update_option('projectplot_webhook_token_dogbytemarketing', $webhook_token);
        }
      }
    }
  }

}