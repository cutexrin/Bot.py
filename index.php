<?php

// =============================================
// InfinityFree অ্যান্টি-বট চেক বাইপাস
// =============================================
if (isset($_GET['i']) && $_GET['i'] == '1') {
    setcookie('__test', '1', time() + 3600, '/');
    header('Location: ' . str_replace('?i=1', '', $_SERVER['REQUEST_URI']));
    exit;
}

error_reporting(E_ALL); // পরিবর্তন: ডিবাগের জন্য error_reporting চালু
ini_set('display_errors', 1); // নতুন: error দেখাবে

header('Content-Type: application/json; charset=UTF-8');

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$target_base_url = "https://api.storytv.asia";

// =============================================
// ১. সাবস্ক্রিপশন স্টেট ফেক করা
// =============================================
if (strpos($request_uri, '/profile/subscription/state') !== false) {
    echo json_encode([
        "code" => 200,
        "message" => "Success",
        "data" => [
            "subStat" => "2",
            "mc" => "0"
        ]
    ]);
    exit;
}

// =============================================
// ২. অ্যানালিটিকস ব্লক করা
// =============================================
elseif (strpos($request_uri, '/analytics/v1/heartbeat') !== false || 
        strpos($request_uri, '/analytics/v1/impression') !== false) {
    echo json_encode([
        "status" => 200,
        "message" => "SUCCESS",
        "data" => null
    ]);
    exit;
}

// =============================================
// ৩. বাকি সব রিকোয়েস্ট আসল API-তে ফরওয়ার্ড
// =============================================
else {
    $target_url = $target_base_url . $request_uri;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $target_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    // হেডার সেট করা
    $headers = [];
    if (function_exists('getallheaders')) {
        $all_headers = getallheaders();
        foreach ($all_headers as $key => $value) {
            $key_lower = strtolower($key);
            if ($key_lower === 'host') {
                $headers[] = "Host: api.storytv.asia";
            } elseif ($key_lower === 'accept-encoding') {
                continue;
            } else {
                $headers[] = "$key: $value";
            }
        }
    } else {
        // getallheaders() না থাকলে নিজে হেডার বানাই
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                if ($header !== 'Host' && $header !== 'Accept-Encoding') {
                    $headers[] = "$header: $value";
                }
            }
        }
        $headers[] = "Host: api.storytv.asia";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // POST/PUT বডি ফরওয়ার্ড
    if ($method === 'POST' || $method === 'PUT') {
        $body = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch); // নতুন: error ধরার জন্য
    curl_close($ch);
    
    // যদি cURL ব্যর্থ হয়, তাহলে JSON error দেখাও
    if ($response === false) {
        http_response_code(500);
        echo json_encode([
            "error" => "cURL Error: " . $error,
            "target" => $target_url
        ]);
        exit;
    }
    
    // =============================================
    // ৪. শো টাইটেলে ব্র্যান্ডিং যোগ করা
    // =============================================
    if (strpos($request_uri, '/feedservice/v1/shows') !== false || 
        strpos($request_uri, '/feedservice/v1/homepage/struct') !== false) {
        
        $json_data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json_data['data']['content']) && is_array($json_data['data']['content'])) {
            foreach ($json_data['data']['content'] as $key => $item) {
                if (isset($item['title']) && strpos($item['title'], '[ cutexrin ]') === false) {
                    $json_data['data']['content'][$key]['title'] = $item['title'] . "\n [ cutexrin ] ";
                }
            }
            echo json_encode($json_data);
        } else {
            // যদি JSON ডিকোড না হয়, তাহলে আসল রেসপন্স ফেরত দাও
            http_response_code($http_code);
            echo $response;
        }
    } else {
        http_response_code($http_code);
        echo $response;
    }
    exit;
}

// getallheaders() ফাংশন backup (যদি থাকে তাহলে ওভাররাইট করবে না)
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

?>