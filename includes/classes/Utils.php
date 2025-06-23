<?php

namespace DogByteMarketing\ProjectPlot;

class Utils {

  /**
   * Get projectplot options
   *
   * @return array $options The project management options
   */
  public static function get_options() {
    $options = get_option('projectplot_settings_dogbytemarketing');

    return $options;
  }
  
  /**
   * Get projectplot option from options array
   *
   * @param  string $section The section to retrieve from the options
   * @param  string $option  The option to retrieve from the section
   * @return mixed  $option  The retrieved option value
   */
  public static function get_option($section, $option) {
    $options = self::get_options();
    $option  = isset($options[$section][$option]) ? $options[$section][$option] : '';

    return $option;
  }
  
  /**
   * Update an option
   *
   * @param  string $option The option name
   * @param  mixed  $value  The option value
   * @return void
   */
  public static function update_option($option, $value) {
    $options          = self::get_options();
    $options[$option] = $value;

    update_option('projectplot_settings_dogbytemarketing', $options);
  }
  
  /**
   * Send success json
   *
   * @param  string $message The message to send
   * @param  int    $code    The status code
   * @return void
   */
  public static function send_success($message, $code = 200) {
    $message = $message ? sanitize_text_field($message) : __('Success', 'projectplot');
    $code    = is_numeric($code) ? (int) $code : 200;

    wp_send_json_success(array(
      'message' => sanitize_text_field($message),
      'status' => $code
    ), $code);
  }
  
  /**
   * Send error json
   *
   * @param  mixed $message
   * @param  mixed $code
   * @return void
   */
  public static function send_error($message, $code = 400) {
    $message = $message ? sanitize_text_field($message) : __('Error', 'projectplot');
    $code    = is_numeric($code) ? (int) $code : 400;

    wp_send_json_error(array(
      'message' => sanitize_text_field($message),
      'status' => $code
    ), $code);
  }

  /**
   * Convert Hex color to RGBA
   *
   * @param  mixed $hex
   * @param  mixed $alpha
   * @return void
   */
  public static function hex_to_rgba($hex, $alpha = null) {
    // Remove the '#' if present
    $hex = ltrim($hex, '#');
    
    // Get the red, green, and blue values
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // If alpha is provided, return rgba format, otherwise return rgb
    if ($alpha !== null) {
      if ($alpha > 1) {
        $alpha = $alpha / 100; // Convert percentage to decimal
      }

      return "rgba($r, $g, $b, $alpha)";
    } else {
      return "rgb($r, $g, $b)";
    }
  }

  /**
   * get_task_count
   *
   * @param  int $current_user_id
   * @return int $task_count The number of tasks a user has
   */
  public static function mark_projectplot_read($current_user_id) {
    $task_ids = array();

    $tasks = get_posts(array(
      'post_type'      => 'projectplot',
      'posts_per_page' => -1,
      'post_status'    => 'publish',
    ));

    foreach($tasks as $task) {
      $task_id = $task->ID;
      $user_id = get_post_meta($task_id, '_user_id', true);

      if (!$user_id || $user_id == $current_user_id) {
        $task_ids[] = $task_id;
      }
    }

    update_user_meta($current_user_id, '_read_tasks', $task_ids);
  }

  /**
   * get_task_count
   *
   * @param  int $current_user_id
   * @return int $task_count The number of tasks a user has
   */
  public static function get_projectplot_count($current_user_id) {
    $task_count     = 0;
    $get_read_tasks = get_user_meta($current_user_id, '_read_tasks', true);
    $read_tasks     = $get_read_tasks ? $get_read_tasks : array();

    $tasks = get_posts(array(
      'post_type'      => 'projectplot',
      'posts_per_page' => -1,
      'post_status'    => 'publish',
    ));

    foreach($tasks as $task) {
      $task_id = $task->ID;
      $user_id = get_post_meta($task_id, '_user_id', true);

      if (!$user_id || $user_id == $current_user_id) {
        if (!in_array($task_id, $read_tasks)) {
          $task_count++;
        }
      }
    }

    return (int) $task_count;
  }

  public static function get_priorities() {
    $priorities = array(
      4 => 'Low',
      3 => 'Normal',
      2 => 'High',
      1 => 'Urgent',
    );

    return $priorities;
  }

  public static function get_statuses() {
    $statuses = array(
      'to_do'       => 'To Do',
      'in_progress' => 'In Progress',
      'completed'   => 'Completed',
    );

    return $statuses;
  }

}