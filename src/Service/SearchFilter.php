<?php
namespace App\Service;

use App\Entity\File;

Class SearchFilter {
    private $files = [];
    private $parameters = [];

    public function setFiles(?array $files = []): self {
        $this->files = $files;
        return $this;
    }

    public function setParameters(?array $parameters = []): self {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Order files by scoring
     *
     * @return array
     */
    public function filter(): array {
        $size = 0;
        $results = [];
        foreach ($this->files as $index => $file):
            $score = 1;
            foreach ($this->parameters as $column => $value):
                if (!$value): $value = $column; $column = "name"; endif;
                $file_score = $this->searchInArray($file->getInfo(), $column, $value);
                if (!$file_score): $score = 0; break; 
                else: $score += $file_score; endif;
            endforeach;
            if ($score):
                $results = $this->addResult($results, $file, $score, $all_data = true);
                $size += $file->getSize();
            endif;
        endforeach;

        usort($results, function($a, $b) {return $a['score'] <=> $b['score'];});
        $results = array_reverse($results, false);

        return [
            'results' => $results,
            'size' => $size
        ];
    }

    private function searchInArray(array $array, string $column, string $needle) {
        $score = 0;
        foreach ($array as $field => $value):
            if (\is_array($value)):
                $score += $this->searchInArray($value, $column, $needle);
            elseif (strpos(strtolower($field), strtolower($column)) !== false):
                $score += $this->compare($value, $needle);
            endif;
        endforeach;

        return $score;
    }

    // levenshtein || similar_text
    private function compare(string $haystack, string $needle) {
        if (\strpos(strtolower($haystack), strtolower($needle)) !== false):
            \similar_text(strtolower($haystack), strtolower($needle), $score);
            return $score;
        else:
            return false;
        endif;
    }

    private function addResult(array $results, File $file, float $score, ?bool $all_data = false): array {
        $results[$file->getId()] = [
            'score' => $score,
            'file' => $file->getInfo($all_data),
            'volume' => [
                'id' => $file->getVolume()->getId(),
                'name' => $file->getVolume()->getName()
            ]
        ];
        return $results;
    }
}