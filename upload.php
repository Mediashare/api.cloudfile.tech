<?php
// send a file
$request = curl_init();
curl_setopt($request, CURLOPT_URL,"http://127.0.0.1:8001/upload");
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