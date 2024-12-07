<?php

namespace App\Repository;

use App\Entity\PaiementClasse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PaiementClasse|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaiementClasse|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaiementClasse[]    findAll()
 * @method PaiementClasse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaiementClasseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementClasse::class);
    }

    // /**
    //  * @return PaiementClasse[] Returns an array of PaiementClasse objects
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
    public function findOneBySomeField($value): ?PaiementClasse
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
