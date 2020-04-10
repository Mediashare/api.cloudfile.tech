<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

Class Response {
    public function send(array $response, int $status = 200): JsonResponse {
        $response = new JsonResponse($response, $status);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }
}