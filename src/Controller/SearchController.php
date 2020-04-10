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
     * Search file by Name
     * @Route("/search", name="search")
     */
    public function searchByName(Request $request) {
        $fileSystem = new FileSystemApi();
        $files = $fileSystem->getFiles($request, $this->getDoctrine()->getManager());
        $queries = $request->query->all();
        $size = 0;
        $results = [];
        foreach ($files as $file):
            foreach ($queries as $query => $value):
                if (\strpos(\strtolower($file->getName()), \strtolower($value)) !== false):
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
    /**
     * Search file by custom field
     * @Route("/search/field", name="search_field")
     */
    public function searchByField(Request $request) {
        $fileSystem = new FileSystemApi();
        $files = $fileSystem->getFiles($request, $this->getDoctrine()->getManager());
        $queries = $request->query->all();
        $size = 0;
        $results = [];
        foreach ($files as $file):
            foreach ($queries as $query => $value):
                if ($this->searchInArray($file->getInfo(), $query, $value)):
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

    private function searchInArray(array $array, string $key, ?string $query = null) {
        $results = [];
        foreach ($array as $index => $value):
            if (\strtolower($index) == \strtolower($key)):
                if (!$query || ($query && \strpos(\strtolower($value), \strtolower($query)) !== false)):
                    $results[] = [$index => $value];
                endif;
            endif;
            if (is_array($value)):
                $result = $this->searchInArray($value, $key, $query);
                if (!empty($result)):
                    $results = array_merge($results, $result);
                endif;
            endif;
        endforeach;
        return $results;
    }
}
