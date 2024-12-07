<?php

namespace App\Controller;

use App\Entity\EC;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Classe;

use App\Entity\Examen;
use App\Entity\Module;
use App\Entity\Contrat;
use App\Entity\ECModule;

use App\Service\ImportUtils;
use App\Entity\AnneeAcademique;
use App\Form\UploadProgramType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;


/**
 * Un programme academique c'est l'ensemble des UEs (modules) d'une classe.
 * Un module contient des ECs (matieres). Le but de ce controller sera donc de gerer 
 * les programmes academiques des differentes classes. 
 */
class ProgrammeAcademiqueController extends AbstractController
{
    private $link = 'pa';
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * Permet d'afficher la liste des salles de classe qui existent
     * @Route("/programme-academique/{slug}", name="programme_academique")
     */
    public function index(AnneeAcademique $annee)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $classesHasProgram = [];
        foreach ($this->entityManagerInterface->getRepository(Classe::class)->findAll() as $classe) {
            if ($this->entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee])) {
                $classesHasProgram[] = $classe;
            }
        }


        return $this->render('programme_academique/index.html.twig', [
            'annee' => $annee,
            'classes' => $classesHasProgram,
            'li' => $this->link,
        ]);
    }

    /**
     * Cette methode affiche le programme academique d'une classe donnee pour une annee donnee
     * @Route("/programme-academique/{annee_slug}/{slug}", name="PA_classe")
     */
    public function afficherProgrammeClasse(string $annee_slug, Classe $classe)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $annee = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findOneBy(['slug'=>$annee_slug]);
        if (!$annee) {
            throw $this->createNotFoundException('Page not found');
        }
        $modules = $this->entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
        if (!$modules) {
            $this->addFlash('info', $classe->getNom()." n'a pas encore de programme academique pour l'année academique ".$annee->getDenomination());
            return $this->redirectToRoute('creer_programme', ['annee_slug'=>$annee->getSlug(), 'slug'=>$classe->getSlug()]);
        }
        return $this->render('programme_academique/afficher.html.twig', [
            'annee' => $annee,
            'classe' => $classe,
            'modules' => $modules,
            'li' => $this->link,
        ]);

    }

    /**
     * Cette methode permet de creer le programme academique d'une salle de classe
     * Si la classe a deja un programme alors on redirige vers la page de modification
     * @Route("/programme-academique/creer/{annee_slug}/{slug}", name="creer_programme")
     */
    public function creerProgramme(string $annee_slug, Classe $classe, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $annee = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findOneBy(['slug'=>$annee_slug]);
        if (!$annee) {
            throw $this->createNotFoundException('Page not found');
        }
        if ($annee->getIsArchived()) {
            throw new Exception("L'année ".$annee->getDenomination()." est en lecture seule !", 1);
        }

        // On redirige vers la page de modification du programme si celui-ci existe
        if ($this->entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee])) {
            return $this->redirectToRoute('edit_programme', ['annee_slug'=>$annee->getSlug(), 'slug'=>$classe->getSlug()]);
        }

        if ($_POST && !empty($_POST['modules'])) {
            foreach ($_POST['modules'] as $mod) {
                // On duplique les UEs (Modules)
                $module = new Module();
                $module->setSlug($mod['name']);
                $module->setIntitule($mod['name']);
                $module->setCode($mod['code']);
                $module->setAnneeAcademique($annee);
                $module->setClasse($classe);
                $this->entityManagerInterface->persist($module);
                // on persiste les ECs 
                foreach ($mod['ecs'] as $mat) {
                    // On part sur l'hypothese que deux matieres (EC) ne doivent pas avoir le meme code. 
                    // Donc si le code saisie existe deja, on considèrera la matière qui est enregistrée dans la base de données
                    $ec = $this->entityManagerInterface->getRepository(EC::class)->findOneBy(['code'=>$mat['code']]);
                    if (!$ec) {
                        // Si l'EC n'existe pas alors on le crée. 
                        $ec = new EC();
                        $ec->setCode($mat['code']);
                        $ec->setIntitule($mat['name']);
                        $ec->setSlug($mat['name']);
                        $this->entityManagerInterface->persist($ec);
                    }
                    // On cree une matiere qui sera liée
                    $ecModule = new ECModule();
                    $ecModule->setModule($module);
                    $ecModule->setEc($ec);
                    $ecModule->setCredit($mat['credit']);
                    $ecModule->setSemestre($mat['semestre']);
                    $this->entityManagerInterface->persist($ecModule);
                }

            }
            $this->entityManagerInterface->flush();
            return $this->redirectToRoute('PA_classe', ['annee_slug'=>$annee->getSlug(), 'slug'=>$classe->getSlug()]);
        }

        return $this->render('programme_academique/creer.html.twig', [
            'pageTitle' => 'Creer le programme academique',
            'annee' => $annee, 
            'classe' => $classe, 
            'modeCreation' => true, 
            'li' => $this->link,
        ]);
    }

    /**
     * Cette methode permet de modifier le programme academique d'une salle de classe
     * @Route("/programme-academique/modifier/{annee_slug}/{slug}", name="edit_programme")
     */
    public function modifierProgrammeAcademique(string $annee_slug, Classe $classe, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $annee = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findOneBy(['slug'=>$annee_slug]);
        if (!$annee) {
            throw $this->createNotFoundException('Page not found');
        }
        if ($annee->getIsArchived()) {
            throw new Exception("L'année ".$annee->getDenomination()." est en lecture seule !", 1);
        }
        
        // le formulaire a ete soumis
        if ($_POST && !empty($_POST['modules'])) {
            foreach ($_POST['modules'] as $mod) {
                $module = new Module();
                if (!empty($mod['id'])) {
                    $module = $this->entityManagerInterface->getRepository(Module::class)->findOneBy(['id'=>$mod['id']]);
                    if (!$module) {
                        throw $this->createNotFoundException('Erreur de donnees');
                    }
                }
                if ($module->getSlug() === null) {
                    $module->setSlug($mod['name'] . '-' . time());
                }
                $module->setIntitule($mod['name']);
                $module->setCode($mod['code']);
                $module->setAnneeAcademique($annee);
                $module->setClasse($classe);
                $this->entityManagerInterface->persist($module);
                // on persiste les ECs 
                foreach ($mod['ecs'] as $mat) {
                    // On part sur l'hypothese que deux matieres (EC) ne doivent pas avoir le meme code. 
                    // Donc si le code saisie existe deja, on considèrera la matiere qui est enregistree dans la base de donnee
                    if (!empty($mat['id_ec'])) {
                        $ec = $this->entityManagerInterface->getRepository(EC::class)->findOneBy(['id'=>$mat['id_ec']]);
                        if (!$ec) {
                            throw $this->createNotFoundException('Erreur de donnees');
                        }
                    }else {
                        // il faut creer l'ec en question ou prendre l'ec correspondant au code saisie
                        $ec = $this->entityManagerInterface->getRepository(EC::class)->findOneBy(['code'=>$mat['code']]);
                        if (!$ec) {
                            // Si l'EC n'existe pas alors on le crée. 
                            $ec = new EC();
                        }
                    }
                    $ec->setCode($mat['code']);
                    $ec->setIntitule($mat['name']);
                    $ec->setSlug($mat['name'].'-'.time());
                    $this->entityManagerInterface->persist($ec);
                    $ecModule = new ECModule();
                    if (!empty($mat['id_ecm'])) {
                        $ecModule = $this->entityManagerInterface->getRepository(ECModule::class)->findOneBy(['id'=>$mat['id_ecm']]);
                        if (!$ecModule) {
                            throw $this->createNotFoundException('Erreur de donnees');
                        }
                    }
                    $ecModule->setModule($module);
                    $ecModule->setEc($ec);
                    $ecModule->setCredit($mat['credit']);
                    $ecModule->setSemestre($mat['semestre']);
                    $this->entityManagerInterface->persist($ecModule);
                }
            }
            $this->entityManagerInterface->flush();
            return $this->redirectToRoute('PA_classe', ['annee_slug'=>$annee->getSlug(), 'slug'=>$classe->getSlug()]);
        }


        return $this->render('programme_academique/creer.html.twig', [
            'pageTitle' => 'Modifier le programme academique',
            'annee' => $annee, 
            'classe' => $classe,
            'modeCreation' => false,
            'li' => $this->link,
        ]);
    }

    /**
     * Cette fonction permet de supprimer un ec dans une classe
     * @Route("/programme-academique/ecm/delete/{annee_slug}/{classe_slug}/{id}", name="deleteECM")
     * @Route("/programme-academique/delete-ecm/{annee_slug}/{classe_slug}", name="deleteECM2")
     */
    public function deleteECM(string $annee_slug, string $classe_slug, ECModule $ecm, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $annee = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findOneBy(['slug'=>$annee_slug]);
        if (!$annee) {
            throw $this->createNotFoundException('Erreur de donnees: Annee non trouvee !');
        }
        if ($annee->getIsArchived()) {
            throw new Exception("L'année ".$annee->getDenomination()." est en lecture seule !", 1);
        }

        $classe = $this->entityManagerInterface->getRepository(Classe::class)->findOneBy(['slug'=>$classe_slug]);
        if (!$classe) {
            throw $this->createNotFoundException('Erreur de donnees: Classe non trouvee !');
        }
        $this->entityManagerInterface->remove($ecm);
        $this->entityManagerInterface->flush();

        if($request->isXmlHttpRequest()){
            return new Response(json_encode(['error'=>0]), '200', ['Content-type'=>'appplication/json']);
        }

        return $this->redirectToRoute('PA_classe', ['annee_slug'=>$annee->getSlug(), 'slug'=>$classe->getSlug()]);

    }

    /**
     * Cette fonction permet de supprimer un module
     * @Route("/programme-academique/ue/delete/{annee_slug}/{classe_slug}", name="deleteUE1")
     * @Route("/programme-academique/ue/delete/{annee_slug}/{classe_slug}/{id}", name="deleteUE")
     */
    public function deleteUE(string $annee_slug, string $classe_slug, Module $module, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $annee = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findOneBy(['slug'=>$annee_slug]);
        if (!$annee) {
            throw $this->createNotFoundException('Erreur de donnees: Annee non trouvee !');
        }
        if ($annee->getIsArchived()) {
            throw new Exception("L'année ".$annee->getDenomination()." est en lecture seule !", 1);
        }

        $classe = $this->entityManagerInterface->getRepository(Classe::class)->findOneBy(['slug'=>$classe_slug]);
        if (!$classe) {
            throw $this->createNotFoundException('Erreur de donnees: Classe non trouvee !');
        }
        foreach ($module->getECModules() as $ecModule) {
            $this->entityManagerInterface->remove($ecModule);
        }
        $this->entityManagerInterface->remove($module);
        $this->entityManagerInterface->flush();

        if($request->isXmlHttpRequest()){
            return new Response(json_encode(['error'=>0]), '200', ['Content-type'=>'appplication/json']);
        }

        return $this->redirectToRoute('PA_classe', ['annee_slug'=>$annee->getSlug(), 'slug'=>$classe->getSlug()]);
    }

    /**
     * Cette fonction permet d'importer un fichier excel contenant le(s) programme(s) academique(s) d'une ou de plusieurs salles de classe
     * Cette fonctionnalité est uniquement disponible pour les classes n'ayant pas encore de programme academique.
     * @Route("/programme-academique/import/fichier/excel/{slug}", name="import_program")
     */
    public function importProgrammesAcademiques(AnneeAcademique $annee, Request $request, ImportUtils $importUtils)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            throw new Exception("L'année ".$annee->getDenomination()." est en lecture seule !", 1);
        }
        
        $form = $this->createForm(UploadProgramType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $flashAlert = $importUtils->doImportation($this->entityManagerInterface, $form, 'p', $annee, $this->getParameter('sample_directory'));
            $this->entityManagerInterface->flush();
            $this->addFlash('errors', $flashAlert);
            return $this->redirectToRoute('programme_academique', ['slug'=>$annee->getSlug()]);
        }
        return $this->render('programme_academique/creer.html.twig', [
            'pageTitle' => 'Créer le programme academique',
            'annee' => $annee,
            'modeCreation' => true,
            'isByUpload' => true,
            'form' => $form->createView(),
            'li' => $this->link,
        ]);
    }

    /**
     * Cette fonction permet d'exporter un programme academique aux formats csv et excel
     * @Route("/programme-academique/export/programmes/fichier/excel/{slug}", name="export_programs_excel")
     */
    public function exportPrograms(AnneeAcademique $annee, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $spreadsheet = new Spreadsheet();

        $classes = $this->entityManagerInterface->getRepository(Classe::class)->findBy([], ['specialite'=>'ASC', 'niveau'=>'ASC']);
        if (!$classes) {
            throw $this->createNotFoundException('Vous n\'avez pas encore de salles de classes !');
        }
        $hasues = false;
        foreach ($classes as $classe) {
            if ($this->entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee])) {
                $hasues = true;    
                break;
            }
        }
        if (!$hasues) {
            throw $this->createNotFoundException('Aucun programme académique trouvé !');
        }

        foreach ($classes as $classe) {
            $sheet = $spreadsheet->getActiveSheet();
            $modules = $this->entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
            if ($modules) {
                $this->setProgrammeDataOnSheet($modules, $sheet, $classe, $annee);
                $sheet->setTitle("PROG_ACAD_".$classe->getCode()."_".$annee->getSlug());
                
                // ajout d'une nouvelle feuille
                $myWorkSheet = new Worksheet($spreadsheet, 'Feuille');
                $spreadsheet->addSheet($myWorkSheet, 0);
                $spreadsheet->setActiveSheetIndex(0);
                $sheet = $spreadsheet->getActiveSheet();
            }
        }
        
        $writer = new Xlsx($spreadsheet);
        $fileName = 'test.xlsx';
        $writer->save($fileName);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        
        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);
        
        // Return the excel file as an attachment
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * Cette fonction permet d'exporter un programme academique aux formats csv et excel
     * @Route("/programme-academique/export/{annee_slug}/{slug}", name="export_program")
     */
    public function exportProgram(string $annee_slug, Classe $classe, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $annee = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findOneBy(['slug'=>$annee_slug]);
        if (!$annee) {
            throw $this->createNotFoundException('Page not found');
        }
        $spreadsheet = new Spreadsheet();

        $modules = $this->entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
        if (!$modules) {
            $this->addFlash('info', $classe->getNom()." n'a pas encore de programme academique pour l'année academique ".$annee->getDenomination());
            return $this->redirectToRoute('creer_programme', ['annee_slug'=>$annee->getSlug(), 'slug'=>$classe->getSlug()]);
        }
        $sheet = $spreadsheet->getActiveSheet();
        $this->setProgrammeDataOnSheet($modules, $sheet, $classe, $annee);
        $writer = new Xlsx($spreadsheet);
        $fileName = 'programme-academique-' . $annee_slug . '-' . $classe->getSlug() . '.xlsx';
        $writer->save($fileName);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        
        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);
        
        // Return the excel file as an attachment
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    private function setProgrammeDataOnSheet(Array $modules, $sheet, Classe $classe, AnneeAcademique $annee)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $dataFile = [
            ['Intitulé de l\'UE', "Code de l'UE", "Intitulé de l'EC", "Code de l'EC", "Crédit", "Semestre"]
        ];
        foreach ($modules as $module) {
            foreach ($module->getECModules() as $ecm) {
                $dataFile[] = [
                    $module->getIntitule(),
                    $module->getCode(),
                    $ecm->getEC()->getIntitule(),
                    $ecm->getEC()->getCode(),
                    $ecm->getCredit(),
                    $ecm->getSemestre()
                ];
            }
        }

        $styleArray = [
            'font' => [
                'bold' => true,
                'size' => 14
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THICK,
                ]
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'rotation' => 90,
                'startColor' => [
                    'argb' => 'FFA0A0A0',
                ]
            ],
        ];
        $styleArray2 = [
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THICK,
                ],
            ]
        ];
        $sheet->getDefaultRowDimension()->setRowHeight(25);
        $sheet->getStyle('A4:F4')->applyFromArray($styleArray);
        if (count($dataFile) > 1) {
            $sheet->getStyle('A5:F'.(count($dataFile)+3))->applyFromArray($styleArray2);
        }
        
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);

        $sheet->setCellValue('A1', 'PROGRAMME ACADEMIQUE');
        $sheet->setCellValue('A2', 'CLASSE');
        $sheet->setCellValue('B2', $classe->getNom());
        $sheet->setCellValue('A3', 'ANNEE ACADEMIQUE');
        $sheet->setCellValue('B3', $annee->getDenomination());

        $sheet->fromArray(
            $dataFile,
            '',
            'A4'
        );
    }

    /**
     * Cette fonction permet de generer le programme academique de toutes les classes en pdf
     * @Route("/programme-academique/export/programmes/pdf/{slug}", name="export_pdf_programs")
     */
    public function exportPDFPrograms(AnneeAcademique $annee, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $classes = $this->entityManagerInterface->getRepository(Classe::class)->findBy([], ['specialite'=>'ASC', 'niveau'=>'ASC']);
        if (!$classes) {
            throw $this->createNotFoundException('Vous n\'avez pas encore de salles de classes !');
        }
        $programmes = NULL;
        foreach ($classes as $classe) {
            $modules = $this->entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
            if ($modules) {
                $programmes[] = ['classe'=>$classe, 'modules'=>$modules];
            }
        }
        if (!$programmes) {
            throw $this->createNotFoundException("Aucun programme academique trouvé !");
        }
        $pdfOptions = new Options();
        $pdfOptions->setDefaultFont('Arial');
        
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->renderView('programme_academique/pdf.html.twig', [
            'annee' => $annee,
            'programmes' => $programmes,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        $fileName = "programmes_academiques_".$annee->getSlug().".pdf";
        $output = $dompdf->output();
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        file_put_contents($temp_file, $output);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * Cette fonction permet de generer le programme academique d'une classe en pdf
     * @Route("/programme-academique/generer-pdf/{annee_slug}/{slug}", name="export_pdf_program")
     */
    public function obtenirPDF(string $annee_slug, Classe $classe, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $annee = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findOneBy(['slug'=>$annee_slug]);
        if (!$annee) {
            throw $this->createNotFoundException('Page not found');
        }
        $modules = $this->entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
        if (!$modules) {
            $this->addFlash('info', $classe->getNom()." n'a pas encore de programme academique pour l'année academique ".$annee->getDenomination());
            return $this->redirectToRoute('creer_programme', ['annee_slug'=>$annee->getSlug(), 'slug'=>$classe->getSlug()]);
        }
        $pdfOptions = new Options();
        $pdfOptions->setDefaultFont('Arial');
        
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->renderView('programme_academique/pdf.html.twig', [
            'annee' => $annee,
            'programmes' => [['classe'=>$classe, 'modules'=>$modules]],
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        $fileName = "PROG_ACAD_".$annee->getSlug().'_'.$classe->getCode().'.pdf';
        $output = $dompdf->output();
        // file_put_contents($fileName, $output);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        file_put_contents($temp_file, $output);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @Route("/programmes/academiques/liste/matieres/{slug}/{page}", name="pa_liste_matieres")
     * @Route("/programmes/academiques/liste/matieres/classe/{slug}/{page}/{slugClasse}", name="pa_liste_matieres_classe")
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function showListEC(AnneeAcademique $annee, int $page, Classe $classe=null, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($page <= 0) {
            throw $this->createNotFoundException("La page $page n'existe pas !");
        }
        $ecs = [];
        $maxResult = 25;
        $start = ($page-1)*$maxResult;

        if ($classe) {
            $maxResult = null; $start = 0;
        }

        if ($request->get('search')) {
            $ecs2 = $this->entityManagerInterface->getRepository(EC::class)->search($request->get('search'));
        }else{
            $ecs2 = $this->entityManagerInterface->getRepository(EC::class)->findBy([], ['intitule'=>'ASC'], $maxResult, $start);
        }

        foreach ($ecs2 as $ec) {
            if ($classe) {
                $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsClasseForEC($annee, $ec, $classe);
            }else{
                $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsForEC($annee, $ec);
            }
            if ($contrats) {
                $ecs[] = [
                    'ec' => $ec,
                    'nbEtudiants' => count($contrats),
                    'classes' => $this->entityManagerInterface->getRepository(ECModule::class)->findClassesEC($ec, $annee),
                    
                ];
            }
        }
        $nbPages = $classe ? 1 : (int) (count($this->entityManagerInterface->getRepository(EC::class)->findAll())/$maxResult)+1;
        $pages = [];
        if ($nbPages > 1) {
            for ($i=1; $i <= $nbPages; $i++) { 
                $pages[] = $i;
            }
        }
        // dump($ecs); die();
        return $this->render('programme_academique/liste_ec.html.twig', [
            'ecs' => $ecs,
            'annee' => $annee,
            'nbPages' => $nbPages,
            'pages' => $pages,
            'currentPage' => $page,
            'li' => 'ec',
            'cmp' => $start+1,
            'examens' => $this->entityManagerInterface->getRepository(Examen::class)->findAll(),
            'classe' => $classe,
        ]);
    }

}
