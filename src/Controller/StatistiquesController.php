<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Module;
use App\Entity\Contrat;
use App\Entity\ECModule;
use App\Service\ExportUtils;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use App\Repository\ClasseRepository;
use App\Repository\ContratRepository;
use App\Repository\ECModuleRepository;
use App\Repository\EtudiantInscrisRepository;
use App\Repository\ModuleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class StatistiquesController extends AbstractController
{
    private $link = 'stats';
    
    /**
     * @Route("/statistiques/{slugAnnee}", name="statistiques")
     * @Route("/statistiques/{slugAnnee}/{slugClasse}", name="statistiques_classe")
     * 
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     */
    public function index(AnneeAcademique $annee, ?Classe $classe=null,
        ClasseRepository $classeRepository, Request $request, ExportUtils $eu,
        EtudiantInscrisRepository $etudiantInscrisRepository,
        ModuleRepository $moduleRepository,
        ECModuleRepository $eCModuleRepository,
        ContratRepository $contratRepository
    )
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $classes = $classeRepository->findBy([], ['niveau'=>'ASC', 'formation'=>'ASC', 'specialite'=>'ASC']);
        
        if ($request->get('download')) {
            if ($classe) {
                $classes2 = [$classe];
            }else {
                $classes2 = $classes;
            }
            // dump($this->getStatsPDF($annee, $classes2));die();

            $result = $eu->doExportation($this->getStatsPDF($annee, $classes2, $eu, $etudiantInscrisRepository, $moduleRepository, $eCModuleRepository, $contratRepository), $annee, 'pdf-stats');
            
            return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
            
        }
        
        $data = [];
        if (!$classe) {
            if ($classes) {
                $classe = $classes[0];
            }else {
                throw $this->createNotFoundException("Aucune classe pour les stats");
            }
        }
        $data = $this->getStatsClasse($annee, $classe, $eu, $etudiantInscrisRepository, $moduleRepository, $eCModuleRepository, $contratRepository);
        // dump($this->getStatsClasse($annee, $classe));die();
        return $this->render('statistiques/index.html.twig', [
            'li' => $this->link,
            'data' => $data,
            'annee' => $annee, 
            'classe' => $classe,
            'classes' => $classes
        ]);
    }
    
    private function getStatsPDF(AnneeAcademique $annee, 
        Array $classes, 
        ExportUtils $eu,
        EtudiantInscrisRepository $etudiantInscrisRepository,
        ModuleRepository $moduleRepository,
        ECModuleRepository $eCModuleRepository,
        ContratRepository $contratRepository
    )
    {
        $data = [];
        foreach ($classes as $classe) {
            $data[] = [
                'classe' => $classe,
                'statsClasse' => $this->getStatsClasse($annee, $classe, $eu, $etudiantInscrisRepository, $moduleRepository, $eCModuleRepository, $contratRepository),
            ];
        }
        if (empty($data)) {
            $this->createNotFoundException("Aucune donnée trouvée !");
        }
        
        return $data;
    }

    private function getStatsClasse(AnneeAcademique $annee, 
        Classe $classe, ExportUtils $eu,
        EtudiantInscrisRepository $etudiantInscrisRepository,
        ModuleRepository $moduleRepository,
        ECModuleRepository $eCModuleRepository,
        ContratRepository $contratRepository
        )
    {
        $nbreGarcons = $nbreFilles = 0;
        $etudiants = $etudiantInscrisRepository->findEtudiants($annee, 0, NULL, NULL, NULL, NULL, $classe);
        $nbreEtudiants = count($etudiants);
        
        foreach ($etudiants as $inscris) {
            if (strtoupper($inscris->getEtudiant()->getSexe()) == 'MASCULIN' || strtoupper($inscris->getEtudiant()->getSexe()) == 'M') {
                $nbreGarcons++;
            }else {
                $nbreFilles++;
            }
        }

        $modules = $moduleRepository->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
        $data = [];
        foreach ($modules as $module) {
            $moduleData = $this->getStatsModule($module, $classe, $annee, $nbreGarcons, $nbreFilles, $nbreEtudiants, $eu, $eCModuleRepository, $contratRepository);
            if (!empty($moduleData)) {
                $data['modules'][] = [
                    'module' => $module,
                    'stats' => $moduleData,
                ];
            }
        }
        $data['effectifs'] = [
            'effectif' => $nbreEtudiants,
            'nbreGarcons' => $nbreGarcons,
            'nbreFilles' => $nbreFilles,
        ];

        return $data;
    }

    private function getStatsModule(Module $module, Classe $classe, 
    AnneeAcademique $annee, int $nbreGarcons, int $nbreFilles, 
    int $nbreEtudiants, ExportUtils $eu,
    ECModuleRepository $eCModuleRepository,
    ContratRepository $contratRepository)
    {
        $ecsModule = $eCModuleRepository->findBy(['module'=>$module], ['semestre'=>'ASC']);
        if ($nbreEtudiants <= 0) {
            return null;
        }
        $lignes = [];
        foreach ($ecsModule as $ecm) {
            $contrats = $contratRepository->findContratsClasseForEC($annee, $ecm->getEc(), $classe);
            $nbGV = 0; // Nombre de garcons ayant valide
            $nbFV = 0; // Nombre de filles ayant valide
            $nbEV = 0; // Nombre d'etudiants ayant valide
            foreach ($contrats as $contrat) {
                if ($contrat->getIsValidated()) {
                    if (strtoupper($contrat->getEtudiantInscris()->getEtudiant()->getSexe()) == 'MASCULIN' || strtoupper($contrat->getEtudiantInscris()->getEtudiant()->getSexe()) == 'M') {
                        $nbGV++;
                    }else {
                        $nbFV++;
                    }
                    $nbEV++;
                }
            }

            $statsGarcons = ' - ';
            $statsFilles = ' - ';
            $statsTotal = ' - ';
            if ($nbreEtudiants > 0) {
                $pourcentageEtudiants = number_format(($nbEV*100)/$nbreEtudiants, 2);
                $statsTotal = $nbEV.' sur '.$nbreEtudiants.' <br>soit '.$pourcentageEtudiants.'%';
                if ($nbreGarcons > 0) {
                    $pourcentageGarcons = number_format(($nbGV*100)/$nbreGarcons, 2);
                    $statsGarcons = $nbGV.' sur '.$nbreGarcons.' <br>soit '.$pourcentageGarcons.'%';
                }
                if ($nbreFilles > 0) {
                    $pourcentageFilles = number_format(($nbFV*100)/$nbreFilles, 2);
                    $statsFilles = $nbFV.' sur '.$nbreFilles.' <br>soit '.$pourcentageFilles.'%';
                }
            }

            $lignes[] = [
                'intituleEC' => $ecm->getEc()->getIntitule(),
                'codeEC' => $ecm->getEc()->getCode(),
                'statsGarcons' => $statsGarcons,
                'statsFilles' => $statsFilles,
                'statsTotal' => $statsTotal,
                'moyGenClasse' => $eu->getMoyenneGenerale($contrats),
            ];
        }

        return $lignes;
    }

    
}
