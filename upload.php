<?php
// send a file
$request = curl_init();
$url = "http://127.0.0.1:8001/upload";
$url = "http://api.cloudfile.tech/upload";
curl_setopt($request, CURLOPT_URL, $url);
curl_setopt($request, CURLOPT_POST, true);
curl_setopt(
    $request,
    CURLOPT_POSTFIELDS,
    ['file' => curl_file_create('README.md')]
);

// output the response
curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
echo curl_exec($request);

// close the session
curl_close($request);