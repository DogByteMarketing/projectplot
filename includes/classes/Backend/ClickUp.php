<?php

namespace DogByteMarketing\ProjectPlot\Backend;

use DogByteMarketing\ProjectPlot\Log;
use DogByteMarketing\ProjectPlot\Utils;

class ClickUp {

  const API_URL = 'https://www.dogbytemarketing.com/wp-json/projectplot-api/v1';

  /**
   * Maybe create clickup task
   *
   * @param  array $task    The task array
   * @return mixed $request The request
   */
  public function maybe_create_clickup_task($task) {
    Log::debug("Maybe creating Clickup task");
    Log::debug("Task");
    Log::debug($task);

    $token         = Utils::get_option('clickup', 'token');
    $email         = Utils::get_option('clickup', 'email');
    $workspace_id  = Utils::get_option('clickup', 'workspace_id');
    $list_id       = Utils::get_option('clickup', 'list_id');

    // ClickUp not configured
    if (!$token || !$email || !$workspace_id || !$list_id) {
      Log::debug("ClickUp not configured.");

      return;
    }

    $title         = isset($task['title']) ? sanitize_text_field($task['title']) : '';
    $description   = isset($task['description']) ? sanitize_text_field($task['description']) : '';
    $priority      = isset($task['priority']) ? sanitize_text_field($task['priority']) : '';
    $status        = isset($task['status']) ? sanitize_text_field($task['status']) : '';

    $body = array(
      'email'        => $email,
      'workspace_id' => $workspace_id,
      'list_id'      => $list_id,
      'title'        => $title,
      'description'  => $description,
      'priority'     => $priority,
      'status'       => $status,
    );

    $request = wp_remote_post(self::API_URL . '/clickup/task/', array(
      'method'    => 'POST',
      'headers'   => array(
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
      ),
      'body'      => json_encode($body),
    ));

    Log::debug("Request");
    Log::debug($request);

    if (is_wp_error($request)) {
      Log::error('Task creation failed: ' . $request->get_error_message());
      
      return;
    }

    return $request;
  }
  
  /**
   * Maybe update clickup task
   *
   * @param  array $task    The task array
   * @return mixed $request The request
   */
  public function maybe_update_clickup_task($task) {
    Log::debug("Maybe updating Clickup task");
    Log::debug("Task");
    Log::debug($task);

    $token         = Utils::get_option('clickup', 'token');
    $email         = Utils::get_option('clickup', 'email');
    $workspace_id  = Utils::get_option('clickup', 'workspace_id');
    $list_id       = Utils::get_option('clickup', 'list_id');

    // ClickUp not configured
    if (!$token || !$email || !$workspace_id || !$list_id) {
      Log::debug("ClickUp not configured.");

      return;
    }

    $task_id = isset($task['task_id']) ? sanitize_text_field($task['task_id']) : '';

    if (!$task_id) {
      Log::debug("Missing Task ID.");

      return;
    }

    $body = array(
      'email'        => $email,
      'workspace_id' => $workspace_id,
      'list_id'      => $list_id,
      'task_id'      => $task_id,
    );

    if (isset($task['title'])) {
      $body['title'] = sanitize_text_field($task['title']);
    }

    if (isset($task['description'])) {
      $body['description'] = sanitize_text_field($task['description']);
    }

    if (isset($task['priority'])) {
      $body['priority'] = sanitize_text_field($task['priority']);
    }

    if (isset($task['status'])) {
      $body['status'] = sanitize_text_field($task['status']);
    }

    $request = wp_remote_request(self::API_URL . '/clickup/task/', array(
      'method'    => 'PUT',
      'headers'   => array(
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
      ),
      'body'      => json_encode($body),
    ));

    Log::debug("Request");
    Log::debug($request);

    if (is_wp_error($request)) {
      Log::error('Task update failed: ' . $request->get_error_message());
      
      return;
    }

    return $request;
  }

  /**
   * Maybe delete clickup task
   *
   * @param  array $task    The task array
   * @return mixed $request The request
   */
  public function maybe_delete_clickup_task($task) {
    Log::debug("Maybe deleting Clickup task");
    Log::debug("Task");
    Log::debug($task);
    
    $token         = Utils::get_option('clickup', 'token');
    $email         = Utils::get_option('clickup', 'email');
    $workspace_id  = Utils::get_option('clickup', 'workspace_id');
    $list_id       = Utils::get_option('clickup', 'list_id');

    // ClickUp not configured
    if (!$token || !$email || !$workspace_id || !$list_id) {
      Log::debug("ClickUp not configured.");

      return;
    }

    $task_id       = isset($task['task_id']) ? sanitize_text_field($task['task_id']) : '';
    $title         = isset($task['title']) ? sanitize_text_field($task['title']) : '';
    $description   = isset($task['description']) ? sanitize_text_field($task['description']) : '';
    $priority      = isset($task['priority']) ? sanitize_text_field($task['priority']) : '';
    $status        = isset($task['status']) ? sanitize_text_field($task['status']) : '';

    $body = array(
      'email'        => $email,
      'workspace_id' => $workspace_id,
      'list_id'      => $list_id,
      'task_id'      => $task_id,
      'title'        => $title,
      'description'  => $description,
      'priority'     => $priority,
      'status'       => $status,
    );

    $request = wp_remote_post(self::API_URL . '/clickup/task/', array(
      'method'    => 'DELETE',
      'headers'   => array(
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
      ),
      'body'      => json_encode($body),
    ));

    Log::debug("Request");
    Log::debug($request);

    if (is_wp_error($request)) {
      Log::error('Task deletion failed: ' . $request->get_error_message());
      
      return;
    }

    return $request;
  }

  /**
   * Create ClickUp webhook
   *
   * @param  array $task    The task array
   * @return mixed $request The request
   */
  public function create_clickup_webhook() {
    Log::debug("Creating ClickUp Webhook");
    
    $token         = Utils::get_option('clickup', 'token');
    $email         = Utils::get_option('clickup', 'email');
    $workspace_id  = Utils::get_option('clickup', 'workspace_id');
    $list_id       = Utils::get_option('clickup', 'list_id');

    // ClickUp not configured
    if (!$token || !$email || !$workspace_id || !$list_id) {
      Log::debug("ClickUp not configured.");

      return;
    }

    $webhook_token = strtoupper(bin2hex(random_bytes(16)));

    $body = array(
      'email'         => $email,
      'workspace_id'  => $workspace_id,
      'list_id'       => $list_id,
      'endpoint'      => home_url() . '/wp-json/projectplot/v1/clickup/webhook/?token=' . $webhook_token,
    );

    $request = wp_remote_post(self::API_URL . '/clickup/webhook/', array(
      'method'    => 'POST',
      'headers'   => array(
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
      ),
      'body'      => json_encode($body),
    ));

    Log::debug("Request");
    Log::debug($request);

    if (is_wp_error($request)) {
      Log::error('Webhook creation failed: ' . $request->get_error_message());
      
      return;
    }

    $response = isset($request['body']) ? json_decode($request['body'], true) : '';

    if (!$response) {
      Log::error('Webhook creation failed no body: ' . $request->get_error_message());

      return;
    }

    Log::debug('Response');
    Log::debug($response);
    
    $response_code = wp_remote_retrieve_response_code($request);

    if ($response_code == 200 || $response_code == 201) {
      return $webhook_token;
    }
  }
  
}