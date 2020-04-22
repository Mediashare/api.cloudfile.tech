<?php
// send a file
$request = curl_init();
curl_setopt($request, CURLOPT_URL,"http://api.cloudfile.tech/upload");
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