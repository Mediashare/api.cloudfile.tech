<?php
if (function_exists('curl_file_create')) { // php 5.5+
    $file = curl_file_create(realpath('README.md'));
} else { // 
    $file = '@' . realpath('README.md');
}

$post = ['file' => $file];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"http://localhost:8000/upload");
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
$result = curl_exec($ch);
curl_close($ch);