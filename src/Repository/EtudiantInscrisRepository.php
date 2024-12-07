<?php

namespace App\Repository;

use App\Entity\Classe;
use App\Entity\Filiere;
use App\Entity\Etudiant;
use App\Entity\Formation;
use App\Entity\Specialite;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method EtudiantInscris|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtudiantInscris|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtudiantInscris[]    findAll()
 * @method EtudiantInscris[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method EtudiantInscris[]    search(AnneeAcademique $annee, string searchText)
 * @method EtudiantInscris[]    findEtudiants(AnneeAcademique $annee, $start=0, $maxResults=NULL, Formation $formation=NULL, Filiere $filiere=NULL, Specialite $specialite=NULL, Classe $classe=NULL, $status=NULL)
 * @method EtudiantInscris|null    findLastInscription(Etudiant $etudiant)
 */
class EtudiantInscrisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtudiantInscris::class);
    }

    public function findByNiveau(AnneeAcademique $annee, int $niveau)
    {
        return $this->createQueryBuilder('e')
            ->join('e.etudiant', 'et')
            ->join('e.classe', 'cl')
            ->andWhere('e.anneeAcademique = :annee')
            ->andWhere('cl.niveau = :niveau')
            ->setParameter('annee', $annee)
            ->setParameter('niveau', $niveau)
            ->setMaxResults(NULL)
            ->getQuery()
            ->getResult()
        ;
    }

    public function search(AnneeAcademique $annee, string $searchText)
    {
        return $this->createQueryBuilder('e')
            ->join('e.etudiant', 'et')
            ->andWhere('e.anneeAcademique = :annee')
            ->andWhere('et.nom LIKE :val')
            ->orWhere('et.matricule LIKE :val')
            ->setParameter('val', '%'.$searchText.'%')
            ->setParameter('annee', $annee)
            ->orderBy('et.nom', 'ASC')
            ->setMaxResults(NULL)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findEtudiants(AnneeAcademique $annee, $start=0, $maxResults=NULL, Formation $formation=NULL, Filiere $filiere=NULL, Specialite $specialite=NULL, Classe $classe=NULL, $status=NULL, $niveau=NULL)
    {
        $return = $this->createQueryBuilder('e')
            ->join('e.classe', 'c')
            ->join('c.specialite', 's')
            ->join('s.filiere', 'd')
            ->join('c.formation', 'f')
            ->join('e.etudiant', 'et');
        if ($formation) {
            $return->andWhere('c.formation = :formation')
                ->setParameter('formation', $formation);
        }
        if ($filiere) {
            $return->andWhere('s.filiere = :filiere')
                ->setParameter('filiere', $filiere);
        }
        if ($specialite) {
            $return->andWhere('c.specialite = :specialite')
                ->setParameter('specialite', $specialite);
        }
        if ($classe) {
            $return->andWhere('e.classe = :classe')
                ->setParameter('classe', $classe);
        }
        if ($niveau !== null) {
            $return->andWhere('c.niveau = :niveau')
                ->setParameter('niveau', $niveau);
        }
        switch ($status) {
            case 'add':
                $return->andWhere('e.isADD = :statut')
                    ->setParameter('statut', true);
                break;
            case 'adc':
                $return->andWhere('e.isADC = :statut')
                    ->setParameter('statut', true);
                break;
            case 'redouble':
                $return->andWhere('e.redouble = :statut')
                    ->setParameter('statut', true);
                break;
            case 'redoublants':
                $return->andWhere('e.isRedoublant = :statut')
                    ->setParameter('statut', true);
                break;
            case 'nouveaux':
                $return->andWhere('e.isRedoublant = :statut')
                    ->setParameter('statut', false);
                break;
        }

        return $return->andWhere('e.anneeAcademique = :annee')
            ->setParameter('annee', $annee)
            ->orderBy('et.nom', 'ASC')
            ->setMaxResults($maxResults)
            ->setFirstResult($start)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findLastInscription(Etudiant $etudiant): ?EtudiantInscris
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.etudiant = :etudiant')
            ->andWhere('e.id < :id')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('id', $etudiant->getId())
            ->orderBy('e.id', 'DESC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Fonction permettant de recuperer la liste des etudiant en fonction d'une specialite 
     * donnée.
     * @return EtudiantInscris[] Returns an array of EtudiantInscris objects
     */
    public function findBySpecialite(Specialite $specialite, $maxResults=null)
    {
        return $this->createQueryBuilder('e')
            ->join('e.classe', 'c')
            ->where('c.specialite = :specialite')
            ->setParameter('specialite', $specialite)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Fonction permettant de recuperer la liste des etudiant en fonction d'une filiere ou  
     * departement donné.
     * @return EtudiantInscris[] Returns an array of EtudiantInscris objects
     */
    public function findByFiliere(Filiere $filiere, $maxResults=null)
    {
        return $this->createQueryBuilder('e')
            ->join('e.classe', 'c')
            ->join('c.specialite', 's')
            ->where('s.filiere = :filiere')
            ->setParameter('filiere', $filiere)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Fonction permettant de recuperer la liste des etudiant en fonction d'une formation  
     * donnée.
     * @return EtudiantInscris[] Returns an array of EtudiantInscris objects
     */
    public function findByFormation(Formation $formation, $maxResults=null)
    {
        return $this->createQueryBuilder('e')
            ->join('e.classe', 'c')
            ->where('c.formation = :formation')
            ->setParameter('formation', $formation)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return EtudiantInscris[] Returns an array of EtudiantInscris objects
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
    public function findOneBySomeField($value): ?EtudiantInscris
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
