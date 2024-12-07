<?php

namespace App\Controller;

use App\Entity\EC;
use App\Entity\Classe;
use App\Entity\Examen;
use App\Entity\Module;
use App\Entity\Contrat;
use App\Entity\ECModule;
use App\Service\ExportUtils;
use App\Entity\AnneeAcademique;
use App\Form\CustomPVJuryType;
use App\Repository\ClasseRepository;
use App\Repository\ContratRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class PVController extends AbstractController
{
    private $link = 'note';
    private $contratRepository;
    private $classeRepository;
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(ClasseRepository $classeRepository, ContratRepository $contratRepository, EntityManagerInterface $entityManagerInterface)
    {
        $this->classeRepository = $classeRepository;
        $this->contratRepository = $contratRepository;
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @Route("/pv/{slug}", name="p_v")
     */
    public function index(AnneeAcademique $annee)
    {   
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->addFlash('info', "Sélectionner la classe dont vous souhaitez afficher le PV ! Allez sur le bouton action et cliquez sur afficher les PV");
        return $this->redirectToRoute('note_show_classes', ['slug' => $annee->getSlug()]);
    }

    /**
     * Dans cette action du controller, nous allons gerer l'exporation des pvs sous le format
     * excel et sous plusieurs forme.
     * 
     * @Route("/custom-pv/{slugAnnee}", name="pv_custom")
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     */
    public function customPVs(AnneeAcademique $annee, Request $request, ExportUtils $exportUtils)
    {   
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $customJuryPVForm = $this->createForm(CustomPVJuryType::class);
        $customJuryPVForm->handleRequest($request);

        if ($customJuryPVForm->isSubmitted() && $customJuryPVForm->isValid()) {
            // On traite le formulaire pour extraire les données soumises et générer 
            // le PV en fonction de ces données.
            $champsEc = $customJuryPVForm->get('ec')->getData();
            $champsModule = $customJuryPVForm->get('module')->getData();
            $champsSynthese = $customJuryPVForm->get('synthese')->getData();
            $semestre = $customJuryPVForm->get('semestre')->getData();
            $classesForm = $customJuryPVForm->get('classes')->getData();
            $classes = [];
            foreach ($classesForm as $key => $value) {
                $classes[] = $value;
            }
            $disposition = $customJuryPVForm->get('disposition')->getData();
            
            if (!empty($champsEc) && !empty($disposition)) {
                $result = $exportUtils->customExportPVsForJury($annee, $this->entityManagerInterface, $champsEc, $champsModule, $champsSynthese, $semestre, $classes, $disposition, $this->contratRepository);
                
                return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
            }
        }
        
        return $this->render('pv/pv/custom.html.twig', [
            'annee' => $annee,
            'classes' => $this->classeRepository->findAll(),
            'customJuryPVForm' => $customJuryPVForm->createView(),

        ]);
    }

    /**
     * @Route("/pv/show/{slugAnnee}/{slugClasse}/{page}/{code}", name="pv_show_pv_classe", requirements={"page"="\d+"}, defaults={"page"=1})
     * @Route("/pv/show/{slugAnnee}/{slugClasse}/{page}", name="pv_show_pv_definitif_classe", requirements={"page"="\d+"}, defaults={"page"=1})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     */
    public function showPVs(AnneeAcademique $annee, Classe $classe, int $page=1, ?Examen $examen=null, ContratRepository $contratRepository)
    { 
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $ecs = [];
        $data['exam'] = $examen;
        $data['classe'] = $classe;
        $data['annee'] = $annee;
        $pages = [];
        foreach ($this->entityManagerInterface->getRepository(EC::class)->findBy([], ['intitule'=>'ASC']) as $ec) {
            $contrats = $contratRepository->findContratsClasseForEC($annee, $ec, $classe);
            if ($contrats) {
                $ecs[] = $ec;
                $pages[] = $ec->getIntitule();
            }
        }
        
        if (!empty($ecs) && isset($ecs[$page - 1]) && !empty($ecs[$page - 1]) && $ecs[$page - 1] instanceof EC) {
            $ecPV = $ecs[$page - 1];
            $data['ec'] = $ecPV;
            $data['ecm'] = $this->entityManagerInterface->getRepository(ECModule::class)->findOneBy(['ec'=>$ecPV]);
            $data['currentPage'] = $page;
            $data['nbPages'] = count($ecs);
            $data['pages'] = $pages;
            
            $contrats = $contratRepository->findContratsClasseForEC($annee, $ecPV, $classe);
            if ($contrats) {
                $data['contrats'] = $contrats;
            }
            else {
                $this->addFlash('info', "Aucun contrat n'est lié à cette matière");
            }

        }else {
            throw $this->createNotFoundException("Aucune matiere trouvé !");
        }
        $data['li'] = $this->link;
        if (!isset($data['ec'])) {
            throw $this->createNotFoundException("Aucune matiere trouvé !");
        }

        return $this->render('pv/index.html.twig', $data);
    }
    
    /**
     * Dans cette fonction on va générer le téléchargement du fichier pdf contenenant 
     * les notes
     * On doit avoir la possibilite de telecharger tous les PVs de toutes les classes et de toutes les matieres
     * ou les PVs d'une classe sur toutes les matieres (sur une matiere ou sur un module)
     * 
     * @Route("/proces-verbaux/{format}/{slugAnnee}", name="pv_telecharger_all")
     * @Route("/proces-verbaux/{format}/{slugAnnee}/{slugClasse}/{slugExamen}", name="pv_telecharger_classe")
     * @Route("/proces-verbaux/{format}/{slugAnnee}/{slugClasse}", name="pv_telecharger_classe_finale")
     * @Route("/proces-verbaux/{format}/{slugAnnee}/{slugClasse}/{slugExamen}/{slugModule}", name="pv_telecharger_module")
     * @Route("/proces-verbaux/{format}/{slugAnnee}/{slugClasse}/{slugExamen}/{slugModule}/{idEcModule}", name="pv_telecharger_ec")
     * 
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     * @ParamConverter("examen", options={"mapping": {"slugExamen": "slug"}})
     * @ParamConverter("module", options={"mapping": {"slugModule": "slug"}})
     * @ParamConverter("ecModule", options={"mapping": {"idEcModule": "id"}})
     */
    public function exportPV(string $format='xls', AnneeAcademique $annee, ?Classe $classe=null, ?Examen $examen=null, ?Module $module=null, ?ECModule $ecModule=null, Request $request, ExportUtils $exportUtils)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $codeExam = trim(htmlspecialchars(strtoupper($request->get('exam'))));
        $semestre = $request->get('semestre');
        // $exam = $this->entityManagerInterface->getRepository(Examen::class)->findOneBy(['code'=>$codeExam]);
        // if (!$exam || !in_array($exam->getCode(), ['SR', 'SN'])) {
        //     die("CODE DE L'EXAMEN INCONNU");
        // }
        if (!in_array(mb_strtolower($format, 'UTF8'), ['xls', 'pdf'])) {
            $this->createNotFoundException("Format non reconnu");
        }
        $others = [
            'classe' => $classe,
            'module' => $module,
            'ecModule' => $ecModule,
            'examen' => $examen,
            'semestre' => $semestre,
        ];

        $matieres = [];// Cette variable est un tableau dont chaque case est une liste de ecModules
        
        if (mb_strtolower($format, 'UTF8') === 'pdf') {
            if ($ecModule) {
                if ($ecModule->getModule()->getAnneeAcademique()->getId() !== $annee->getId()) {
                    $this->createNotFoundException("La matière spécifiée ne correspond pas à l'année indiquée !");
                }
                $matieres[] = [$ecModule];
            }elseif ($module) {
                if ($module->getAnneeAcademique()->getId() !== $annee->getId()) {
                    $this->createNotFoundException("Le module spécifié ne correspond pas à l'année indiquée !");
                }
                $matieres[] = $module->getECModules();
            }elseif ($classe) {
                $modules = $this->entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
                foreach ($modules as $m) {
                    $matieres[] = $m->getECModules();
                }
            }else {
                $modules = $this->entityManagerInterface->getRepository(Module::class)->findBy(['anneeAcademique'=>$annee], ['classe'=>'ASC']);
                foreach ($modules as $m) {
                    $matieres[] = $m->getECModules();
                }
            }

            $matieres = $this->getContrats($matieres, $semestre);
        }
        else {
            $matieres[] = $classe;
        }
            
        if (empty($matieres)) {
            $this->createNotFoundException("Aucune matière trouvée !");
        }

        // $contrats = $this->getContrats($matieres, $semestre);
        $result = $exportUtils->doExportation($matieres, $annee, $format.'-pv', $others, $examen, $this->entityManagerInterface);

        return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
    }

    private function getContrats(Array $matieres, $semestre=null): Array
    {
        $contrats = [];

        foreach ($matieres as $matiere) {
            foreach ($matiere as $ecm) {
                if (!$semestre || ($semestre == $ecm->getSemestre())) {
                    $contrats[] = $this->contratRepository->findContratsByECModule($ecm);
                }
            }
        }

        return $contrats;
    }

}
