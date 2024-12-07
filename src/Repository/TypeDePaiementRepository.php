<?php

namespace App\Repository;

use App\Entity\TypeDePaiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TypeDePaiement|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeDePaiement|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeDePaiement[]    findAll()
 * @method TypeDePaiement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeDePaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeDePaiement::class);
    }

    // /**
    //  * @return TypeDePaiement[] Returns an array of TypeDePaiement objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TypeDePaiement
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
