<?php

namespace DogByteMarketing\ProjectPlot\Backend;

if (!defined('ABSPATH')) exit;

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

    register_rest_route('projectplot/v1', '/tasks', array(
      'methods'             => 'GET',
      'callback'            => array($this, 'get_tasks'),
      'permission_callback' => array($this, 'validate_api_token'),
      'args'                => array(
        'status' => array(
          'required'          => false,
          'sanitize_callback' => 'sanitize_text_field',
        ),
      ),
    ));

    register_rest_route('projectplot/v1', '/tasks', array(
      'methods'             => 'POST',
      'callback'            => array($this, 'create_task'),
      'permission_callback' => array($this, 'validate_api_token'),
    ));

    register_rest_route('projectplot/v1', '/tasks/(?P<id>\d+)', array(
      'methods'             => 'GET',
      'callback'            => array($this, 'get_task'),
      'permission_callback' => array($this, 'validate_api_token'),
      'args'                => array(
        'id' => array(
          'required'          => true,
          'validate_callback' => function($param) { return is_numeric($param); },
        ),
      ),
    ));

    register_rest_route('projectplot/v1', '/tasks/(?P<id>\d+)', array(
      'methods'             => 'PATCH',
      'callback'            => array($this, 'update_task'),
      'permission_callback' => array($this, 'validate_api_token'),
      'args'                => array(
        'id' => array(
          'required'          => true,
          'validate_callback' => function($param) { return is_numeric($param); },
        ),
      ),
    ));
  }
  
  /**
   * Validate API token for other API clients (Bearer or X-ProjectPlot-Token header).
   *
   * @param  \WP_REST_Request $request
   * @return bool
   */
  public function validate_api_token(\WP_REST_Request $request) {
    $api_token = Utils::get_option('general', 'api_token');

    if (empty($api_token)) {
      return false;
    }

    $auth_header = $request->get_header('Authorization');
    if ($auth_header && preg_match('/Bearer\s+(.+)/', $auth_header, $m)) {
      return hash_equals($api_token, trim($m[1]));
    }

    $token_header = $request->get_header('X-ProjectPlot-Token');
    if ($token_header) {
      return hash_equals($api_token, $token_header);
    }

    return false;
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

  /**
   * Get tasks for other API clients
   *
   * @param  \WP_REST_Request $request
   * @return \WP_REST_Response
   */
  public function get_tasks(\WP_REST_Request $request) {
    $status_filter = $request->get_param('status');

    $args = array(
      'post_type'      => 'task_dbm',
      'post_status'    => 'publish',
      'posts_per_page' => -1,
      'orderby'        => 'meta_value_num',
      'meta_key'       => '_task_priority',
      'order'          => 'ASC',
    );

    if (!empty($status_filter)) {
      $args['meta_query'] = array(
        array(
          'key'     => '_task_status',
          'value'   => sanitize_text_field($status_filter),
          'compare' => '=',
        ),
      );
    }

    $query = new \WP_Query($args);
    $tasks = array();
    $statuses = Utils::get_statuses();
    $priorities = Utils::get_priorities();

    foreach ($query->posts as $post) {
      $task_status  = get_post_meta($post->ID, '_task_status', true);
      $task_priority = get_post_meta($post->ID, '_task_priority', true);
      $tasks[] = array(
        'id'         => (int) $post->ID,
        'title'      => $post->post_title,
        'content'    => $post->post_content,
        'status'     => $task_status,
        'status_label' => isset($statuses[$task_status]) ? $statuses[$task_status] : $task_status,
        'priority'   => $task_priority,
        'priority_label' => isset($priorities[(int) $task_priority]) ? $priorities[(int) $task_priority] : '',
        'task_id'    => get_post_meta($post->ID, '_task_id', true),
        'date'       => $post->post_modified,
      );
    }

    return new \WP_REST_Response(array('tasks' => $tasks), 200);
  }

  /**
   * Build a single task array from a post (task_dbm).
   *
   * @param  \WP_Post $post
   * @return array
   */
  private function task_from_post($post) {
    $statuses  = Utils::get_statuses();
    $priorities = Utils::get_priorities();
    $task_status  = get_post_meta($post->ID, '_task_status', true);
    $task_priority = get_post_meta($post->ID, '_task_priority', true);
    return array(
      'id'            => (int) $post->ID,
      'title'         => $post->post_title,
      'content'       => $post->post_content,
      'status'        => $task_status,
      'status_label'  => isset($statuses[$task_status]) ? $statuses[$task_status] : $task_status,
      'priority'      => $task_priority,
      'priority_label' => isset($priorities[(int) $task_priority]) ? $priorities[(int) $task_priority] : '',
      'task_id'       => get_post_meta($post->ID, '_task_id', true),
      'date'          => $post->post_modified,
    );
  }

  /**
   * Get a single task by ID
   *
   * @param  \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function get_task(\WP_REST_Request $request) {
    $id = (int) $request->get_param('id');
    $post = get_post($id);

    if (!$post || $post->post_type !== 'task_dbm' || $post->post_status !== 'publish') {
      return new \WP_Error('not_found', __('Task not found.', 'projectplot'), array('status' => 404));
    }

    return new \WP_REST_Response($this->task_from_post($post), 200);
  }

  /**
   * Update a task via API (title, content, status, priority).
   *
   * @param  \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function update_task(\WP_REST_Request $request) {
    $id = (int) $request->get_param('id');
    $post = get_post($id);

    if (!$post || $post->post_type !== 'task_dbm' || $post->post_status !== 'publish') {
      return new \WP_Error('not_found', __('Task not found.', 'projectplot'), array('status' => 404));
    }

    $params = $request->get_json_params();
    if (!is_array($params)) {
      $params = array();
    }

    if (isset($params['title'])) {
      wp_update_post(array(
        'ID'         => $id,
        'post_title' => sanitize_text_field($params['title']),
      ));
    }

    if (array_key_exists('content', $params)) {
      wp_update_post(array(
        'ID'           => $id,
        'post_content' => wp_kses_post($params['content']),
      ));
    }

    if (isset($params['status'])) {
      $status = sanitize_text_field($params['status']);
      if (in_array($status, array('to_do', 'in_progress', 'complete'), true)) {
        update_post_meta($id, '_task_status', $status);
      }
    }

    if (isset($params['priority'])) {
      $priority = sanitize_text_field($params['priority']);
      if (in_array($priority, array('1', '2', '3', '4'), true)) {
        update_post_meta($id, '_task_priority', $priority);
      }
    }

    $updated = get_post($id);
    return new \WP_REST_Response($this->task_from_post($updated), 200);
  }

  /**
   * Create a task via API (title required; content, status, priority optional).
   *
   * @param  \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public function create_task(\WP_REST_Request $request) {
    $params = $request->get_json_params();
    if (!is_array($params)) {
      $params = array();
    }

    $title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
    if (empty($title)) {
      return new \WP_Error('missing_title', __('Title is required.', 'projectplot'), array('status' => 400));
    }

    $content = isset($params['content']) ? wp_kses_post($params['content']) : '';
    $status  = isset($params['status']) ? sanitize_text_field($params['status']) : 'to_do';
    if (!in_array($status, array('to_do', 'in_progress', 'complete'), true)) {
      $status = 'to_do';
    }
    $priority = isset($params['priority']) ? sanitize_text_field($params['priority']) : '3';
    if (!in_array($priority, array('1', '2', '3', '4'), true)) {
      $priority = '3';
    }

    $post_id = wp_insert_post(array(
      'post_type'    => 'task_dbm',
      'post_status'  => 'publish',
      'post_title'   => $title,
      'post_content' => $content,
    ));

    if (is_wp_error($post_id)) {
      return $post_id;
    }

    update_post_meta($post_id, '_task_status', $status);
    update_post_meta($post_id, '_task_priority', $priority);

    $post = get_post($post_id);
    return new \WP_REST_Response($this->task_from_post($post), 201);
  }

}