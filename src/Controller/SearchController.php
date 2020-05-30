<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Volume;
use App\Service\Response;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SearchController extends AbstractController
{
    /**
     * Search file(s)
     * @Route("/search", name="search")
     */
    public function search(Request $request) {
        $queries = $this->getRealInput('GET');
        if (!$queries && $request->getContent()):
            $queries = \json_decode($request->getContent(), true);
        elseif (!$queries):
            $queries = $request->query->all();
        endif;

        // Check Authority
        $response = new Response();
        $em = $this->getDoctrine()->getManager();
        $apikey = $request->headers->get('apikey');
        if ($apikey):
            $authority = $response->checkAuthority($em, $apikey);
            if ($authority):
                return $authority;
            endif;
            $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey]);
            $files = $em->getRepository(File::class)->search($queries, $volume);
        else:
            $files = $em->getRepository(File::class)->search($queries);
        endif;

        // Response
        $fileSystem = new FileSystemApi();
        return $response->send([
            'status' => 'success',
            'queries' => $queries,
            'files' => [
                'counter' => count($files['results']),
                'size' => $fileSystem->getSizeReadable($files['size']),
                'results' => $files['results']
            ],
        ]);
    }

    private function getRealInput($source) {
        $pairs = explode("&", $source == 'POST' ? file_get_contents("php://input") : $_SERVER['QUERY_STRING']);
        $vars = array();
        foreach ($pairs as $pair) {
            $name = null;
            $value = null;
            $nv = explode("=", $pair);
            $name = trim(urldecode($nv[0]));
            if (isset($nv[1])):
                $value = trim(urldecode($nv[1]));
            endif;
            if ($name || $value):
                $vars[$name] = $value ?? null;
            endif;
        }
        return $vars;
    }
}
