<?php

namespace App\Repository;

use App\Entity\SyntheseModulaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SyntheseModulaire|null find($id, $lockMode = null, $lockVersion = null)
 * @method SyntheseModulaire|null findOneBy(array $criteria, array $orderBy = null)
 * @method SyntheseModulaire[]    findAll()
 * @method SyntheseModulaire[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SyntheseModulaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SyntheseModulaire::class);
    }

    // /**
    //  * @return SyntheseModulaire[] Returns an array of SyntheseModulaire objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SyntheseModulaire
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
