<?php

namespace DogByteMarketing\ProjectPlot\Backend;

use DogByteMarketing\ProjectPlot\Utils;

class Widget {

  private $priorities;
  private $statuses;
  
  /**
   * __construct
   *
   * @return void
   */
  public function __construct() {
    $this->priorities = Utils::get_priorities();
    $this->statuses   = Utils::get_statuses();
  }
  
  /**
   * Init
   *
   * @return void
   */
  public function init() {
    add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
  }
  
  /**
   * Add task dashboard widget
   *
   * @return void
   */
  public function add_dashboard_widget() {
    wp_add_dashboard_widget(
      'projectplot_tasks_widget',        // Widget slug
      __('Recent Tasks', 'projectplot'), // Title
      array($this, 'render_dashboard_widget'),  // Display callback
      null,
      null,
      'column3'
    );
  }
  
  /**
   * Render task dashboard widget
   *
   * @return void
   */
  public function render_dashboard_widget() {
    $all_tasks = get_posts(array(
      'post_type'      => 'task',
      'posts_per_page' => -1, // Get all tasks to sort manually
      'post_status'    => 'publish',
      'meta_query'     => array(
        array(
          'key'     => '_task_status',
          'value'   => 'completed',
          'compare' => '!=',
        ),
      ),
    ));
  
    if (empty($all_tasks)) {
      echo '<p>' . esc_html__('No tasks found.', 'projectplot') . '</p>';
      return;
    }
  
    // Build array with priorities
    $priority_order = array_keys($this->priorities); // Assumes order in Utils defines priority (highest to lowest)
    $tasks_with_priority = [];
  
    foreach ($all_tasks as $task) {
      $priority_key = get_post_meta($task->ID, '_task_priority', true);
      $priority_index = array_search($priority_key, $priority_order);
  
      if ($priority_index === false) {
        $priority_index = count($priority_order); // lowest if not found
      }
  
      $tasks_with_priority[] = [
        'task'    => $task,
        'priority_index' => $priority_index,
      ];
    }
  
    // Sort by priority index
    usort($tasks_with_priority, function ($a, $b) {
      return $b['priority_index'] - $a['priority_index'];
    });
  
    // Take top 5
    $tasks_with_priority = array_slice($tasks_with_priority, 0, 5);
  
    // Output
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>
            <th>' . esc_html__('Task', 'projectplot') . '</th>
            <th>' . esc_html__('Status', 'projectplot') . '</th>
            <th>' . esc_html__('Priority', 'projectplot') . '</th>
          </tr></thead><tbody>';
  
    foreach ($tasks_with_priority as $item) {
      $task = $item['task'];
      $status_key   = get_post_meta($task->ID, '_task_status', true);
      $priority_key = get_post_meta($task->ID, '_task_priority', true);
      $status       = isset($this->statuses[$status_key]) ? $this->statuses[$status_key] : __('N/A', 'projectplot');
      $priority     = isset($this->priorities[$priority_key]) ? $this->priorities[$priority_key] : __('N/A', 'projectplot');
  
      echo '<tr>';
      echo '<td><a href="' . esc_url(get_edit_post_link($task->ID)) . '">' . esc_html(get_the_title($task)) . '</a></td>';
      echo '<td>' . esc_html($status) . '</td>';
      echo '<td>' . esc_html($priority) . '</td>';
      echo '</tr>';
    }
  
    echo '</tbody></table>';
    $create_url = admin_url('post-new.php?post_type=task');
    $view_url   = admin_url('edit.php?post_type=task');

    echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">';
    echo '<a href="' . esc_url($create_url) . '" class="button button-primary">' . esc_html__('Create New Task', 'projectplot') . '</a>';
    echo '<a href="' . esc_url($view_url) . '" class="button button-secondary">' . esc_html__('View All Tasks', 'projectplot') . '</a>';
    echo '</div>';
  }
  
  
}