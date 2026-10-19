<?php
include('../config.php');

if (isset($_POST['login'])) {
    $email = trim($_POST['emailaddress']);
    $password = $_POST['pwd'];

    $statement = mysqli_prepare($conn, 'SELECT * FROM users WHERE email_address = ?');
    mysqli_stmt_bind_param($statement, 's', $email);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($statement);

        $login_success = false;

        // Check if password matches bcrypt hash
        if (password_verify($password, $user['password_hash'])) {
            $login_success = true;
        } elseif ($user['password_hash'] === md5('pync' . $password)) {
            // Backward compatibility: if matches old md5 hash, rehash to bcrypt
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $update = mysqli_prepare($conn, 'UPDATE users SET password_hash = ? WHERE id = ?');
            mysqli_stmt_bind_param($update, 'si', $new_hash, $user['id']);
            mysqli_stmt_execute($update);
            mysqli_stmt_close($update);
            $login_success = true;
        }

        if ($login_success) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            // REQUEST_SCHEME is not set by every SAPI (e.g. PHP's built-in server),
            // so derive the scheme from HTTPS to avoid building a malformed redirect.
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/') . '/';
            if ($user['role_as'] == 1) {
                $redirect_url = $base_url . 'admin-dashboard/index.php';
            } else {
                $redirect_url = $base_url . 'user-dashboard/index.php';
            }
            header("Location: $redirect_url");
            exit();
        } else {
            $_SESSION['message'] = "Invalid email or password";
            header("Location: ../sign-in.php");
            exit();
        }
    } else {
        $_SESSION['message'] = "Invalid email or password";
        header("Location: ../sign-in.php");
        exit();
    }
}
?>
