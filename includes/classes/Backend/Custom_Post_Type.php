<?php

namespace DogByteMarketing\ProjectPlot\Backend;

use DogByteMarketing\ProjectPlot\Log;
use DogByteMarketing\ProjectPlot\Utils;

class Custom_Post_Type {

  private $priorities;
  private $statuses;

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
    add_action('init', array($this, 'create_task_post_type'));
    add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
    add_action('save_post_task', array($this, 'maybe_send_to_clickup'), 20, 2);
    add_filter('manage_task_posts_columns', array($this, 'add_task_columns'));
    add_action('manage_task_posts_custom_column', array($this, 'render_task_columns'), 10, 2);
    add_action('quick_edit_custom_box', array($this, 'quick_edit_custom_box'), 10, 2);
    add_action('restrict_manage_posts', array($this, 'add_priority_filter_dropdown'));
    add_filter('views_edit-task', array($this, 'add_status_subsubsub_links'));
    add_action('pre_get_posts', array($this, 'filter_tasks_by_priority'));
    add_action('pre_get_posts', array($this, 'exclude_complete_from_all_view'));
    add_action('pre_get_posts', array($this, 'orderby_priority'), 99);
    add_filter('manage_edit-task_sortable_columns', array($this, 'make_priority_sortable'));
    add_action('before_delete_post', array($this, 'maybe_remove_from_clickup'));
    add_action('trashed_post', array($this, 'maybe_remove_from_clickup'));
  }
  
  /**
   * Create the custom post type
   *
   * @return void
   */
  public function create_task_post_type() {
    $args = array(
      'labels' => array(
        'name'               => __('Tasks', 'projectplot'),
        'singular_name'      => __('Task', 'projectplot'),
        'add_new'            => __('Add New Task', 'projectplot'),
        'add_new_item'       => __('Add New Task', 'projectplot'),
        'edit_item'          => __('Edit Task', 'projectplot'),
        'new_item'           => __('New Task', 'projectplot'),
        'view_item'          => __('View Task', 'projectplot'),
        'search_items'       => __('Search Tasks', 'projectplot'),
        'not_found'          => __('No tasks found', 'projectplot'),
        'not_found_in_trash' => __('No tasks found in Trash', 'projectplot'),
        'all_items'          => __('All Tasks', 'projectplot'),
        'menu_name'          => __('Tasks', 'projectplot'),
        'name_admin_bar'     => __('Task', 'projectplot'),
      ),
      'public'            => false,
      'has_archive'       => true,
      'show_ui'           => true,
      'show_in_menu'      => true,
      'supports'          => array('title', 'editor', 'author', 'custom-fields'),
      'menu_position'     => 5,
      'menu_icon'         => 'dashicons-yes',
      'capability_type'   => 'post',
      'rewrite'           => array('slug' => 'tasks'),
    );

    // Register the post type
    register_post_type('task', $args);
	}
  
  /**
   * Add task meta boxes
   *
   * @return void
   */
  public function add_meta_boxes() {
    add_meta_box(
      'task_priority',
      __('Task Priority', 'projectplot'),
      array($this, 'render_priority_meta_box'),
      'task',
      'side',
      'default'
    );

    add_meta_box(
      'task_status',
      __('Task Status', 'projectplot'),
      array($this, 'render_status_meta_box'),
      'task',
      'side',
      'default'
    );
  }
  
  /**
   * Render priority meta data
   *
   * @param  mixed $post
   * @return void
   */
  public function render_priority_meta_box($post) {
    wp_nonce_field('add_task_priority', 'task_meta_priority_nonce');

    $value = get_post_meta($post->ID, '_task_priority', true);
    ?>
    <select name="task_priority">
      <option value="4" <?php selected($value, 4); ?>><?php esc_html_e('Low', 'projectplot'); ?></option>
      <option value="3" <?php selected($value, 3); ?>><?php esc_html_e('Normal', 'projectplot'); ?></option>
      <option value="2" <?php selected($value, 2); ?>><?php esc_html_e('High', 'projectplot'); ?></option>
      <option value="1" <?php selected($value, 1); ?>><?php esc_html_e('Urgent', 'projectplot'); ?></option>
    </select>
    <?php
  }
  
  /**
   * Render status meta data
   *
   * @param  mixed $post
   * @return void
   */
  public function render_status_meta_box($post) {
    wp_nonce_field('add_task_status', 'task_meta_status_nonce');

    $value = get_post_meta($post->ID, '_task_status', true);
    ?>
    <select name="task_status">
      <option value="to_do" <?php selected($value, 'to_do'); ?>>To Do</option>
      <option value="in_progress" <?php selected($value, 'in_progress'); ?>>In Progress</option>
      <option value="complete" <?php selected($value, 'complete'); ?>>Complete</option>
    </select>
    <?php
  }

  /**
   * Add task related columns
   *
   * @param  mixed $column
   * @param  mixed $post_id
   * @return void
   */
  public function add_task_columns($columns) {
    $columns['task_status']   = __('Status', 'projectplot');
    $columns['task_priority'] = __('Priority', 'projectplot');
    return $columns;
  }

  /**
   * Render task related columns
   *
   * @param  mixed $column
   * @param  mixed $post_id
   * @return void
   */
  public function render_task_columns($column, $post_id) {
    if ($column === 'task_status') {
      $get_status = get_post_meta($post_id, '_task_status', true);
      $status     = isset($this->statuses[$get_status]) ? $this->statuses[$get_status] : '';

      if ($status) {
        echo esc_html($status);
      } else {
        echo "No status set";
      }
    }

    if ($column === 'task_priority') {
      $get_priority = get_post_meta($post_id, '_task_priority', true);
      $priority     = isset($this->priorities[$get_priority]) ? $this->priorities[$get_priority] : '';

      if ($priority) {
        echo esc_html($priority);
      } else {
        echo "No priority set";
      }
    }
  }
  
  /**
   * Add task related data to quick edit
   *
   * @param  mixed $column_name
   * @param  mixed $post_type
   * @return void
   */
  public function quick_edit_custom_box($column_name, $post_type) {
    if ($post_type !== 'task') return;

    if (in_array($column_name, ['task_priority', 'task_status'])) {
      ?>
      <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
          <?php if ($column_name === 'task_priority') : ?>
            <label class="alignleft">
              <span class="title"><?php esc_html_e('Priority', 'projectplot'); ?></span>
              <select name="task_priority">
                <option value="4"><?php esc_html_e('Low', 'projectplot'); ?></option>
                <option value="3"><?php esc_html_e('Normal', 'projectplot'); ?></option>
                <option value="2"><?php esc_html_e('High', 'projectplot'); ?></option>
                <option value="1"><?php esc_html_e('Urgent', 'projectplot'); ?></option>
              </select>
            </label>
          <?php elseif ($column_name === 'task_status') : ?>
            <label class="alignleft">
              <span class="title"><?php esc_html_e('Status', 'projectplot'); ?></span>
              <select name="task_status">
                <option value="pending"><?php esc_html_e('Pending', 'projectplot'); ?></option>
                <option value="in_progress"><?php esc_html_e('In Progress', 'projectplot'); ?></option>
                <option value="complete"><?php esc_html_e('Complete', 'projectplot'); ?></option>
              </select>
            </label>
          <?php endif; ?>
        </div>
      </fieldset>
      <?php
    }
  }
  
  /**
   * Add priority filter dropdown
   *
   * @return void
   */
  public function add_priority_filter_dropdown() {
    global $typenow;
  
    if ($typenow !== 'task') {
      return;
    }
  
    $selected = isset($_GET['task_priority']) ? sanitize_text_field(wp_unslash($_GET['task_priority'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $priorities = $this->priorities;
  
    echo '<select name="task_priority">';
    echo '<option value="">' . esc_html__('All Priorities', 'projectplot') . '</option>';
    foreach ($priorities as $key => $label) {
      printf(
        '<option value="%s"%s>%s</option>',
        esc_attr($key),
        selected($selected, $key, false),
        esc_html($label)
      );
    }
    echo '</select>';
  }
    
  /**
   * Filter tasks by priority
   *
   * @param  mixed $query
   * @return void
   */
  public function filter_tasks_by_priority($query) {
    global $pagenow;

    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'task') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      // If a specific priority filter is selected
      if (isset($_GET['task_priority']) && $_GET['task_priority'] !== '') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $query->set('meta_query', array(
          array(
            'key'     => '_task_priority',
            'value'   => sanitize_text_field(wp_unslash($_GET['task_priority'])), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'compare' => '='
          )
        ));
      }

      // Handle sorting by task priority
      if (isset($_GET['orderby']) && $_GET['orderby'] === 'task_priority') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $query->set('meta_key', '_task_priority'); // Sorting by priority meta field
        $query->set('orderby', 'meta_value'); // Meta value sorting
        $query->set('order', (isset($_GET['order']) && $_GET['order'] === 'asc') ? 'ASC' : 'DESC'); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      }
    }
  }
   
  /**
   * Add statuses to filter options
   *
   * @param  mixed $views
   * @return void
   */
  public function add_status_subsubsub_links($views) {
    global $wpdb, $post_type, $wp_query;
  
    if ($post_type !== 'task') {
      return $views;
    }

    unset($views['publish']);
  
    $statuses = $this->statuses;
    $current_status = isset($_GET['task_status']) ? sanitize_text_field(wp_unslash($_GET['task_status'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
  
    // Count tasks by status
    $counts = array();

    foreach (array_keys($statuses) as $status_key) {
      $count = $wpdb->get_var($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        "SELECT COUNT(*) FROM $wpdb->postmeta pm
        INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_task_status'
        AND pm.meta_value = %s
        AND p.post_type = 'task'
        AND p.post_status = 'publish'",
        $status_key
      ));

      $counts[$status_key] = (int) $count;
    }
  
    // "All" link
    $class     = ($current_status === '' ? 'current' : '');
    $all_count = 0;

    foreach ($counts as $key => $count) {
      if ($key !== 'complete') {
        $all_count += $count;
      }
    }
    
    $views['all'] = sprintf(
      '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
      admin_url('edit.php?post_type=task&exclude_status=complete'),
      ($current_status === '' ? 'current' : ''),
      __('Open', 'projectplot'),
      $all_count
    );
  
    // Each Status Link
    foreach ($statuses as $status_key => $status_label) {
      $class = ($current_status === $status_key ? 'current' : '');
      $url = add_query_arg([
        'post_type' => 'task',
        'task_status' => $status_key
      ], admin_url('edit.php'));
  
      $views[$status_key] = sprintf(
        '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
        esc_url($url),
        $class,
        esc_html($status_label),
        isset($counts[$status_key]) ? $counts[$status_key] : 0
      );
    }
  
    return $views;
  }
    
  /**
   * Exclude complete task status from all view
   *
   * @param  mixed $query
   * @return void
   */
  public function exclude_complete_from_all_view($query) {
    global $pagenow;
  
    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'task') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      // If no specific task status is selected, exclude complete tasks from the general view
      if (!isset($_GET['task_status'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $meta_query = $query->get('meta_query') ?: [];

        $meta_query[] = array(
          'key'     => '_task_status',
          'value'   => 'complete',
          'compare' => '!='
        );

        $query->set('meta_query', $meta_query);
      }

      // If a task status is specifically selected, adjust the query accordingly
      if (isset($_GET['task_status']) && $_GET['task_status'] === 'complete') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = array(
          'key'     => '_task_status',
          'value'   => 'complete',
          'compare' => '='
        );

        $query->set('meta_query', $meta_query);
      }
      
      if (isset($_GET['task_status']) && $_GET['task_status'] === 'to_do') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = array(
          'key'     => '_task_status',
          'value'   => 'to_do',
          'compare' => '='
        );

        $query->set('meta_query', $meta_query);
      }
      
      if (isset($_GET['task_status']) && $_GET['task_status'] === 'in_progress') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = array(
          'key'     => '_task_status',
          'value'   => 'in_progress',
          'compare' => '='
        );

        $query->set('meta_query', $meta_query);
      }
    }
  }
  
  /**
   * Order by priority
   *
   * @param  mixed $query
   * @return void
   */
  public function orderby_priority($query) {
    global $pagenow;
    
    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'task') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      if ($query->get('orderby') == '') {
        $query->set('meta_key', '_task_priority'); // Sorting by priority meta field
        $query->set('orderby', 'task_priority'); // Meta value sorting
        $query->set('order', (isset($_GET['order']) && $_GET['order'] === 'asc') ? 'DESC' : 'ASC'); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
      }
      if ($query->get('order') == '') {
        $query->set('order', 'DESC');
      }
    }
  }
  
  /**
   * Make priority sortable
   *
   * @param  mixed $columns
   * @return void
   */
  public function make_priority_sortable($columns) {
    $columns['task_priority'] = 'task_priority'; // Make task_priority column sortable
    return $columns;
  }
  
  /**
   * Maybe send post to clickup
   *
   * @param  int    $post_id The post ID
   * @param  object $post    The post object
   * @return void
   */
  public function maybe_send_to_clickup($post_id, $post) {
    // Prevent auto-saves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    if (wp_is_post_revision($post_id)) {
      return;
    }
  
    // Only trigger on publish (skip drafts, pending, etc.)
    if ($post->post_status !== 'publish') {
      return;
    }

    if (!isset($_POST['task_meta_priority_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['task_meta_priority_nonce'])), 'add_task_priority')) {
      return;
    }

    if (!isset($_POST['task_meta_status_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['task_meta_status_nonce'])), 'add_task_status')) {
      return;
    }

    Log::debug("Saving Priority");

    if (array_key_exists('task_priority', $_POST)) {
      $task_priority = sanitize_text_field(wp_unslash($_POST['task_priority']));

      Log::debug("Setting task priority for {$post_id} to {$task_priority}");

      update_post_meta($post_id, '_task_priority', $task_priority);
    }

    Log::debug("Saving Status");

    if (array_key_exists('task_status', $_POST)) {
      $task_status = sanitize_text_field(wp_unslash($_POST['task_status']));
      
      Log::debug("Setting task status for {$post_id} to {$task_status}");

      update_post_meta($post_id, '_task_status', $task_status);
    }

    $task_id  = get_post_meta($post_id, '_task_id', true);
    $title    = isset($post->post_title) ? $post->post_title : '';
    $content  = $post->post_content ? $post->post_content : '';
    $priority = isset($_POST['task_priority']) ? sanitize_text_field(wp_unslash($_POST['task_priority'])) : '';
    $status   = isset($_POST['task_status']) ? sanitize_text_field(wp_unslash($_POST['task_status'])) : '';

    $task    = array(
      'task_id'     => $task_id ? $task_id : '',
      'title'       => $title,
      'description' => $content,
      'priority'    => $priority,
      'status'      => isset($this->statuses[$status]) ? $this->statuses[$status] : '',
    );

    $clickup     = new ClickUp();

    if (!$task_id) {
      $request = $clickup->maybe_create_clickup_task($task);
    } else {
      $request = $clickup->maybe_update_clickup_task($task);
    }

    if ($request) {
      $body = isset($request['body']) ? json_decode($request['body'], true) : '';
      $task = isset($body['data']['task']) ? $body['data']['task'] : '';

      if ($task) {
        update_post_meta($post_id, '_task_id', sanitize_text_field($task['id']));
      }
    }
  }
  
  /**
   * Maybe remove task from clickup
   *
   * @param  int  $post_id The post ID
   * @return void
   */
  public function maybe_remove_from_clickup($post_id) {
    if (get_post_type($post_id) !== 'task') {
      return;
    }

    $task_id  = get_post_meta($post_id, '_task_id', true);

    if ($task_id) {
      $task    = array(
        'task_id'     => $task_id,
      );

      $clickup = new ClickUp();

      $clickup->maybe_delete_clickup_task($task);
    }
  }
  
}