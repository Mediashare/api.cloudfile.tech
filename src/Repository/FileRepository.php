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

    public function pagination(?string $apiKey = null, ?int $page = 1, ?int $max = 100) {
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

    public function search(?array $parameters = [], ?string $apiKey = null, int $page = 1, ?int $max = 1000) {
        if ($apiKey):
            $query = $this->createQueryBuilder('f')
                ->where('f.apiKey = :apiKey')
                ->setParameter('apiKey', $apiKey)
                ->orderBy('f.name', 'ASC')
                ->setMaxResults($max);
        else:
            $query = $this->createQueryBuilder('f')
                ->where('f.private = :private')
                ->setParameter('private', false)
                ->orderBy('length(f.name)', 'ASC')
                ->setMaxResults($max);
        endif;

        $classMetadata = $this->registry->getManager()->getClassMetadata(File::class);
        $fields = $classMetadata->getColumnNames();
        $index = 0;
        foreach ($parameters as $column => $value):
            $index++; 
            if (!$value): $value = $column; $column = 'name'; endif;
            if (in_array($classMetadata->getColumnName($column), $fields)):
                $query = $query->andWhere('f.'.$column.' LIKE :'.$column.'_'.$index)->setParameter($column.'_'.$index, '%'.$value.'%');
            endif;
        endforeach;
        $files = $query->getQuery()->getResult();
        
        // Order files by score
        $size = 0;
        $results = [];
        if (!empty($parameters)):
            foreach ($files as $index => $file):
                $score = 0;
                foreach ($parameters as $column => $value):
                    if (!$value): $value = $column; endif;
                    $score += $this->compare($file->getName(), $value);
                endforeach;
                $results = $this->addResult($results, $file, $score, $all_data = true);
                $size += $file->getSize();
            endforeach;
        endif;
        usort($results, function($a, $b) {return $a['score'] <=> $b['score'];});
        // $results = array_reverse($results, false);

        return [
            'size' => $size,
            'results' => $results
        ];
    }

    // levenshtein || similar_text
    private function compare(string $haystack, string $needle): ?int {
        $max_cost = strlen($haystack);
        if ($max_cost > 250):
            if (\strpos(strtolower($haystack), strtolower($needle)) !== false):
                return $max_cost - \strlen($needle);
            else:
                return false;
            endif;
        else:
            $score = \levenshtein($haystack, $needle);
            if ($score < $max_cost):
                return $score;
            else:
                return false;
            endif;
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
