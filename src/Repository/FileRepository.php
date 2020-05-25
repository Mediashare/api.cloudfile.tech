<?php

namespace App\Repository;

use App\Entity\File;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method File|null find($id, $lockMode = null, $lockVersion = null)
 * @method File|null findOneBy(array $criteria, array $orderBy = null)
 * @method File[]    findAll()
 * @method File[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
        parent::__construct($registry, File::class);
    }

    public function pagination(?string $apiKey = null, int $page, ?int $max = 100) {
        $first_result = $max * ($page - 1);
        if ($apiKey):
            $files = $this->createQueryBuilder('f')
                ->andWhere('f.apiKey = :apiKey')
                ->setParameter('apiKey', $apiKey)
                ->orderBy('f.createDate', 'DESC')
                ->setFirstResult($first_result)
                ->setMaxResults($max)
                ->getQuery()
                ->getResult();
        else:
            $files = $this->createQueryBuilder('f')
                ->andWhere('f.private = :private')
                ->setParameter('private', false)
                ->orderBy('f.createDate', 'DESC')
                ->setFirstResult($first_result)
                ->setMaxResults($max)
                ->getQuery()
                ->getResult();
        endif;

        return $files;
    }

    public function search(?array $parameters = [], ?string $apiKey = null) {
        if ($apiKey):
            $query = $this->createQueryBuilder('f')
                ->where('f.apiKey = :apiKey')
                ->setParameter('apiKey', $apiKey)
                ->orderBy('f.createDate', 'DESC');
        else:
            $query = $this->createQueryBuilder('f')
                ->where('f.private = :private')
                ->setParameter('private', false)
                ->orderBy('f.createDate', 'DESC');
        endif;

        $classMetadata = $this->registry->getManager()->getClassMetadata(File::class);
        $fields = $classMetadata->getColumnNames();
        foreach ($parameters as $column => $value):
            if (!$value): $value = $column; $column = 'name'; endif;
            if (in_array($classMetadata->getColumnName($column), $fields)):
                $query = $query->andWhere('f.'.$column.' LIKE :'.$column)->setParameter($column, '%'.$value.'%');
                unset($parameters[$column]);
            endif;
        endforeach;
        $files = $query->getQuery()->getResult();
        
        $size = 0;
        $results = [];
        foreach ($files as $index => $file):
            foreach ($parameters as $key => $value):
                if ($value && $score = $this->searchInArray($file->getInfo(), $key, $value)): // Complexe search in all file data
                    if (!isset($results[$file->getId()])): $size += $file->getSize(); endif;
                    $results = $this->addResult($results, $file, $score, $all_data = true);
                else: 
                    unset($results[$file->getId()]);
                    break;
                endif;
            endforeach;
        endforeach;

        // Order
        usort($results, function($a, $b) {return $a['score'] <=> $b['score'];});
        $results = array_reverse($results, false);

        return [
            'size' => $size,
            'results' => $results
        ];
    }

    private function searchInArray(array $array, string $key, ?string $query = null, ?float $score = 0) {
        foreach ($array as $index => $value):
            if ($this->compare($index, $key)): // index === $key
                \similar_text($index, $key, $percent_index_key);
                if ($query && is_string($value) && $this->compare($value, $query)): // index === $key && value === $query
                    \similar_text($value, $query, $percent_value_query);
                    $score += $percent_value_query + $percent_index_key;
                elseif (!$query): // index === $key && !$query
                    $score += $percent_index_key;
                endif;
            elseif (!$query && is_string($value) && $this->compare($value, $key)): // index !== $key && !$query && value === $key
                \similar_text($value, $key, $percent_value_key); 
                $score += $percent_value_key * 1.5;
            endif;

            // Array recursive
            if (is_array($value)):
                $result = $this->searchInArray((array) $value, $key, $query, $score);
                if (!empty($result)):
                    $score += $result;
                endif;
            endif;
        endforeach;
        return $score;
    }

    // levenshtein || similar_text
    private function compare(string $haystack, string $needle) {
        if (strlen($haystack) < strlen($needle)) return false;
        if (\strpos(\strtolower($haystack), \strtolower($needle)) !== false):
            return true;
        else:
            return false;
        endif;
    }

    private function addResult(array $results, File $file, float $score, ?bool $all_data = false): array {
        if (isset($results[$file->getId()])):
            $score += $results[$file->getId()]['score'];
        endif;
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
