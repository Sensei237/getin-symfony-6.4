<?php
namespace App\Service;

use App\Entity\Etudiant;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Contient des fonctions permet de manager les etudiants
 * @author : MBOZO'O METOU'OU Emmanuel Beranger Alias Sensei emmaberanger2@gmail.com
 */
class EtudiantUtils
{
    public function genererMatricule(AnneeAcademique $annee, EtudiantInscris $inscription, EntityManagerInterface $doctrine, $codeEcole='N')
    {
        $y = explode('-', $annee->getSlug())[0];
        $matricule = $y[2].$y[3];
        $matricule .= $codeEcole;
        $matricule .= $inscription->getClasse()->getSpecialite()->getLetterMatricule();
        $existe = true;
        while ($existe) {
            $numeroInscription = count($doctrine->getRepository(EtudiantInscris::class)->findBy(['anneeAcademique'=>$annee, 'classe'=>$inscription->getClasse()]))+1;
            if ($numeroInscription < 10) {
                $numeroInscription = '00'.$numeroInscription;
            }elseif ($numeroInscription < 100) {
                $numeroInscription = '0'.$numeroInscription;
            }
            $matricule .= $numeroInscription.$inscription->getClasse()->getSpecialite()->getFiliere()->getLettrePourLeMatricule();
            if (!$doctrine->getRepository(Etudiant::class)->findOneBy(['matricule'=>$matricule])) {
                $existe = false;
            }
        }
        $inscription->getEtudiant()->setMatricule(strtoupper($matricule));
    }
}
