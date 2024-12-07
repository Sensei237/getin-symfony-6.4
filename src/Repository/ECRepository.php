<?php

namespace App\Repository;

use App\Entity\EC;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method EC|null find($id, $lockMode = null, $lockVersion = null)
 * @method EC|null findOneBy(array $criteria, array $orderBy = null)
 * @method EC[]    findAll()
 * @method EC[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ECRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EC::class);
    }

    public function search(string $searchText)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.intitule LIKE :val')
            ->orWhere('e.code LIKE :val')
            ->setParameter('val', '%'.$searchText.'%')
            ->orderBy('e.intitule', 'ASC')
            ->setMaxResults(NULL)
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return EC[] Returns an array of EC objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EC
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
