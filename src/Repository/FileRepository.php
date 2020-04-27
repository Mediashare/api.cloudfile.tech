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

    public function pagination(string $apiKey, int $page, ?int $max = 100) {
        $first_result = $max * ($page - 1);
        $files = $this->createQueryBuilder('f')
            ->andWhere('f.apiKey = :apiKey')
            ->setParameter('apiKey', $apiKey)
            ->orderBy('f.createDate', 'DESC')
            ->setFirstResult($first_result)
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();

        return $files;
    }
}
