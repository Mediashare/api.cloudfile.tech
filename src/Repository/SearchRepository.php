<?php

namespace App\Repository;

use App\Entity\Search;
use App\Service\SearchFilter;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Search|null find($id, $lockMode = null, $lockVersion = null)
 * @method Search|null findOneBy(array $criteria, array $orderBy = null)
 * @method Search[]    findAll()
 * @method Search[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Search::class);
    }

    public function search(?array $parameters = [], ?Volume $volume = null) {
        $query = $this->createQueryBuilder('s');
        // ->select('DISTINCT(s.file) as file')

        if ($volume):
            $query = $query->where('s.volume = :volume')->setParameter('volume', $volume);
        endif;

        $index = 0;
        foreach ($parameters as $field => $value):
            $index++;
            if (!$value): $value = $field; $field = 'name'; endif;
            $query = $query->andWhere('s.field LIKE :field')->setParameter('field', '%'.$field.'%');
            $query = $query->andWhere('s.value LIKE :value')->setParameter('value', '%'.$value.'%');
        endforeach;
        dd($query->getQuery());
        $founds = $query->getQuery()->getResult();

        foreach ($founds as $found):
            $file = $found->getFile();
            $files[$file->getId()] = $file;
        endforeach;
        
        if ($files ?? []):
            $searchFilter = new SearchFilter();
            $searchFilter->setFiles($files);
            $searchFilter->setParameters($parameters);
            $results = $searchFilter->filter();
        endif;

        return $results ?? ['results' => [], 'size' => 0];
    }
}
