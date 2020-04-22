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

    public function getPublic(int $page) {
        $max_result = 100;
        $first_result = $max_result * ($page - 1);

        return $this->createQueryBuilder('f')
            ->andWhere('f.private = :private')
            ->setParameter('private', false)
            ->orderBy('f.createDate', 'DESC')
            ->setFirstResult($first_result)
            ->setMaxResults($max_result)
            ->getQuery()
            ->getResult();
    }

    public function getPrivate(int $page, string $apikey) {
        $max_result = 100;
        $first_result = $max_result * ($page - 1);

        return $this->createQueryBuilder('f')
            ->andWhere('f.apikey = :apikey')
            ->setParameter('apikey', $apikey)
            ->orderBy('f.createDate', 'DESC')
            ->setFirstResult($first_result)
            ->setMaxResults($max_result)
            ->getQuery()
            ->getResult();
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
