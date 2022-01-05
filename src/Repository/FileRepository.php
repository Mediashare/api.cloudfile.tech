<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\Volume;
use App\Service\Response;
use App\Service\SearchFilter;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method File|null find($id, $lockMode = null, $lockVersion = null)
 * @method File|null findOneBy(array $criteria, array $orderBy = null)
 * @method File[]    findAll()
 * @method File[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, Response $response)
    {
        $this->registry = $registry;
        $this->response = $response;
        parent::__construct($registry, File::class);
    }

    public function pagination(?Volume $volume = null, ?int $page = 1, ?int $max = 100) {
        $first_result = $max * ($page - 1);
        if ($volume):
            $files = $this->createQueryBuilder('f')
                ->andWhere('f.volume = :volume')
                ->setParameter('volume', $volume)
                ->orderBy('f.createDate', 'DESC')
                ->setFirstResult($first_result)->setMaxResults($max)
                ->getQuery()->getResult();
        else:
            $files = $this->createQueryBuilder('f')
                ->andWhere('f.private = :private')
                ->setParameter('private', false)
                ->orderBy('f.createDate', 'DESC')
                ->setFirstResult($first_result)->setMaxResults($max)
                ->getQuery()->getResult();
        endif;

        return $files;
    }

    public function search(?array $parameters = [], ?Volume $volume = null) {
        if ($volume):
            $query = $this->createQueryBuilder('f')
                ->where('f.volume = :volume')
                ->setParameter('volume', $volume)
                ->orderBy('length(f.name)', 'ASC');
        else:
            $query = $this->createQueryBuilder('f')
                ->where('f.private = :private')
                ->setParameter('private', false)
                ->orderBy('length(f.name)', 'ASC');
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
        
        $searchFilter = new SearchFilter();
        $searchFilter->setFiles($files);
        $searchFilter->setParameters($parameters);
        $results = $searchFilter->filter();

        return $results;
    }

    /**
     * Check if ApiKey exist & if Volume associated.
     */
    public function authority(string $id, ?string $apikey = null) {
        if (!$apikey):
            return $this->response->json([
                'status' => 'error',
                'message' => 'ApiKey not found in Header/Post data.'
            ]);
        endif;

        $em = $this->getEntityManager();
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey]);
        if ($volume): $file = $em->getRepository(File::class)->findOneBy(['id' => $id, 'volume' => $volume]); // Volume ApiKey used
        else: $file = $em->getRepository(File::class)->findOneBy(['id' => $id, 'apikey' => $apikey]); endif; // File ApiKey used

        if (!$file):
            return $this->response->json([
                'status' => 'error',
                'message' => 'File/Volume not found with your apikey.'
            ]);
        endif;
        
        return null; // Checkup valid!
    }
}
