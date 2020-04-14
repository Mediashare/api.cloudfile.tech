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
     * Search file(s)
     * @Route("/search", name="search")
     */
    public function search(Request $request) {
        $fileSystem = new FileSystemApi();
        $files = $fileSystem->getFiles($request, $this->getDoctrine()->getManager());
        $queries = $this->getRealInput('GET');
        if (!$queries && $request->getContent()):
            $queries = \json_decode($request->getContent(), true);
        endif;
        $size = 0;
        $results = [];
        if ($queries):
            foreach ($files as $index => $file):
                foreach ($queries as $query => $value):
                    if ($score = $this->searchInArray($file->getInfo(), $query, $value)):
                        if (isset($results[$file->getId()])):
                            $score += $results[$file->getId()]['score'];
                        else:
                            $size += $file->getSize();
                        endif;
                        $results[$file->getId()] = [
                            'score' => $score,
                            'file' => $file->getInfo()
                        ];
                    else: // Remove if score = 0
                        unset($results[$file->getId()]);
                        break;
                    endif;
                endforeach;
            endforeach;
        endif;
        // Order
        usort($results, function($a, $b) {
            return $a['score'] <=> $b['score'];
        });
        $results = array_reverse($results, false);
        // Response
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
        $results = 0;
        foreach ($array as $index => $value):
            if ($this->compare($index, $key)): // index === $key
                \similar_text($index, $key, $percent_index_key);
                if ($query && is_string($value) && $this->compare($value, $query)): // index === $key && value === $query
                    \similar_text($value, $query, $percent_value_query);
                    $results += $percent_value_query + $percent_index_key;
                elseif (!$query): // index === $key && !$query
                    $results += $percent_index_key;
                else: // index === $key && value !== $query
                    $results = 0;
                endif;
            elseif (is_string($value) && $this->compare($value, $key)): // index !== key && value === $key
                \similar_text($value, $key, $percent_value_key); 
                $results += $percent_value_key * 1.5;
            endif;

            // Array recursive
            if (is_array($value)):
                $result = $this->searchInArray($value, $key, $query);
                if (!empty($result)):
                    $results += $result;
                endif;
            endif;
        endforeach;
        return $results;
    }

    // levenshtein || similar_text
    private function compare(string $haystack, string $needle) {
        if (\strpos(\strtolower($haystack), \strtolower($needle)) !== false):
            return true;
        else:
            return false;
        endif;
    }

    private function getRealInput($source) {
        $pairs = explode("&", $source == 'POST' ? file_get_contents("php://input") : $_SERVER['QUERY_STRING']);
        $vars = array();
        foreach ($pairs as $pair) {
            $nv = explode("=", $pair);
            $name = trim(urldecode($nv[0]));
            if (isset($nv[1])):
                $value = trim(urldecode($nv[1]));
            endif;
            $vars[$name] = $value ?? null;
        }
        return $vars;
    }
}
