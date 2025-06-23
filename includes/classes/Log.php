<?php

namespace DogByteMarketing\ProjectPlot;

use DogByteMarketing\ProjectPlot\Utils;

class Log {

  /**
   * Write a debug log
   */
  public static function debug($log, $source = "") {
    self::write_log('DEBUG', $log, $source);
  }

  /**
   * Write an info log
   */
  public static function info($log, $source = "") {
    self::write_log('INFO', $log, $source);
  }

  /**
   * Write a warning log
   */
  public static function warning($log, $source = "") {
    self::write_log('WARNING', $log, $source);
  }

  /**
   * Write an error log
   */
  public static function error($log, $source = "") {
    self::write_log('ERROR', $log, $source);
  }

  /**
   * Write to the log file
   */
  private static function write_log($level, $log, $source = "") {
    if (!self::is_debugging_enabled()) {
      return;
    }

    if ($level) {
      $level = sanitize_text_field($level);
    }

    if ($source) {
      $source = sanitize_text_field($source);
    }

    $plugin_slug = basename(dirname(dirname(__DIR__)));
    $dir         = WP_CONTENT_DIR . '/uploads/logs/';
    
    if (!file_exists($dir)) {
      if (!self::create_log_dir($dir)) {
        return;
      }
    }

    $file      = $source ? $dir . $plugin_slug . '-' . $source . '.log' : $dir . $plugin_slug . '.log';
    $timestamp = gmdate("Y-m-d H:i:s");

    if (is_object($log) || is_array($log)) {
      $log       = print_r($log, true);
      $log_entry = "[{$timestamp}] [{$level}] {$log}" . PHP_EOL;
    } else {
      $log_entry = "[{$timestamp}] [{$level}] {$log}" . PHP_EOL;
    }

    file_put_contents($file, $log_entry, FILE_APPEND);
  }
  
  /**
   * Create log directory
   *
   * @param  string $dir The directory
   * @return void
   */
  private static function create_log_dir($dir) {
    $index    = $dir . '/index.php';
    $htaccess = $dir . '/.htaccess';

    // Make the directory, allow writing so we can add a file
    if (!wp_mkdir_p($dir)) {
      return;
    }

    // Shhh we don't need people looking at our logs
    $contents = '<?php' . PHP_EOL . '// Silence is golden';
    file_put_contents($index, $contents);

    // Block access
    $contents = 'Order Allow,Deny' . PHP_EOL . 'Deny from All';
    file_put_contents($htaccess, $contents);

    return true;
  }

  /**
   * Checks if debugging is enabled
   * 
   * @return bool $is_debugging_enabled Returns true if debugging is enable, false if not
   */
  private static function is_debugging_enabled() {
    $option = Utils::get_option('general', 'debug');

    $is_debugging_enabled = $option ? true : false;

    return $is_debugging_enabled;
  }

}
