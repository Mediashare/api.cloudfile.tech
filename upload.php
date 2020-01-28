<?php
if (function_exists('curl_file_create')) { // php 5.5+
    $file = curl_file_create(realpath('README.md'));
} else { // 
    $file = '@' . realpath('README.md');
}

$post = array('extra_info' => '123456', 'file' => $file);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"http://localhost:8000/upload");
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
$result=curl_exec($ch);
curl_close($ch);
echo $result;


function buildMultiPartRequest($ch, $boundary, $fields, $files) {
    $delimiter = '-------------' . $boundary;
    $data = '';

    foreach ($fields as $name => $content) {
        $data .= "--" . $delimiter . "\r\n"
            . 'Content-Disposition: form-data; name="' . $name . "\"\r\n\r\n"
            . $content . "\r\n";
    }
    foreach ($files as $name => $content) {
        $data .= "--" . $delimiter . "\r\n"
            . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . "\r\n\r\n"
            . $content . "\r\n";
    }

    $data .= "--" . $delimiter . "--\r\n";

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($data)
        ],
        CURLOPT_POSTFIELDS => $data
    ]);

    return $ch;
}

// and here's how you'd use it
// $ch = curl_init('http://localhost:8000/upload');
// $ch = buildMultiPartRequest($ch, uniqid(),
//     ['key' => 'value', 'key2' => 'value2'], ['README.md' => file_get_contents('README.md')]);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// echo curl_exec($ch);