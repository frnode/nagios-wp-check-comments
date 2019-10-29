<?php

// Include your Nagios server IP below
// It is safe to keep 127.0.0.1
$allowed_ips = array(
    '127.0.0.1',
    '10.10.10.121',
    '10.10.10.100'
);

// If your Wordpress installation is behind a Proxy like Nginx use 'HTTP_X_FORWARDED_FOR'
if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $remote_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $remote_ip = $_SERVER['REMOTE_ADDR'];
}

// Check if the requesting server is allowed
if (! in_array($remote_ip, $allowed_ips)) {
    echo "CRITICAL#IP $remote_ip not allowed.";
    exit;
}

require_once('wp-load.php');

global $wpdb;

$hour = 4;

$check_comments = $wpdb->get_results($wpdb->prepare(
    "
    SELECT comment_author_email, comment_author_IP, COUNT(comment_author_IP) as comments_count
    FROM $wpdb->comments 
    WHERE comment_date >= DATE_SUB(NOW(),INTERVAL %s HOUR) 
    GROUP BY comment_author_IP 
    HAVING COUNT(*) >= 4
	",
    $hour
), ARRAY_A);

$text = array();
$status = 'OK';

foreach ($check_comments as $result){
    if ($status == 'OK')
        if ($result['comments_count'] < 10 AND $status != 'CRITICAL')
            $status = 'WARNING';
        elseif ($result['comments_count'] >= 10)
            $status = 'CRITICAL';

    $text[] = $result['comments_count'] . " comments from the IP address/email address: " . $result['comment_author_IP'] . "/" . $result['comment_author_email'];
}

echo $status . '#' . implode($text, ';');
