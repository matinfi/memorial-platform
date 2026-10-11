<?php
/**
 * Privacy-conscious application diagnostics for آرامگاه مجازی.
 */
if (!defined('ABSPATH')) exit;

final class AVAM_Logger {
    const OPTION = 'avam_diagnostic_logs';
    const MAX_ENTRIES = 200;
    private static $previous_error_handler = null;

    public static function init() {
        add_action('shutdown', [__CLASS__, 'capture_fatal_error'], 1);
        add_action('wp_mail_failed', [__CLASS__, 'capture_mail_failure']);
        if (self::enabled()) {
            self::$previous_error_handler = set_error_handler([__CLASS__, 'capture_php_warning'], E_WARNING | E_USER_WARNING | E_RECOVERABLE_ERROR);
        }
    }

    private static function delegate_php_error($severity, $message, $file, $line) {
        if (is_callable(self::$previous_error_handler)) {
            return call_user_func(self::$previous_error_handler, $severity, $message, $file, $line);
        }
        return false;
    }

    public static function capture_php_warning($severity, $message, $file, $line) {
        if (!self::enabled() || !(error_reporting() & $severity) || !in_array($severity, [E_WARNING, E_USER_WARNING, E_RECOVERABLE_ERROR], true)) {
            return self::delegate_php_error($severity, $message, $file, $line);
        }

        $path = str_replace('\\', '/', (string) $file);
        $plugin_root = trailingslashit(str_replace('\\', '/', WP_PLUGIN_DIR)) . 'aramgah-virtual/';
        $theme_root = function_exists('get_theme_root') ? trailingslashit(str_replace('\\', '/', get_theme_root())) . 'aramgah-virtual-theme/' : '';
        $component = '';
        if (strpos($path, $plugin_root) === 0) $component = 'plugin';
        elseif ($theme_root !== '' && strpos($path, $theme_root) === 0) $component = 'theme';
        else return self::delegate_php_error($severity, $message, $file, $line);

        static $seen = [];
        $signature = hash('sha256', $severity . '|' . $path . '|' . (int) $line);
        if (!isset($seen[$signature])) {
            $seen[$signature] = true;
            self::log('warning', 'php_runtime_warning', [
                'file' => wp_basename($file),
                'line' => (int) $line,
                'code' => 'type_' . (int) $severity,
                'component' => $component,
            ]);
        }
        return self::delegate_php_error($severity, $message, $file, $line);
    }

    public static function enabled() {
        $settings = get_option('avam_control_center', []);
        return is_array($settings) && ($settings['diagnostics_enabled'] ?? '0') === '1';
    }

    public static function log($level, $event, $context = []) {
        if (!self::enabled()) return false;
        $allowed_levels = ['info', 'warning', 'error', 'critical'];
        $level = in_array($level, $allowed_levels, true) ? $level : 'info';
        $event = sanitize_key($event);
        if ($event === '') return false;

        $safe_context = [];
        $allowed_keys = ['code', 'post_id', 'attachment_id', 'http_status', 'component', 'file', 'line'];
        foreach ((array) $context as $key => $value) {
            $key = sanitize_key($key);
            if (!in_array($key, $allowed_keys, true) || !is_scalar($value)) continue;
            if (in_array($key, ['file', 'component'], true)) {
                $value = sanitize_file_name(wp_basename((string) $value));
            } elseif ($key === 'code') {
                $value = sanitize_key((string) $value);
            } else {
                $value = absint($value);
            }
            $safe_context[$key] = $value;
        }

        $entries = get_option(self::OPTION, []);
        if (!is_array($entries)) $entries = [];
        array_unshift($entries, [
            'time' => current_time('mysql'),
            'level' => $level,
            'event' => $event,
            'context' => $safe_context,
        ]);
        $entries = array_slice($entries, 0, self::MAX_ENTRIES);
        update_option(self::OPTION, $entries, false);
        return true;
    }

    public static function capture_fatal_error() {
        if (!self::enabled()) return;
        $error = error_get_last();
        if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) return;
        self::log('critical', 'php_fatal_error', [
            'file' => isset($error['file']) ? wp_basename($error['file']) : '',
            'line' => isset($error['line']) ? (int) $error['line'] : 0,
            'code' => 'type_' . (int) $error['type'],
        ]);
    }

    public static function capture_mail_failure($error) {
        if (!self::enabled() || !is_wp_error($error)) return;
        self::log('error', 'email_delivery_failed', [
            'code' => $error->get_error_code(),
            'component' => 'wp_mail',
        ]);
    }

    public static function entries() {
        $entries = get_option(self::OPTION, []);
        return is_array($entries) ? array_slice($entries, 0, self::MAX_ENTRIES) : [];
    }

    public static function clear() {
        update_option(self::OPTION, [], false);
    }
}

AVAM_Logger::init();
