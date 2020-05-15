<?php
namespace App\Service;

use Mediashare\Kernel\Kernel;

Class PingIt {
    private $request;
    private $url = "http://pingit.marquand.pro";
    private $apikey;
    public function __construct(string $apikey) {
        $this->apikey = $apikey;
        $kernel = new Kernel();
        $this->request = $kernel->get('Curl');
    }

    public function send(?string $name = 'Ping!', ?string $message = null, ?string $icon = 'map-pin', ?string $color = 'primary') {
        $url = $this->url.'/api/ping/'.$this->apikey;
        $response = $this->request->post($url, [
            'name' => $name,
            'message' => $message,
            'icon' => $icon,
            'color' => $color,
        ]);
        $response = \json_decode($response);
        return $response;
    }
}