<?php

namespace DogByteMarketing\ProjectPlot\Backend;

use DogByteMarketing\ProjectPlot\Log;
use DogByteMarketing\ProjectPlot\Utils;

class API {

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
	 * The token
	 *
	 * @var string $token The token.
	 */
	private $token;
  
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
    add_action('rest_api_init', array($this, 'register_routes'));
  }

  public function register_routes() {
    register_rest_route('projectplot/v1', '/clickup/webhook/', array(
      'methods'             => 'POST',
      'callback'            => array($this, 'get_payload'),
      'permission_callback' => array($this, 'validate_token'),
    ));
  }
  
  /**
   * Validate token
   *
   * @param  mixed $request
   * @return void
   */
  public function validate_token(\WP_REST_Request $request) {
    // Get the webhook secret token stored in the DB
    $webhook_token = get_option('projectplot_webhook_token_dogbytemarketing');

    if (!$webhook_token) {
      Log::debug("No webhook secret found.");
      Utils::send_error('No webhook secret found.', 403);

      return false;
    }

    // Get the signature from the header
    $signature_header = $request->get_header('x-signature');

    if (!$signature_header) {
      Log::debug("Missing signature header.");
      Utils::send_error('Missing signature header.', 403);

      return false;
    }

    // Generate HMAC-SHA256 hash of the raw body using the secret
    $raw_body             = $request->get_body();
    $calculated_signature = hash_hmac('sha256', $raw_body, $webhook_token);

    // Compare the signature
    if (!hash_equals($calculated_signature, $signature_header)) {
      Log::debug("Invalid signature.");
      Utils::send_error('Invalid signature.', 403);

      return false;
    }

    Log::debug("Webhook token verified.");

    return true;
  }
  
  /**
   * Get's the payload from ClickUp
   *
   * @param  mixed $request
   * @return void
   */
  public function get_payload(\WP_REST_Request $request) {
    $params = $request->get_json_params();

    Log::debug('Webhook Params');
    Log::debug($params);

    $event = isset($params['event']) ? sanitize_text_field($params['event']) : '';

    if (!$event) {
      Utils::send_error('Missing Event.', 403);
    }

    if ($event == 'taskCreated') {
      // It appears that task name is not passed from Clickup, so we can't integrate this
    }

    if ($event == 'taskUpdated') {
      $task_id = isset($params['task_id']) ? sanitize_text_field($params['task_id']) : '';

      if (!$task_id) {
        Utils::send_error('Missing Task ID.', 403);
      }

      $new_name = isset($params['history_items'][0]['field']) ? sanitize_text_field($params['history_items'][0]['field']) : '';

      if ($new_name != 'name') {
        return;
      }

      $new_name = isset($params['history_items'][0]['after']) ? sanitize_text_field($params['history_items'][0]['after']) : '';
      
      $this->update_task_name($task_id, $new_name);
    }

    if ($event == 'taskDeleted') {
      $task_id = isset($params['task_id']) ? sanitize_text_field($params['task_id']) : '';

      if (!$task_id) {
        Utils::send_error('Missing Task ID.', 403);
      }

      $this->delete_task($task_id);
    }

    if ($event == 'taskPriorityUpdated') {
      $task_id = isset($params['task_id']) ? sanitize_text_field($params['task_id']) : '';

      if (!$task_id) {
        Utils::send_error('Missing Task ID.', 403);
      }

      $new_priority = isset($params['history_items'][0]['after']['id']) ? sanitize_text_field($params['history_items'][0]['after']['id']) : '';

      if (!$new_priority) {
        Utils::send_error('Missing status.', 403);
      }

      $this->update_task_priority($task_id, $new_priority);
    }

    if ($event == 'taskStatusUpdated') {
      $task_id = isset($params['task_id']) ? sanitize_text_field($params['task_id']) : '';

      if (!$task_id) {
        Utils::send_error('Missing Task ID.', 403);
      }

      $new_status = isset($params['history_items'][0]['after']['status']) ? sanitize_text_field($params['history_items'][0]['after']['status']) : '';

      if (!$new_status) {
        Utils::send_error('Missing status.', 403);
      }

      $this->update_task_status($task_id, $new_status);
    }
  }
  
  /**
   * Delete task
   *
   * @param  string $task_id The task ID
   * @return void
   */
  private function delete_task($task_id) {
    Log::debug("Deleting task ID " . $task_id);

    if (!$task_id) {
      return;
    }

    $task_id    = sanitize_text_field($task_id);

    $args = array(
      'post_type'  => 'task_dbm',
      'numberposts' => 1,
      'meta_query' => array(
        array(
          'key'     => '_task_id',
          'value'   => $task_id,
          'compare' => '='
        )
      )
    );
    
    $posts = get_posts($args);

    if (!$posts) {
      Log::debug("No task found by that ID");
    }

    $post    = $posts[0];
    $post_id = $post->ID;

    Log::debug("Deleting task");

    wp_delete_post($post_id);
  }
  
  /**
   * Update task status
   *
   * @param  string $task_id    The Task ID
   * @param  string $new_status The new status
   * @return void
   */
  private function update_task_status($task_id, $new_status) {
    Log::debug("Updating task ID " . $task_id);

    if (!$task_id || !$new_status) {
      return;
    }

    $task_id    = sanitize_text_field($task_id);
    $new_status = str_replace(" ", "_", sanitize_text_field($new_status));

    $args = array(
      'post_type'  => 'task_dbm',
      'numberposts' => 1,
      'meta_query' => array(
        array(
          'key'     => '_task_id',
          'value'   => $task_id,
          'compare' => '='
        )
      )
    );
    
    $posts = get_posts($args);

    if (!$posts) {
      Log::debug("No task found by that ID");
    }

    $post    = $posts[0];
    $post_id = $post->ID;

    Log::debug("Updating task status to " . $new_status);

    update_post_meta($post_id, '_task_status', $new_status);
  }
  
  /**
   * Update task name
   *
   * @param  string $task_id  The task ID
   * @param  string $new_name The new name of the task
   * @return void
   */
  private function update_task_name($task_id, $new_name) {
    Log::debug("Updating task ID " . $task_id);

    if (!$task_id || !$new_name) {
      return;
    }

    $task_id  = sanitize_text_field($task_id);
    $new_name = sanitize_text_field($new_name);

    $args = array(
      'post_type'  => 'task_dbm',
      'numberposts' => 1,
      'meta_query' => array(
        array(
          'key'     => '_task_id',
          'value'   => $task_id,
          'compare' => '='
        )
      )
    );
    
    $posts = get_posts($args);

    if (!$posts) {
      Log::debug("No task found by that ID");
    }

    $post    = $posts[0];
    $post_id = $post->ID;

    Log::debug("Updating task name to " . $new_name);

    $updated_post = array(
      'ID'         => $post_id,
      'post_title' => $new_name,
    );
    
    wp_update_post($updated_post);
  }
  
  /**
   * Update task priority
   *
   * @param  string $task_id      The task ID
   * @param  int    $new_priority The new priority
   * @return void
   */
  private function update_task_priority($task_id, $new_priority) {
    Log::debug("Updating task ID " . $task_id);

    if (!$task_id || !$new_priority) {
      return;
    }

    $task_id      = sanitize_text_field($task_id);
    $new_priority = sanitize_text_field($new_priority);

    $args = array(
      'post_type'  => 'task_dbm',
      'numberposts' => 1,
      'meta_query' => array(
        array(
          'key'     => '_task_id',
          'value'   => $task_id,
          'compare' => '='
        )
      )
    );
    
    $posts = get_posts($args);

    if (!$posts) {
      Log::debug("No task found by that ID");
    }

    $post    = $posts[0];
    $post_id = $post->ID;

    Log::debug("Updating task priority to " . $new_priority);

    update_post_meta($post_id, '_task_priority', $new_priority);
  }
  
}