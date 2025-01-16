<?php

namespace App\Repository;

use App\Entity\EC;
use App\Entity\Classe;
use App\Entity\Examen;
use App\Entity\Contrat;
use App\Entity\ECModule;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Contrat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Contrat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Contrat[]    findAll()
 * @method Contrat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Contrat[]    findContratsClasseForEC(AnneeAcademique $annee, EC $ec, Classe $classe, $maxResult=null, $start=null)
 * @method Contrat|null findContratPrecedent(ECModule $ecm, EtudiantInscris $ei)
 */
class ContratRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contrat::class);
    }

    public function findContratsForECWidthAnonymats(AnneeAcademique $annee, EC $ec, Examen $ex)
    {
        return $this->createQueryBuilder('c')
            ->join('c.etudiantInscris', 'ei')
            ->join('c.anonymats', 'an')
            ->join('an.examen', 'ex')
            ->join('ei.etudiant', 'et')
            ->join('ei.anneeAcademique', 'aa')
            ->join('c.ecModule', 'ecm')
            ->join('ecm.ec', 'ec')
            ->andWhere('ex.id = :ex_id')
            ->andWhere('aa.id = :annee_id')
            ->andWhere('ec.id = :ec_id')
            ->setParameter('annee_id', $annee->getId())
            ->setParameter('ec_id', $ec->getId())
            ->setParameter('ex_id', $ex->getId())
            ->orderBy('an.anonymat', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findContratsForEC(AnneeAcademique $annee, EC $ec, $maxResult=null, $start=null)
    {
        return $this->createQueryBuilder('c')
            ->join('c.etudiantInscris', 'ei')
            ->join('ei.etudiant', 'et')
            ->join('ei.anneeAcademique', 'aa')
            ->join('c.ecModule', 'ecm')
            ->join('ecm.ec', 'ec')
            ->andWhere('aa.id = :annee_id')
            ->andWhere('ec.id = :ec_id')
            ->setParameter('annee_id', $annee->getId())
            ->setParameter('ec_id', $ec->getId())
            ->orderBy('et.nom', 'ASC')
            ->setMaxResults($maxResult)
            ->setFirstResult($start)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Cette fonction return la liste de tous les contrats qui sont associés à une matière 
     * dans une spécialité. Les dettes y compris
     */
    public function findContratsClasseForEC(AnneeAcademique $annee, EC $ec, Classe $classe, $maxResult=null, $start=null)
    {
        return $this->createQueryBuilder('c')
            ->join('c.etudiantInscris', 'ei')
            ->join('ei.etudiant', 'et')
            ->join('c.ecModule', 'ecm')
            ->join('ei.classe', 'cl')
            ->andWhere('ei.anneeAcademique = :annee')
            ->andWhere('ecm.ec = :ec')
            ->andWhere('cl.niveau >= :niveau')
            ->andWhere('cl.specialite = :specialite')
            ->andWhere('cl.formation = :formation')
            ->setParameter('annee', $annee)
            ->setParameter('ec', $ec)
            ->setParameter('niveau', $classe->getNiveau())
            ->setParameter('specialite', $classe->getSpecialite())
            ->setParameter('formation', $classe->getFormation())
            ->orderBy('et.nom', 'ASC')
            ->setMaxResults($maxResult)
            ->setFirstResult($start)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Cette fonction permet de recuperer tous les contrats academiques d'une classe pour une année donnée
     */
    public function findContratsClasse(AnneeAcademique $annee, Classe $classe, $maxResult=null, $start=null)
    {
        return $this->createQueryBuilder('c')
            ->join('c.etudiantInscris', 'ei')
            ->join('ei.etudiant', 'et')
            ->join('c.ecModule', 'ecm')
            ->join('ei.classe', 'cl')
            ->andWhere('ei.anneeAcademique = :annee')
            ->andWhere(' ei.classe = :classe')
            ->setParameter('annee', $annee)
            ->setParameter('classe', $classe)
            ->orderBy('et.nom', 'ASC')
            ->setMaxResults($maxResult)
            ->setFirstResult($start)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findContratsClasseForECWidthAnonymats(AnneeAcademique $annee, EC $ec, Classe $classe, Examen $ex, $maxResult=null, $start=null)
    {
        return $this->createQueryBuilder('c')
            ->join('c.etudiantInscris', 'ei')
            ->join('c.anonymats', 'an')
            ->join('an.examen', 'ex')
            ->join('ei.etudiant', 'et')
            ->join('c.ecModule', 'ecm')
            ->join('ei.classe', 'cl')
            ->andWhere('ei.anneeAcademique = :annee')
            ->andWhere('ecm.ec = :ec')
            ->andWhere('cl.niveau >= :niveau')
            ->andWhere('cl.specialite = :specialite')
            ->andWhere('cl.formation = :formation')
            ->andWhere('ex.id = :ex_id')
            ->setParameter('annee', $annee)
            ->setParameter('ec', $ec)
            ->setParameter('niveau', $classe->getNiveau())
            ->setParameter('specialite', $classe->getSpecialite())
            ->setParameter('formation', $classe->getFormation())
            ->setParameter('ex_id', $ex->getId())
            ->orderBy('an.anonymat', 'ASC')
            ->setMaxResults($maxResult)
            ->setFirstResult($start)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findContratPrecedent(ECModule $ecm, EtudiantInscris $ei)
    {
        return $this->createQueryBuilder('c')
            ->join('c.etudiantInscris', 'ei')
            ->join('ei.etudiant', 'et')
            ->join('ei.anneeAcademique', 'aa')
            ->andWhere('et.id = :etudiant')
            ->andWhere('aa.id < :annee')
            ->andWhere('c.ecModule = :ecModule')
            ->andWhere('c.isValidated = :isValidated')
            ->setParameter('etudiant', $ei->getEtudiant()->getId())
            ->setParameter('annee', $ei->getAnneeAcademique()->getId())
            ->setParameter('ecModule', $ecm)
            ->setParameter('isValidated', false)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findContratsByECModule(ECModule $ecm)
    {
        return $this->createQueryBuilder('c')
            ->join('c.etudiantInscris', 'ei')
            ->join('ei.etudiant', 'et')
            ->join('c.ecModule', 'ecm')
            ->andWhere('c.ecModule = :ecm')
            ->setParameter('ecm', $ecm)
            ->orderBy('et.nom', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findContratsClasseByECModule(AnneeAcademique $annee, ECModule $ecm, Classe $classe, $status=true)
    {
        return $this->createQueryBuilder('c')
            ->join('c.etudiantInscris', 'ei')
            ->join('ei.etudiant', 'et')
            ->join('c.ecModule', 'ecm')
            ->join('ecm.module', 'mod')
            ->andWhere('c.ecModule = :ecm')
            ->andWhere('mod.classe = :classe')
            ->andWhere('ei.anneeAcademique = :annee')
            ->andWhere('c.isValidated = :status')
            ->setParameter('ecm', $ecm)
            ->setParameter('classe', $classe)
            ->setParameter('annee', $annee)
            ->setParameter('status', $status)
            ->orderBy('et.nom', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Cette fonction permet de recuperer la liste des contrats d'un etudiant donnee en les ordonant
     * par module et en evitant les contrats qui sont consideres comme dette.
     * On utilise cette fonction pour generer le releve de note de l'etudiant
     */
    public function orderContratsByModule(EtudiantInscris $inscris)
    {
        return $this->createQueryBuilder('c')
            ->join('c.ecModule', 'ecm')
            ->join('ecm.module', 'mod')
            ->andWhere('c.etudiantInscris = :ins')
            ->andWhere('mod.classe = :classe')
            ->setParameter('ins', $inscris)
            ->setParameter('classe', $inscris->getClasse())
            ->orderBy('mod.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Contrat[] Returns an array of Contrat objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Contrat
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
