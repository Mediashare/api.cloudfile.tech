<?php
/**
 * Clear file database & remove this from the stockage.
 */

$files = getFiles();
foreach ($files as $file) {
    $id = $file->id;
    remove_file($id);
}
function getFiles(): array {
    $request = curl_init();
    curl_setopt($request, CURLOPT_URL,"http://127.0.0.1:8001/");
    // output the response
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($request);
    $response = json_decode($response);
    // close the session
    curl_close($request);
    
    return $response->files->results;
}
function remove_file(string $id) {
    $request = curl_init();
    curl_setopt($request, CURLOPT_URL,"http://127.0.0.1:8001/remove/".$id);
    // output the response
    curl_setopt($request, CURLOPT_RETURNTRANSFER, false);
    $response = curl_exec($request);
    // close the session
    curl_close($request);
}