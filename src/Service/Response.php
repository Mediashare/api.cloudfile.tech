<?php
namespace App\Service;

use App\Entity\Volume;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

Class Response {
    public function send(array $response, int $status = 200): JsonResponse {
        $response = new JsonResponse($response, $status);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }

    /**
     * Check if ApiKey exist & if Volume associated.
     *
     * @param Request $request
     * @return Response|null
     */
    public function checkAuthority(Request $request, EntityManager $em) {
        $apikey = $request->headers->get('apikey');
        if (!$apikey):
            return $this->send([
                'status' => 'error',
                'message' => 'ApiKey not found in Header.'
            ]);
        endif;
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        if (!$volume):
            return $this->send([
                'status' => 'error',
                'message' => 'Volume not found with your apikey.'
            ]);
        endif;
        // Checkup valid!
        return null;
    }
}