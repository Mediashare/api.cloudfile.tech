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
        parent::__construct($registry, File::class);
    }

    public function getPrivate(int $page, string $apiKey) {
        $max_result = 100;
        $first_result = $max_result * ($page - 1);

        $files = $this->createQueryBuilder('f')
            ->andWhere('f.apiKey = :apiKey')
            ->setParameter('apiKey', $apiKey)
            ->orderBy('f.createDate', 'DESC')
            ->setFirstResult($first_result)
            ->setMaxResults($max_result)
            ->getQuery()
            ->getResult();
        
        $counter = $this->createQueryBuilder('f')
            ->select('count(f.id)')
            ->andWhere('f.apiKey = :apiKey')
            ->setParameter('apiKey', $apiKey)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'files' => $files,
            'counter' => $counter
        ];
    }

    /*
    public function findOneBySomeField($value): ?File
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
