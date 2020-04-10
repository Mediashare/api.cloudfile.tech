<?php

namespace App\Controller;

use App\Entity\File;
use App\Service\Response;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SearchController extends AbstractController
{
    /**
     * @Route("/search", name="search")
     */
    public function search(Request $request) {
        $fileSystem = new FileSystemApi();
        $files = $fileSystem->getFiles($request, $this->getDoctrine()->getManager());
        $queries = $request->query->all();
        $size = 0;
        $results = [];
        foreach ($files as $file):
            foreach ($queries as $query => $value):
                if (\strpos($file->getName(), $value) !== false):
                    $results[] = $file->getInfo();
                    $size += $file->getSize();
                endif;
            endforeach;
        endforeach;
        $response = new Response();
        return $response->send([
            'status' => 'success',
            'queries' => $queries,
            'files' => [
                'counter' => count($results),
                'size' => $fileSystem->getSizeReadable($size),
                'results' => $results
            ],
        ]);
    }
}
