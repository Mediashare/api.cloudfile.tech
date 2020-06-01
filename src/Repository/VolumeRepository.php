<?php

namespace App\Repository;

use App\Entity\Volume;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Volume|null find($id, $lockMode = null, $lockVersion = null)
 * @method Volume|null findOneBy(array $criteria, array $orderBy = null)
 * @method Volume[]    findAll()
 * @method Volume[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VolumeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Volume::class);
    }

    /**
     * Check if ApiKey exist & if Volume associated.
     */
    public function authority(?string $apikey = null) {
        if (!$apikey):
            return [
                'status' => 'error',
                'message' => 'ApiKey not found in Header.'
            ];
        endif;
        $volume = $this->findOneBy(['apikey' => $apikey]);
        if (!$volume):
            return [
                'status' => 'error',
                'message' => 'Volume not found with your apikey.'
            ];
        endif;
        
        return null; // Checkup valid!
    }
}
