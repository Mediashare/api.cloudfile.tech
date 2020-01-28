<?php
// send a file
$request = curl_init();
curl_setopt($request, CURLOPT_URL,"http://localhost:8001/upload");
curl_setopt($request, CURLOPT_POST, true);
curl_setopt(
    $request,
    CURLOPT_POSTFIELDS,
    [
        'file' => curl_file_create(realpath('README.md')),
        'file2' => curl_file_create(realpath('composer.json'))
    ]
);

// output the response
curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
echo curl_exec($request);

// close the session
curl_close($request);