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
        $index = 0;
        foreach ($parameters as $column => $value):
            $index++; 
            if (!$value): $value = $column; $column = 'name'; endif;
            if (in_array($classMetadata->getColumnName($column), $fields)):
                $query = $query->andWhere('f.'.$column.' LIKE :'.$column.'_'.$index)->setParameter($column.'_'.$index, '%'.$value.'%');
            else: unset($parameters[$column]); endif;
        endforeach;
        $files = $query->getQuery()->getResult();

        $size = 0;
        $results = [];
        if (!empty($parameters)):
            foreach ($files as $index => $file):
                foreach ($parameters as $column => $value):
                    if (!$value): $value = $column; endif;
                    if ($score = $this->compare($file->getName(), $value)): // Simple search in file name
                        $results = $this->addResult($results, $file, $score, $all_data = true);
                    else: 
                        unset($results[$file->getId()]);
                        break;
                    endif;
                endforeach;
                if (!isset($results[$file->getId()])): $size += $file->getSize(); endif;
            endforeach;
        endif;

        // Order
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
        $score = \levenshtein($haystack, $needle);
        if ($score <= $max_cost):
            return $score;
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
