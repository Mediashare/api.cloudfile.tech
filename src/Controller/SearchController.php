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
        // Check Authority
        $response = new Response();
        $em = $this->getDoctrine()->getManager();
        $apikey = $request->headers->get('apikey');
        if ($apikey):
            $authority = $response->checkAuthority($em, $apikey);
            if ($authority):
                return $authority;
            endif;
            $files = $em->getRepository(File::class)->findBy(['apiKey' => $apikey], ['createDate' => 'DESC']);
        else:
            $files = $em->getRepository(File::class)->findBy(['private' => false], ['createDate' => 'DESC']);
        endif;

        $queries = $this->getRealInput('GET');
        if (!$queries && $request->getContent()):
            $queries = \json_decode($request->getContent(), true);
        elseif (!$queries):
            $queries = $request->query->all();
        endif;
        $size = 0;
        $results = [];
        if (!empty($queries)):
            foreach ($files as $index => $file):
                foreach ($queries as $query => $value):
                    if ($score = $this->searchInArray($info = $file->getInfo(), $query, $value)):
                        if (isset($results[$file->getId()])):
                            $score += $results[$file->getId()]['score'];
                        else:
                            $size += $file->getSize();
                        endif;
                        $results[$file->getId()] = [
                            'score' => $score,
                            'file' => $info,
                            'volume' => [
                                'id' => $file->getVolume()->getId(),
                                'name' => $file->getVolume()->getName()
                            ]
                        ];
                    else: // Remove if score = 0
                        unset($results[$file->getId()]);
                        break;
                    endif;
                endforeach;
            endforeach;
        endif;
        // Order
        usort($results, function($a, $b) {return $a['score'] <=> $b['score'];});
        $results = array_reverse($results, false);
        $results = array_slice($results, 0, 100);
        // Response
        $fileSystem = new FileSystemApi();
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

    private function searchInArray(array $array, string $key, ?string $query = null, ?float $score = 0) {
        foreach ($array as $index => $value):
            // if ($this->compare($index, $key)): // index === $key
                // \similar_text($index, $key, $percent_index_key);
                // if ($query && is_string($value) && $this->compare($value, $query)): // index === $key && value === $query
                //     \similar_text($value, $query, $percent_value_query);
                //     $score += $percent_value_query + $percent_index_key;
                // elseif (!$query): // index === $key && !$query
                //     $score += $percent_index_key;
                // endif;
            // elseif (!$query && is_string($value) && $this->compare($value, $key)): // index !== $key && !$query && value === $key
            // endif;
            \similar_text($value, $key, $percent_value_key); 
            $score += $percent_value_key * 1.5;

            // // Array recursive
            // if (is_array($value)):
            //     $result = $this->searchInArray((array) $value, $key, $query, $score);
            //     if (!empty($result)):
            //         $score += $result;
            //     endif;
            // endif;
        endforeach;
        return $score;
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
