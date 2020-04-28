<?php

namespace App\Repository;

use App\Entity\Proxy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Proxy|null find($id, $lockMode = null, $lockVersion = null)
 * @method Proxy|null findOneBy(array $criteria, array $orderBy = null)
 * @method Proxy[]    findAll()
 * @method Proxy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProxyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Proxy::class);
    }

    // /**
    //  * @return Proxy[] Returns an array of Proxy objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Proxy
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
