<?php
if (!defined('ABSPATH')) exit;

class Newsletter_PDF_Logger {
    private $log_directory;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_directory = $upload_dir['basedir'] . '/newsletter-logs';
        
        if (!file_exists($this->log_directory)) {
            wp_mkdir_p($this->log_directory);
        }
    }

    public function log($message, $level = 'info') {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        $timestamp = current_time('mysql');
        $log_entry = sprintf("[%s] [%s]: %s\n", $timestamp, strtoupper($level), $message);
        
        $log_file = $this->log_directory . '/pdf-' . date('Y-m-d') . '.log';
        error_log($log_entry, 3, $log_file);
    }

    public function error($message) {
        $this->log($message, 'error');
    }

    public function info($message) {
        $this->log($message, 'info');
    }

    public function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log($message, 'debug');
        }
    }

    public function get_logs($days = 1) {
        $logs = array();
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $log_file = $this->log_directory . '/pdf-' . $date . '.log';
            
            if (file_exists($log_file)) {
                $logs[$date] = file_get_contents($log_file);
            }
        }
        
        return $logs;
    }

    public function clear_logs() {
        $files = glob($this->log_directory . '/pdf-*.log');
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}