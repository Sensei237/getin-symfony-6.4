<?php

namespace App\Repository;

use App\Entity\EC;
use App\Entity\Classe;
use App\Entity\ECModule;
use App\Entity\AnneeAcademique;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method ECModule|null find($id, $lockMode = null, $lockVersion = null)
 * @method ECModule|null findOneBy(array $criteria, array $orderBy = null)
 * @method ECModule[]    findAll()
 * @method ECModule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method ECModule[] findECModulesByClasse(Classe $classe)
 * @method ECModule[] findClassesEC(EC $ec, AnneeAcademique $annee)
 */
class ECModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ECModule::class);
    }

    public function findECModulesByClasse(Classe $classe)
    {
        return $this->createQueryBuilder('e')
            ->join('e.module', 'm')
            ->join('m.classe', 'c')
            ->join('c.specialite', 's')
            ->andWhere('c.niveau <= :niveau')
            ->setParameter('niveau', $classe->getNiveau())
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findActualECModulesByClasse(AnneeAcademique $annee, Classe $classe)
    {
        return $this->createQueryBuilder('e')
            ->join('e.module', 'm')
            ->join('m.classe', 'c')
            ->join('c.specialite', 's')
            ->andWhere('m.classe = :classe')
            ->andWhere('m.anneeAcademique = :annee')
            ->setParameter('classe', $classe)
            ->setParameter('annee', $annee)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findECModuleByYearAndClasseAndCodeEC(AnneeAcademique $annee, Classe $classe, String $codeEC): ?ECModule
    {
        return $this->createQueryBuilder('e')
            ->join('e.module', 'm')
            ->join('m.classe', 'c')
            ->join('c.specialite', 's')
            ->join('e.ec', 'ec')
            ->andWhere('m.classe = :classe')
            ->andWhere('m.anneeAcademique = :annee')
            ->andWhere('ec.code = :codeEC')
            ->setParameter('classe', $classe)
            ->setParameter('annee', $annee)
            ->setParameter('codeEC', $codeEC)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    public function findActualECModulesByYear(AnneeAcademique $annee)
    {
        return $this->createQueryBuilder('e')
            ->join('e.module', 'm')
            ->join('m.classe', 'c')
            ->join('c.specialite', 's')
            ->andWhere('m.anneeAcademique = :annee')
            ->setParameter('annee', $annee)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Permet de rechercher les salles de classes qui font un ec spécifique. 
     */
    public function findClassesEC(EC $ec, AnneeAcademique $annee)
    {
        $ecms = $this->createQueryBuilder('ecm')
                        ->join('ecm.module', 'm')
                        ->join('m.classe', 'c')
                        ->andWhere('m.anneeAcademique = :annee')
                        ->andWhere('ecm.ec = :ec')
                        ->setParameter('annee', $annee)
                        ->setParameter('ec', $ec)
                        ->orderBy('m.id', 'ASC')
                        ->getQuery()
                        ->getResult();
        // on veux uniquement les classes;
        $classes = new ArrayCollection();
        foreach ($ecms as $ecm) {
            if (!$classes->contains($ecm->getModule()->getClasse())) {
                $classes[] = $ecm->getModule()->getClasse();
            }
        }
        return $classes;
    }

    public function findOneByYearClasseAndEc(AnneeAcademique $annee, Classe $classe, EC $ec): ?ECModule
    {
        return $this->createQueryBuilder('e')
            ->join('e.module', 'm')
            ->join('e.ec', 'ec')
            ->andWhere('m.classe = :classe')
            ->andWhere('m.anneeAcademique = :annee')
            ->andWhere('e.ec = :ec')
            ->setParameter('classe', $classe)
            ->setParameter('annee', $annee)
            ->setParameter('ec', $ec)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // /**
    //  * @return ECModule[] Returns an array of ECModule objects
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
    public function findOneBySomeField($value): ?ECModule
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
