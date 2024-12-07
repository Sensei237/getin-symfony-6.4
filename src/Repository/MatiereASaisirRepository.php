<?php

namespace App\Repository;

use App\Entity\MatiereASaisir;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MatiereASaisir|null find($id, $lockMode = null, $lockVersion = null)
 * @method MatiereASaisir|null findOneBy(array $criteria, array $orderBy = null)
 * @method MatiereASaisir[]    findAll()
 * @method MatiereASaisir[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MatiereASaisirRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MatiereASaisir::class);
    }

    // /**
    //  * @return MatiereASaisir[] Returns an array of MatiereASaisir objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MatiereASaisir
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
