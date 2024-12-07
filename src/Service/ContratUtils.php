<?php
namespace App\Service;

use App\Entity\Classe;
use App\Entity\Module;
use App\Entity\Contrat;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use App\Repository\EtudiantInscrisRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Permet de gerer les contrats academiques des etudiants
 * @author : MBOZO'O METOU'OU Emmanuel Beranger Alias Sensei emmaberanger2@gmail.com
 */
class ContratUtils 
{
    
    public function genererLesContrats(AnneeAcademique $annee, EntityManagerInterface $entityManagerInterface)
    {
        $messages = '';

        $classes = $entityManagerInterface->getRepository(Classe::class)->findAll();
        foreach ($classes as $classe) {
            $modules = $entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
            if ($modules) {
                // La classe a deja un programme academique on verifie qu'il y a aussi des etudiants 
                $inscriptions = $entityManagerInterface->getRepository(EtudiantInscris::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
                if ($inscriptions) {
                    // La classe a un programme academique et elle a aussi des etudiants alors on cree le contrat de chaque etudiant.
                    foreach ($inscriptions as $inscris) {
                        $this->genererLeContrat($inscris, $modules, $entityManagerInterface);
                    }
                    $messages .= "Contrats des étudiants de la classe ".$classe->getCode()." ont été générés les dettes y compris!<br>";
                }else {
                    $messages .= "La classe ".$classe->getCode()." n'a aucun etudiant inscris pour l'année académique ".$annee->getDenomination()."<br>";
                }
            }else {
                $messages .= "La classe ".$classe->getCode()." n'a pas encore de programme academique pour l'année académique ".$annee->getDenomination()."<br>";
            }
        }
        $entityManagerInterface->flush();
        
        return $messages;
    }

    /**
     * Cette fonction genere le contrat de l'etudiant en fonction des modules specifier et
     * de son statut (redoublant, ADC, ADD). 
     * le parametre modules est un tableau de Module
     */
    public function genererLeContrat(EtudiantInscris $inscris, $modules, EntityManagerInterface $entityManagerInterface)
    {
        if ($inscris->getIsRedoublant()) {
            // Dans ce cas on va chercher dans ses contrats precedents les matieres qu'il n'a pas valider.
            $this->genererContratRedoublant($inscris, $modules, $entityManagerInterface);
        }
        else {
            // L'etudiant est nouveau dans la salle de classe.
            foreach ($modules as $module) {
                // On recupere la liste des ECs de ce module. 
                foreach ($module->getECModules() as $ecModule) {
                    // On verifie que cette ec n'est pas encore dans son contrat
                    if (!$inscris->contratExist($ecModule)) {
                        $contrat = new Contrat();
                        $contrat->setEtudiantInscris($inscris)
                                ->setEcModule($ecModule)
                                ->setIsDette(false)
                                ->setIsValidated(false)
                            ;
                        $entityManagerInterface->persist($contrat);
                    }
                }
            }
            if ($inscris->getIsADC()) {
                // L'etudiant a des matieres dans la classe ou les classes anterieures.
                $this->ajouterDette($inscris, $entityManagerInterface);
            }
        }
        
    }

    public function ajouterDette(EtudiantInscris $inscris, EntityManagerInterface $entityManagerInterface)
    {
        $repository = $entityManagerInterface->getRepository(EtudiantInscris::class);
        
        $lastInscription = $repository->findLastInscription($inscris->getEtudiant());
        if (!$lastInscription) {
            return;
        }
        // On recupere la liste de ses matieres non validées on les ajoute dans le nouveau contrat 
        foreach ($lastInscription->getContrats() as $c) {
            if (!$c->getIsValidated() && !$inscris->contratExist($c->getEcModule())) {
                $contrat = new Contrat();
                $contrat->setIsValidated(false)
                  ->setIsDette(true)
                  ->setContratPrecedent($c)
                  ->setEcModule($c->getEcModule())
                  ->setEtudiantInscris($inscris)
                ;
                $entityManagerInterface->persist($contrat);
            }
        }
    }

    /**
     * Cette fonction a besoins de savoir quel est le programme academique a utiliser pour la generation 
     * des contrats. Cette information doit etre prise dans la configuration du logiciel.
     */
    public function genererContratRedoublant(EtudiantInscris $inscris, $modules, EntityManagerInterface $entityManagerInterface)
    {
        $repository = $entityManagerInterface->getRepository(EtudiantInscris::class);
        
        $lastInscription = $repository->findLastInscription($inscris->getEtudiant());
        if (!$lastInscription) {
            // Ce cas sera valide beacoup plus pour les migrations
            //  Si on ne trouve pas sa derniere inscription alors on lui donne toutes les matieres de la classe.
            foreach ($modules as $module) {
                // On recupere la liste des ECs de ce module. 
                foreach ($module->getECModules() as $ecModule) {
                    // On verifie que cette ec n'est pas encore dans son contrat
                    if (!$inscris->contratExist($ecModule)) {
                        $contrat = new Contrat();
                        $contrat->setEtudiantInscris($inscris)
                                ->setEcModule($ecModule)
                                ->setIsDette(false)
                                ->setIsValidated(false)
                            ;
                        $entityManagerInterface->persist($contrat);
                    }
                }
            }
            return;
        }
        // La derniere inscription existe alors on ajoute uniquement les matieres qui sont restées non validées. 
        foreach ($lastInscription->getContrats() as $c) {
            if (!$c->getIsValidated() && !$inscris->contratExist($c->getEcModule())) {
                $contrat = new Contrat();
                $contrat->setIsValidated(false)
                  ->setIsDette(false)
                  ->setContratPrecedent($c)
                  ->setEcModule($c->getEcModule())
                  ->setEtudiantInscris($inscris)
                ;
                $entityManagerInterface->persist($contrat);
            }
        }
    }
}
