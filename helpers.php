<?php
// Helper functions for Pync Parcel Chateau

if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

if (!function_exists('generate_token')) {
    function generate_token($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

if (!function_exists('current_user_id')) {
    // Returns the authenticated user id from the server session, or null.
    // Tolerates the legacy json cookie/session shape ({"id": n}).
    function current_user_id() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        $stored = $_SESSION['user_id'];
        if (is_numeric($stored)) {
            return (int) $stored;
        }
        $decoded = json_decode($stored);
        if (is_object($decoded) && isset($decoded->id)) {
            return (int) $decoded->id;
        }
        return null;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return current_user_id() !== null;
    }
}

if (!function_exists('current_user_role')) {
    // Returns the role_as value for the logged-in user, or null.
    function current_user_role() {
        global $conn;
        $userId = current_user_id();
        if ($userId === null) {
            return null;
        }
        $statement = mysqli_prepare($conn, 'SELECT role_as FROM users WHERE id = ?');
        mysqli_stmt_bind_param($statement, 'i', $userId);
        mysqli_stmt_execute($statement);
        $role = null;
        mysqli_stmt_bind_result($statement, $role);
        mysqli_stmt_fetch($statement);
        mysqli_stmt_close($statement);
        return $role === null ? null : (int) $role;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return current_user_role() === 1;
    }
}

if (!function_exists('require_login')) {
    function require_login($base_url) {
        if (!is_logged_in()) {
            header('Location: ' . $base_url . 'sign-in.php');
            exit();
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin($base_url) {
        if (!is_admin()) {
            header('Location: ' . $base_url . 'sign-in.php');
            exit();
        }
    }
}

if (!function_exists('e')) {
    // Escapes a value for safe output inside HTML.
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '" />';
    }
}

if (!function_exists('verify_csrf')) {
    // Validates the submitted token against the session token in constant time.
    function verify_csrf() {
        $submitted = $_POST['csrf_token'] ?? $_REQUEST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !is_string($submitted)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $submitted);
    }
}

if (!function_exists('require_csrf')) {
    function require_csrf() {
        if (!verify_csrf()) {
            http_response_code(419);
            die('Invalid or expired request token. Please go back and try again.');
        }
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit();
    }
}

if (!function_exists('display_message')) {
    function display_message($message, $type = 'info') {
        $class = 'alert alert-' . $type;
        return "<div class='$class'>$message</div>";
    }
}
?>
