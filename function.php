<?php

include('config.php');
include('helpers.php');

// Identity comes from the server session only (see helpers.php::current_user_id).
$user_id = current_user_id();


// Check if function already exists to avoid redeclaration error
if (!function_exists('user_account_information')) {
    //user account information in  this function
    function user_account_information(){

    global $conn;
    global $user_id;

    $emptyAccount = array (
        'first_name' => '',
        'last_name' => '',
        'phone_number' => '',
        'email_address' => '',
        'date_of_birth' => '',
        'account_number' => '',
        'gender' => '',
        'role_as' => '',
        'file' => '',
        'address_type' => '',
        'parish' => '',
        'region' => '',
        'address_line1' => '',
        'address_line2' => '',
        'password_hash' => ''
    );

    if ($user_id === null) {
        return $emptyAccount;
    }

    $statement = mysqli_prepare($conn, 'SELECT * FROM users WHERE id = ?');
    mysqli_stmt_bind_param($statement, 'i', $user_id);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
	$rows  =  mysqli_fetch_assoc($result);
	mysqli_stmt_close($statement);

	if (!$rows) {
	    return $emptyAccount;
	}

	return array (

	 'first_name' => $rows['first_name'],
	 'last_name' => $rows['last_name'],
	 'phone_number' => $rows['phone_number'],
	 'email_address' => $rows['email_address'],
	 'date_of_birth' => $rows['date_of_birth'],
	 'account_number' => $rows['account_number'],
	 'gender' => $rows['gender'],
	 'role_as' => $rows['role_as'],
	 'file' => $rows['file'],
	 'address_type' => $rows['address_type'],
	 'parish' => $rows['parish'],
	 'region' => $rows['region'],
	 'address_line1' => $rows['address_line1'],
	 'address_line2' => $rows['address_line2'],
	 'password_hash' => $rows['password_hash']


	);

    }
};

if (!function_exists('Authorized_User')) {
    // Authorized User's information in this function
    function Authorized_User(){

         global $conn;
         global $user_id;

        if ($user_id === null) {
            return ['first_name' => '', 'last_name' => '', 'IdType' => '', 'IdNumber' => ''];
        }

        $statement = mysqli_prepare($conn, 'SELECT * FROM authorized_users WHERE user_id = ?');
        mysqli_stmt_bind_param($statement, 'i', $user_id);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
    	$rows  =  mysqli_fetch_assoc($result);
    	mysqli_stmt_close($statement);
    	if (!$rows) {
    	    return [
    	        'first_name' => '',
    	        'last_name' => '',
    	        'IdType' => '',
    	        'IdNumber' => ''
    	    ];
    	}
    	return array (

    	 'first_name' => $rows['first_name'],
    	 'last_name' => $rows['last_name'],
    	 'IdType' => $rows['id_type'],
    	 'IdNumber' => $rows['id_number']
    	);

    };
}

if (!function_exists('timeAgo')) {
    // time format function
    function timeAgo($datetime, $full = false) {

    	    //$tz = new DateTimeZone('America/New_York');
    		date_default_timezone_set('America/Jamaica');
            $now = new DateTime();
            $ago = new DateTime($datetime);
            $diff = $now->diff($ago);

            $weeks = floor($diff->d / 7);
            $diff->d -= $weeks * 7;

            $string = [
                'y' => 'year',
                'm' => 'month',
                'w' => 'week',
                'd' => 'day',
                'h' => 'hour',
                'i' => 'minute',
                's' => 'second',
            ];
            foreach ($string as $k => &$v) {
                if ($k === 'w') {
                    if ($weeks > 0) {
                        $v = $weeks . ' ' . $v . ($weeks > 1 ? 's' : '');
                    } else {
                        unset($string[$k]);
                        continue;
                    }
                } elseif ($diff->$k) {
                    $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
                } else {
                    unset($string[$k]);
                }
            }

            if (!$full) $string = array_slice($string, 0, 1);
            return $string ? implode(', ', $string) . ' ago' : 'just now';
    };
}

if (!function_exists('calculate_value_of_package')) {
    // Calculate shipping cost based on weight using rates.json
    function calculate_value_of_package($weight) {
        $rates_file = __DIR__ . '/rates.json';
        if (!file_exists($rates_file)) {
            return 0.00; // or handle error
        }
        $rates_data = json_decode(file_get_contents($rates_file), true);
        $additional_rate = (float) $rates_data['additional_rate_above_20'];
        $minimum_weight = (float) $rates_data['minimum_weight'];

        $price_by_weight = [];
        $max_weight = 0;
        foreach ($rates_data['rates'] as $rate) {
            $price_by_weight[(string) $rate['weight']] = (float) $rate['price'];
            $max_weight = max($max_weight, (float) $rate['weight']);
        }

        $weight = (float) $weight;
        if ($weight <= 0) {
            return 0.00;
        }

        // Mirrors the client-side computePrice() in js/custom.js so quotes match.
        if ($weight <= $minimum_weight) {
            return $price_by_weight[(string) $minimum_weight] ?? 0.00;
        }
        if ($weight <= $max_weight) {
            $tier = ($weight <= 1) ? 1 : (int) ceil($weight);
            return $price_by_weight[(string) $tier] ?? 0.00;
        }

        $base_price = $price_by_weight[(string) $max_weight] ?? 0.00;
        $extra_units = (int) ceil($weight - $max_weight);
        return $base_price + ($extra_units * $additional_rate);
    }
}
?>
