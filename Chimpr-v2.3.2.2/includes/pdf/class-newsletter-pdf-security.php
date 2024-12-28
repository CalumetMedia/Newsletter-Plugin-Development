<?php
if (!defined('ABSPATH')) exit;

class Newsletter_PDF_Security {
    private $secure_dir;
    private $secure_url;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->secure_dir = $upload_dir['basedir'] . '/secure';
        $this->secure_url = $upload_dir['baseurl'] . '/secure';

        // Initialize security measures
        add_action('init', [$this, 'setup_security']);
        add_action('template_redirect', [$this, 'handle_pdf_access']);
        add_filter('mod_rewrite_rules', [$this, 'add_rewrite_rules']);
        
        // Handle PDF downloads
        add_action('wp_ajax_download_newsletter_pdf', [$this, 'handle_pdf_download']);
        add_action('wp_ajax_nopriv_download_newsletter_pdf', [$this, 'handle_pdf_download']);
    }

    public function setup_security() {
        // Create secure directory if it doesn't exist
        if (!file_exists($this->secure_dir)) {
            wp_mkdir_p($this->secure_dir);
        }

        // Create/update .htaccess for directory protection
        $htaccess = $this->secure_dir . '/.htaccess';
        $rules = $this->get_htaccess_rules();
        
        if (!file_exists($htaccess) || file_get_contents($htaccess) !== $rules) {
            file_put_contents($htaccess, $rules);
        }

        // Create index.php to prevent directory listing
        $index = $this->secure_dir . '/index.php';
        if (!file_exists($index)) {
            file_put_contents($index, '<?php // Silence is golden');
        }
    }

    private function get_htaccess_rules() {
        $rules = array(
            'Order Deny,Allow',
            'Deny from all',
            '',
            '<Files ~ "\.pdf$">',
            '    Allow from env=REDIRECT_STATUS',
            '</Files>',
            '',
            '# Prevent viewing of .htaccess file',
            '<Files .htaccess>',
            '    Order allow,deny',
            '    Deny from all',
            '</Files>',
            '',
            '# Disable script execution',
            'AddHandler cgi-script .php .php3 .php4 .php5 .pl .py .jsp .asp .htm .html .shtml .sh .cgi',
            'Options -ExecCGI',
            '',
            '# Secure directory browsing',
            'Options All -Indexes',
            '',
            '# Additional security headers',
            '<IfModule mod_headers.c>',
            '    Header set X-Content-Type-Options "nosniff"',
            '    Header set X-Frame-Options "DENY"',
            '    Header set X-XSS-Protection "1; mode=block"',
            '</IfModule>'
        );

        return implode("\n", $rules);
    }

    public function add_rewrite_rules($rules) {
        $new_rules = "RewriteRule ^secure/(.+)\.pdf$ /wp-admin/admin-ajax.php?action=download_newsletter_pdf&file=$1 [L]\n";
        return $new_rules . $rules;
    }

    public function handle_pdf_access() {
        if (!is_admin() && strpos($_SERVER['REQUEST_URI'], '/secure/') !== false) {
            // Check if user has access
            if (!$this->user_can_access_pdf()) {
                wp_die('Unauthorized access', 'Access Denied', ['response' => 403]);
            }
        }
    }

    public function handle_pdf_download() {
        // Verify nonce if user is logged in
        if (is_user_logged_in()) {
            check_ajax_referer('newsletter_pdf_download');
        }

        // Get filename from request
        $filename = isset($_GET['file']) ? sanitize_file_name($_GET['file']) . '.pdf' : '';
        if (empty($filename)) {
            wp_die('Invalid request');
        }

        // Verify file exists and is within secure directory
        $file_path = $this->secure_dir . '/' . $filename;
        if (!file_exists($file_path) || !$this->is_file_in_secure_dir($file_path)) {
            wp_die('File not found', 'Not Found', ['response' => 404]);
        }

        // Check access permissions
        if (!$this->user_can_access_pdf()) {
            wp_die('Unauthorized access', 'Access Denied', ['response' => 403]);
        }

        // Send file
        $this->send_pdf_file($file_path);
    }

    private function user_can_access_pdf() {
        // Add your access control logic here
        return true; // Default to allow access as per v1.0
    }

    private function is_file_in_secure_dir($file_path) {
        $real_file_path = realpath($file_path);
        $real_secure_dir = realpath($this->secure_dir);
        
        return $real_file_path && $real_secure_dir && 
               strpos($real_file_path, $real_secure_dir) === 0;
    }

    private function send_pdf_file($file_path) {
        // Get file info
        $size = filesize($file_path);
        $name = basename($file_path);
        $mime = 'application/pdf';

        // Clean output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Send headers
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . $size);
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

        // Send file
        readfile($file_path);
        exit;
    }

    public function get_secure_url($filename) {
        $nonce = wp_create_nonce('newsletter_pdf_download');
        return add_query_arg([
            'action' => 'download_newsletter_pdf',
            'file' => basename($filename, '.pdf'),
            '_wpnonce' => $nonce
        ], admin_url('admin-ajax.php'));
    }
}
