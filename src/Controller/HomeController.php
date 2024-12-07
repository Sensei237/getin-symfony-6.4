<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Region;
use App\Entity\Employe;
use App\Entity\Filiere;
use App\Entity\ECModule;
use App\Entity\Formation;
use App\Entity\Specialite;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class HomeController extends AbstractController
{
    private $li = 'home';
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }
    
    /**
     * @Route("/dashboard", name="home")
     */
    public function index(Request $request)
    {
        // dump(new \DateTime('1999-01-01'));die();
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $annee = $request->getSession()->get('annee');
        if (!$annee) {
            $annee = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findOneBy(['isArchived'=>false]);
            if (!$annee) {
                die("Vous devez activer une annee academique");
            }
            $request->getSession()->set('annee', $annee);
        }

        $effectifTotal = count($this->entityManagerInterface->getRepository(EtudiantInscris::class)->findBy(['anneeAcademique'=>$annee]));

        $statsNiveau = [];
        if ($effectifTotal > 0) {
            for ($i=1; $i < 6; $i++) {
                $statsNiveau[] = [
                    'niveau' => 'niveau '.$i,
                    'effectif' => number_format((count($this->entityManagerInterface->getRepository(EtudiantInscris::class)->findByNiveau($annee, $i)))*100/$effectifTotal, 2),
                ];
            }
        }

        return $this->render('home/index.html.twig', [
            'li' => $this->li,
            'classes' => $this->entityManagerInterface->getRepository(Classe::class)->findAll(),
            'etudiants' => $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findBy(['anneeAcademique'=>$annee]),
            'specialites' => $this->entityManagerInterface->getRepository(Specialite::class)->findAll(),
            'employes' => $this->entityManagerInterface->getRepository(Employe::class)->findBy(['isVisible'=>true, 'isGone'=>false]),
            'statsNiveaux' => $statsNiveau,
            'annee' => $annee,
            'effectifTotal' => $effectifTotal,


        ]);
    }

    /**
     * @Route("/fetch-departements", name="home_fetchDepartementUrl")
     * @Route("/fetch-departements/{id}", name="home_fetchDepartementUrlParams")
     */
    public function fetchDepartement(Region $region) 
    {
        return $this->render('home/departement.html.twig', ['region'=>$region]);
    }

    /**
     * @Route("/fetch-years", name="home_fetch_years")
     */
    public function fetchYears(Request $request)
    {
        if (!$request->getSession()->get('annee')) {
            return new Response();
        }
        $years = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findBy([], ['denomination'=>'DESC']);
        $result = "<option value=''>Sélectionner l'année</option>";
        foreach ($years as $y) {
            $selected = $y->getId() == $request->getSession()->get('annee')->getId() ? 'selected' : '';
            $option = "<option value='".$y->getSlug()."' ".$selected.">".$y->getDenomination()."</option>";
            $result .= $option;
        }

        return new Response($result);
    }

    /**
     * @Route("/change/years", name="switch_year")
     * @Route("/change/years/{slug}", name="switch_year_param")
     */
    public function switchYear(AnneeAcademique $annee, Request $request)
    {
        $request->getSession()->set('annee', $annee);
        
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/find-options-filiere-id", name="home_find_optionsaid")
     * @Route("/find-options-filiere-id/{id}", name="home_find_optionsbid")
     */
    public function getSpecialitesID(Filiere $filiere)
    {
        $html = '<option value="">Sélectionner la spécialités</option>';
        foreach ($this->entityManagerInterface->getRepository(Specialite::class)->findBy(['filiere'=>$filiere], ['name'=>'ASC']) as $s) {
            $html .= "<option value='".$s->getId()."'>".$s->getName()."</option>";
        }

        return new Response($html);
    }

    /**
     * @Route("/find-options-filiere/{slug}", name="home_find_options")
     * @Route("/find-options-filiere", name="home_find_optionsb")
     */
    public function getSpecialitesSlug(Filiere $filiere)
    {
        $html = '<option value="">Sélectionner la spécialités</option>';
        foreach ($this->entityManagerInterface->getRepository(Specialite::class)->findBy(['filiere'=>$filiere], ['name'=>'ASC']) as $s) {
            $html .= "<option value='".$s->getSlug()."'>".$s->getName()."</option>";
        }

        return new Response($html);
    }

    /**
     * @Route("/find-classes-option/{slug}", name="home_find_classes")
     * @Route("/find-classes-option", name="home_find_classesb")
     */
    public function getClassesSlug(Specialite $specialite)
    {
        $html = '<option value="">Sélectionner la spécialités</option>';
        foreach ($this->entityManagerInterface->getRepository(Classe::class)->findBy(['specialite'=>$specialite], ['nom'=>'ASC']) as $c) {
            $html .= "<option value='".$c->getSlug()."'>".$c->getNom()."</option>";
        }

        return new Response($html);
    }

    /**
     * @Route("/find-classes-option-id", name="home_find_classesaid")
     * @Route("/find-classes-option-id/{id}", name="home_find_classessid")
     */
    public function getClassesID(Specialite $specialite)
    {
        $html = '<option value="">Sélectionner la spécialités</option>';
        foreach ($this->entityManagerInterface->getRepository(Classe::class)->findBy(['specialite'=>$specialite], ['nom'=>'ASC']) as $c) {
            $html .= "<option value='".$c->getId()."'>".$c->getNom()."</option>";
        }

        return new Response($html);
    }

    /**
     * @Route("/find-matieres-classe", name="home_find_matierea")
     * @Route("/find-matieres-classe/{slug}", name="home_find_matiereb")
     */
    public function getECModuleClasse(Classe $classe, Request $request)
    {
        $html = '';
        foreach ($this->entityManagerInterface->getRepository(ECModule::class)->findActualECModulesByClasse($request->getSession()->get('annee'), $classe) as $ecm) {
            $html .= "<option value='".$ecm->getId()."'>".$ecm->getNom()."</option>";
        }

        return new Response($html);
    }

    /**
     * @Route("/find-matieres-classe-id", name="home_find_matiere_id_a")
     * @Route("/find-matieres-classe-id/{id}", name="home_find_matiere_id_a")
     */
    public function getECModuleClasseID(Classe $classe, Request $request)
    {
        $html = '';
        foreach ($this->entityManagerInterface->getRepository(ECModule::class)->findActualECModulesByClasse($request->getSession()->get('annee'), $classe) as $ecm) {
            $html .= "<option value='".$ecm->getId()."'>".$ecm->getNom()."</option>";
        }

        return new Response($html);
    }

    /**
     * @Route("/find-etudiants-classe/{anneeSlug}/{slugFiliere}/{slugSpecialite}/{slugClasse}", name="home_find_ets")
     * @Route("/find-etudiants-classe/{anneeSlug}/{slugFiliere}/{slugSpecialite}", name="home_find_ets_spe")
     * @Route("/find-etudiants-classe/{anneeSlug}/{slugFiliere}", name="home_find_ets_fil")
     * @Route("/find-etudiants-classe/{anneeSlug}", name="home_find_etsb")
     * 
     * @ParamConverter("annee", options={"mapping": {"anneeSlug": "slug"}})
     * @ParamConverter("filiere", options={"mapping": {"slugFiliere": "slug"}})
     * @ParamConverter("specialite", options={"mapping": {"slugSpecialite": "slug"}})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function getStudent(AnneeAcademique $annee, Filiere $filiere, ?Specialite $specialite=null, ?Classe $classe=null, Request $request)
    {
        $html = '<option value="">Sélectionner les etudiants</option>';
        $formation = $this->entityManagerInterface->getRepository(Formation::class)->findOneBy(['id'=>$request->get('formation')]);

        $niveau = $request->get('niveau');
        $hasEts = false;
        foreach ($this->entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, NULL, $formation, $filiere, $specialite, $classe) as $e) {
            if (!$niveau || $niveau == $e->getClasse()->getNiveau()) {
                $hasEts = true;
                $html .= "<option value='".$e->getId()."'>".$e->getEtudiant()->getMatricule()." | ".$e->getEtudiant()->getNomComplet()."</option>";
            }
        }

        $emcsHtml = '';
        $hasEmcs = false;
        if ($classe) {
            $ecms = $this->entityManagerInterface->getRepository(ECModule::class)->findECModulesByClasse($classe);
            if ($ecms) {
                $hasEmcs = true;
                foreach ($ecms as $ecm) {
                    $emcsHtml .= "<option value='".$ecm->getId()."'>".$ecm->getEc()->getIntitule().' => '.$ecm->getEc()->getCode()."</option>";
                }
            }
        }

        return new Response(json_encode(['etudiants'=>$html, 'hasEts'=>$hasEts, 'hasEcms'=>$hasEmcs, 'ecms'=>$emcsHtml]));
    }

    /**
     * @Route("/home-fetch-graph-data/{id}", name="home_fetch_data_graph")
     */
    public function getEffectifsParClasse(AnneeAcademique $annee, Request $request)
    {
        $effectifs = [];
        $labels = [];
        $hasData = false;
        if ($annee) {
            foreach ($this->entityManagerInterface->getRepository(Classe::class)->findBy([], ['niveau'=>'ASC', 'nom'=>'ASC']) as $classe) {
                $effectifs[] = count($this->entityManagerInterface->getRepository(EtudiantInscris::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]));
                $labels[] = $classe->getCode();
                $hasData = true;
            }
        }
        
        return new Response(json_encode([
            'effectifs' => $effectifs,
            'labels' => $labels,
            'hasData' => $hasData,
        ]));
    }

    /**
     * @Route("/home-data-graph-2", name="home_fech_data_graph2")
     */
    public function fetchEffectifsAnnees()
    {
        $annees = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findBy([], ['denomination'=>'DESC', 'id'=>'DESC'], 5);
        $classes = $this->entityManagerInterface->getRepository(Classe::class)->findBy([], ['niveau'=>'ASC', 'nom'=>'ASC']);
        $labels = [];
        $dataSets = [];
        $hasData = false;

        $colorPicker = [
            'rgba(0, 0, 255, 0.9)',
            'rgba(255, 0, 0, 0.9)',
            'rgba(0, 255, 0, 0.9)',
            'rgba(60, 141, 188, 0.9)',
            'rgba(0, 0, 0, 0.9)',
        ];
        $backgroundColorPicker = [
            'rgba(0, 0, 255, 0.8)',
            'rgba(255, 0, 0, 0.8)',
            'rgba(0, 255, 0, 0.8)',
            'rgba(60, 141, 188, 0.8)',
            'rgba(0, 0, 0, 0.8)',
        ];

        foreach ($classes as $classe) {
            $labels[] = $classe->getCode();
        }
        
        $i=0;
        foreach ($annees as $annee) {
            $data = [];
            foreach ($classes as $classe) {
                $hasData = true;
                $data[] = count($this->entityManagerInterface->getRepository(EtudiantInscris::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]));
            }

            $dataSets[] = [
                'label' => $annee->getDenominationSlash(),
                'backgroundColor' => $backgroundColorPicker[$i],
                'borderColor' => $colorPicker[$i],
                'pointRadius'=>true,
                'pointColor' => $colorPicker[$i],
                'pointStrokeColor' => $colorPicker[$i],
                'pointHighlightFill' => $colorPicker[$i],
                'pointHighlightStroke' => $colorPicker[$i],
                'data' => $data,
            ];
            $i++;
        }

        return new Response(json_encode(['dataSets'=>$dataSets, 'hasData'=>$hasData, 'labels'=>$labels]));
    }
}
