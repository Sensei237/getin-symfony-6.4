<?php

namespace App\Repository;

use App\Entity\Anonymat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Anonymat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Anonymat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Anonymat[]    findAll()
 * @method Anonymat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnonymatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anonymat::class);
    }

    // /**
    //  * @return Anonymat[] Returns an array of Anonymat objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Anonymat
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
