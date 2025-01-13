<?php
namespace App\Service;

use App\Entity\EC;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use App\Entity\Classe;
use App\Entity\Examen;
use App\Entity\Module;
use App\Entity\Contrat;
use App\Entity\AnneeAcademique;
use App\Entity\ECModule;
use App\Entity\EtudiantInscris;
use App\Entity\SyntheseModulaire;
use App\Repository\ContratRepository;
use App\Repository\EtudiantInscrisRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Permet de gerer les exportations des donnees sous differents formats (PDF, Excel)
 * @author : MBOZO'O METOU'OU Emmanuel Beranger Alias Sensei emmaberanger2@gmail.com
 */
class ExportUtils
{
    private $twig;
    private $colsForStats = [];
    private $legende;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
        $this->legende = '';
    }

    /**
     * Cette function va gerer l'export des etudiants via excel et via pdf.
     * Elle attend comme parametres : 
     *  - data : un tableau associatif donc chaque element est aussi un tableau a dux case la premiere contient la classe et la seconde la liste des eleves de cette classe.
     *  - format : c'est le type de fichier que l'on souhaite exporter. les valeurs prises charge sont pdf et xlsx. 
     */
    public function doExportation(Array $data, AnneeAcademique $annee, $format, Array $others=[], Examen $exam=null, EntityManagerInterface $doctrine=null)
    {
        switch (trim(mb_strtolower($format))) {
            case 'pdf':
                return $this->exportEtudiantsInPDF($data, $annee);
            break;

            case 'excel':
                return $this->exportEtudiantsInXLSX($data, $annee);
            break;
            
            case 'pdf-pv':
                return $this->getPvInPDF($data, $annee, $others);
            break;

            case 'xls-pv':
                return $this->getPvInXLSX($data[0], $annee, $exam, $others, $doctrine);
            break;

            case 'custom-xls-students':
                return $this->doCustomExportForStudents($data, $annee, $doctrine);
            break;

            case 'pdf-stats':
                return $this->getPDFStats($data, $annee);
                break;
            
        }
    }

    /**
     * Cette fonction est appelée pour faire l'exportation personnalisée des pvs
     * de notes
     */
    public function customExportPVsForJury(AnneeAcademique $annee, EntityManagerInterface $doctrine, array $champsEc, array $champsModule, array $champsSynthese, ?int $semestre, array $classes, string $disposition, ContratRepository $contratRepository)
    {
        // Si aucune classe n'a été envoyée, on recupere toutes les classes
        if (empty($classes)) {
            $classes = $doctrine->getRepository(Classe::class)->findBy([], ['niveau' => 'ASC']);
        }
        // Pour chaque classe on recupere la liste des modules de l'année en cours
        foreach ($classes as $classe) {
            
            $others['semestre'] = $semestre;
            
            return $this->getPvInXLSX($classe, $annee, null, $others, $doctrine, $semestre, $champsEc, $champsModule, $champsSynthese);
        }

    }

    private function exportEtudiantsInPDF(Array $data, AnneeAcademique $annee, $htmlFile='etudiant/pdf.html.twig', $fileName=null, Classe $classe=null, $format='A4', $orientation='portrait')
    {
        $pdfOptions = new Options();
        $pdfOptions->setDefaultFont('Arial');
        $pdfOptions->setIsRemoteEnabled(true);
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->twig->render($htmlFile, [
            'data' => $data,
            'annee' => $annee,
            'classe' => $classe,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($format, $orientation);
        $dompdf->render();
        $fileName = $fileName ? $fileName : "LISTE_DES_ELEVES_".$annee->getSlug().'.pdf';
        $output = $dompdf->output();
        // file_put_contents($fileName, $output);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // die($fileName);
        file_put_contents($temp_file, $output);

        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }

    public function exportPDFEtudiants(Array $etudiants, AnneeAcademique $annee, $fileTitle, $htmlFile='examen/etudiantpdf.html.twig', $fileName=null, Classe $classe=null, $format='A4', $orientation='portrait')
    {
        $pdfOptions = new Options();
        $pdfOptions->setDefaultFont('Arial');
        $pdfOptions->setIsRemoteEnabled(true);
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->twig->render($htmlFile, [
            'etudiants' => $etudiants,
            'annee' => $annee,
            'classe' => $classe,
            'fileTitle' => $fileTitle,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($format, $orientation);
        $dompdf->render();
        $fileName = $fileName ? $fileName : "LISTE_DES_ELEVES_".$annee->getSlug().'.pdf';
        $output = $dompdf->output();
        // file_put_contents($fileName, $output);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // die($fileName);
        file_put_contents($temp_file, $output);

        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }

    public function exportStudents(Array $data, $htmlFile='etudiant/pdf.html.twig', $fileName=null, $format='A4', $orientation='portrait')
    {
        $pdfOptions = new Options();
        $pdfOptions->setDefaultFont('Arial');
        $pdfOptions->setIsRemoteEnabled(true);
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->twig->render($htmlFile, $data);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($format, $orientation);
        $dompdf->render();
        $fileName = $fileName ? $fileName : "LISTE_DES_ELEVES.pdf";
        $output = $dompdf->output();
        // file_put_contents($fileName, $output);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // die($fileName);
        file_put_contents($temp_file, $output);

        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }

    private function getPDFStats(Array $data, $annee)
    {
        $pdfOptions = new Options();
        $pdfOptions->setDefaultFont('Arial');
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->twig->render('statistiques/pdf.html.twig', [
            'data' => $data,
            'annee' => $annee,
        ]);
        $orientation = 'portrait';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        $fileName = "STATISTIQUES_".$annee->getSlug().'.pdf';
        $output = $dompdf->output();
        // file_put_contents($fileName, $output);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // die($fileName);
        file_put_contents($temp_file, $output);

        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }

    private function getPvInPDF(Array $data, AnneeAcademique $annee, Array $others)
    {
        $pdfOptions = new Options();
        $pdfOptions->setDefaultFont('Arial');
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->twig->render('pv/pdf.html.twig', [
            'data' => $data,
            'annee' => $annee,
            'semestre' => $others['semestre'],
            'exam' => $others['examen'],
            'nbCharForStudentName' => $others['examen'] ? 40 : 34
        ]);
        $orientation = $others['examen'] ? 'landscape' : 'portrait';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        $fileName = "LISTE_DES_ELEVES_".$annee->getSlug().'.pdf';
        $output = $dompdf->output();
        // file_put_contents($fileName, $output);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // die($fileName);
        file_put_contents($temp_file, $output);

        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }

    private function doCustomExportForStudents(Array $data, AnneeAcademique $annee, EntityManagerInterface $doctrine)
    {
        $styleArray = [
            'font' => [
                'bold' => true,
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ]
            ],
        ];

        $spreadsheet = new Spreadsheet();
        $classes = $data['classes'];
        $champs = $data['champs'];
        $nbCol = count($champs);
        $champsAux = $this->getChamps();
        $fileHeader = [];
        foreach ($champs as $ch) {
            foreach ($champsAux as $key => $value) {
                if ($key == $ch) {
                    $fileHeader[] = $value;
                    break;
                }
            }
        }
        // dump($champs);die();
        $j = 0;
        foreach ($classes as $classe) {
            $repository = $doctrine->getRepository(EtudiantInscris::class);
            if (!$repository instanceof EtudiantInscrisRepository) {
                return;
            }
            $etudiants = $repository->findEtudiants($annee, 0, NULL, $classe->getFormation(), $classe->getSpecialite()->getFiliere(), $classe->getSpecialite(), $classe);
            // dump($etudiants);die();
            $sheetData = [];
            $sheetData[] = $fileHeader;
            $nbLignes = 4;
            foreach ($etudiants as $et) {
                $etData = [];
                $properties = $et->getEtudiant()->getAsArray();
                // dump($properties);die();
                foreach ($champs as $key => $value) {
                    if (isset($properties[$value])) {
                        $etData[] = $properties[$value];
                    }
                }
                if (!empty($etData)) {
                    $sheetData[] = $etData;
                    $nbLignes++;
                }
            }
            if (!empty($sheetData)) {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->fromArray($sheetData, '', 'A4');
                $sheet->setTitle("LISTE_ETUD_".$classe->getCode());
                $sheet->fromArray($sheetData, '', 'A4');
                $sheet->getDefaultRowDimension()->setRowHeight(17);
                for ($i=0; $i<$nbCol; $i++) {
                    $sheet->getColumnDimension($this->getExcelColNames()[$i])->setAutoSize(true);
                }
                $sheet->getStyle('A4:'.$this->getExcelColNames()[$nbCol].$nbLignes)->applyFromArray($styleArray);
                if ($j < count($classes)-1) {
                    $myWorkSheet = new Worksheet($spreadsheet, 'Feuille');
                    $spreadsheet->addSheet($myWorkSheet, 0);
                    $spreadsheet->setActiveSheetIndex(0);
                    $sheet = $spreadsheet->getActiveSheet();
                }
            }
            $j++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'LISTE_DES_ETUDIANTS_'.$annee->getSlug().'.xlsx';
        // $writer->save($fileName);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);
        
        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }

    private function exportEtudiantsInXLSX(Array $data, AnneeAcademique $annee)
    {
        $spreadsheet = new Spreadsheet();
        $j = 0;
        foreach ($data as $d) {
            $sheetData = [];
            if (!empty($d['etudiants']) && !empty($d['classe'])) {
                $sheet = $spreadsheet->getActiveSheet();
                $sheetData[] = $this->setSheetHeader();
                $nbLignes = count($d['etudiants'])+4;
                foreach ($d['etudiants'] as $i) {
                    // On écris les données des etudiants dans la feuille.
                    $sheetData[] = $i->getDataAsArray();
                }
                $sheet->setCellValue('B1', 'LISTE DES ETUDIANTS');
                $sheet->setCellValue('A2', 'CLASSE');
                $sheet->setCellValue('B2', $d['classe']->getCode());
                $sheet->setCellValue('A3', 'ANNEE ACADEMIQUE');
                $sheet->setCellValue('B3', $annee->getDenomination());
                $this->configureSheetColumnDimension($sheet);
                $this->setSheetStyles($sheet, $nbLignes);
                $sheet->fromArray($sheetData, '', 'A4');
                $sheet->setTitle("LISTE_ETUD_".$d['classe']->getCode());
                // Si on est pas a la fin du tableau alors on ajoute une nouvelle feuille.
                if ($j < count($data)-1) {
                    $myWorkSheet = new Worksheet($spreadsheet, 'Feuille');
                    $spreadsheet->addSheet($myWorkSheet, 0);
                    $spreadsheet->setActiveSheetIndex(0);
                    $sheet = $spreadsheet->getActiveSheet();
                }
            }
            $j++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'LISTE_DES_ETUDIANTS_'.$annee->getSlug().'.xlsx';
        // $writer->save($fileName);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);
        
        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }

    /**
     * Cette fonction permet de generer un fichier excel contenant les pv des notes
     */
    private function getPvInXLSX(Classe $classe, AnneeAcademique $annee, Examen $exam=null, Array $others, EntityManagerInterface $doctrine, $semestre=null, array $champsEc = ['ANNEE', 'TPE', 'TP', 'CC', 'EX', 'MOY', 'NOTE', 'CR', 'GRA', 'DEC'], array $champsModule = ['MOY', 'NOTE', 'CA', 'GRA', 'DEC'], array $champsSynthese = ['MOY', 'NOTE', 'CA', 'GRA', 'DEC'])
    {
        // On recupère la liste des modules de la classe pour cette annee
        $modules = $doctrine->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
        $semestreTitle = null;
        if (!empty($others['semestre'])) {
            $newModules = [];
            foreach ($modules as $module) {
                $mod = $module;
                foreach ($module->getECModules() as $key => $ecm) {
                    if ($ecm->getSemestre() != $others['semestre']) {
                        unset($mod->getECModules()[$key]);
                    }
                }
                $isEmpty = true;
                foreach ($mod->getECModules() as $m) {
                    $isEmpty = false;
                }
                if (!$isEmpty) {
                    $newModules[] = $mod;
                }
            }
            $modules = $newModules;
            $semestreTitle = 'SEMESTRE '.$others['semestre'];
            $semestre = $others['semestre'];
        }

        // Recuperer la liste des etudiant de cette calsse
        $repository = $doctrine->getRepository(EtudiantInscris::class);
        if (!$repository instanceof EtudiantInscrisRepository) {
            return;
        }
        $inscrisBDD = $repository->findEtudiants($annee, $start=0, $maxResults=NULL, NULL, NULL, NULL, $classe);
        $inscris = [];

        /**
         * On prend uniquement les etudiants qui ont deja paye la pension si cela est definie
         * dans la configuration
         */
        if (false) {
            foreach ($inscrisBDD as $e) {
                if ($e->isTranchePayed($others['semestre'])) {
                    $inscris[] = $e;
                }
            }
        }else {
            $inscris = $inscrisBDD;
        }

        if (empty($inscris)) {
            die("Aucun etudiant à afficher !");
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $data = []; $cmp = 0;
        $header = $this->buildHeaderPVExcel($modules, $sheet, count($inscris), $doctrine, $exam, $semestre, $champsEc, $champsModule, $champsSynthese);
        $data[] = $header;

        foreach ($inscris as $inscri) {
            $cmp++;
            $data[] = $this->addStudentNoteInPVExcel($inscri, $modules, $exam, $cmp, $doctrine, $semestre, $champsEc, $champsModule, $champsSynthese);
        }

        $nbLignes = $cmp;
        $nbColonnes = count($header);
        $lastColName = $this->getExcelColNames()[$nbColonnes-1];

        $sheet->fromArray($data, null, 'A4');

        $this->applyStylesInPVExcel($sheet, $nbColonnes, $nbLignes, $lastColName);

        $intitule = $exam ? $exam->getIntitule() : "SYNTHESE FINALE";
        $sheet->setCellValue('A1', "PROCES VERBAL...\n".$intitule."\n".$semestreTitle."\n".$annee->getDenominationSlash());
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true,'size' => 16],'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,'vertical' => Alignment::VERTICAL_CENTER,]]);

        $sheet->setCellValue('D1', "FACULTE DES SCIENCES DE L'UNIVERSITE D'EBOLOWA\nDEPARTEMENT/FILIERE : ".$classe->getSpecialite()->getFiliere()->getName()."\nSPECILALITE/OPTION : ".$classe->getSpecialite()->getName()."\nNIVEAU : ".$classe->getLMDLevel()."\nLEGENDE : ".$this->legende);
        $sheet->getStyle('D1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('D1')->applyFromArray(['font' => ['bold' => true,'size' => 18],'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT,'vertical' => Alignment::VERTICAL_CENTER,]]);

        $fileName = "PV_CLASSE_".$classe->getCode()."_ANNEE_ACADEMIQUE_".$annee->getSlug().'.xlsx';
        $sheet->setTitle("PV_".$classe->getCode()."_ANNEE".$annee->getSlug());
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);

        $sheet->getStyle('C4:C'.($nbLignes+4))->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);
        

        $writer = new Xlsx($spreadsheet);
        // $writer->save($fileName);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // Create the excel file in the tmp directory of the system
        $writer->save($temp_file);
        
        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }

    private function applyStylesInPVExcel($sheet, $nbColonnes, $nbLignes, $lastColName)
    {
        // die($lastColName.$nbLignes);
        $styleArray = [
            'font' => [
                'bold' => true,
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                ]
            ]
        ];
        $styleArray2 = [
            'font' => [
                'bold' => false,
                'size' => 8
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                ]
            ]
        ];
        for ($i=0; $i < $nbColonnes; $i++) { 
            $sheet->getColumnDimension($this->getExcelColNames()[$i])->setAutoSize(true);
        }

        $sheet->getRowDimension('3')->setRowHeight(25);
        $sheet->getRowDimension('2')->setRowHeight(25);
        $sheet->getRowDimension('1')->setRowHeight(150);

        $sheet->mergeCells('A1:C3');
        $sheet->mergeCells('D1:'.$lastColName.'1');
        
        $sheet->getStyle('A4:'.$lastColName.'4')->applyFromArray($styleArray);
        $last = $lastColName.(4+$nbLignes);
        $sheet->getStyle('A5:'.$last)->applyFromArray($styleArray2);
    }

    private function buildHeaderPVExcel(Array $modules, $sheet, $nbLignes, EntityManagerInterface $doctrine, Examen $examen=null, int $semestre=null, array $champsEc, array $champsModule, array $champsSynthese)
    {
        $legende = '';
        $header = ['N°', 'MATRICULE', 'NOMS & PRENOMS'];
        $names = $this->getExcelColNames();
        $start = 3;
        $num = 3;
        $rowEc = 2;
        $rowUE = 2;
        $cmp = 0;

        $styleArray = [
            'font' => [
                'bold' => true,
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                ]
            ]
        ];

        $styleArray5 = [
            'font' => [
                'bold' => true,
                'size' => 10
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
                    'argb' => 'f5b973',
                ]
            ],
        ];

        $styleArray4 = [
            'font' => [
                'bold' => true,
                'size' => 10
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
                    'argb' => 'ECE48A',
                ]
            ],
        ];

        $styleArray3 = [
            'font' => [
                'bold' => true,
                'size' => 10
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
                    'argb' => 'f08b7d',
                ]
            ],
        ];

        $styleArray2 = [
            'font' => [
                'bold' => true,
                'size' => 10
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

        foreach ($modules as $module) {
            $cmp = 0;
            $contratRepository = $doctrine->getRepository(Contrat::class);
            if (!$contratRepository instanceof ContratRepository) {
                return;
            }
            foreach ($module->getECModules() as $ecm) {
                foreach ($data = $champsEc as $key => $value) {
                    $header[] = $value;
                    $cmp++;
                }
                $nbItems = count($data);
                $cn1 = $names[$start].'3'; // Correspond a la premiere cellule qui contient les donnees de l'EC en cours
                // $cn2 = $names[$start+7].'3';
                $cn2 = $names[$start+$nbItems-1].'3'; // Correspond a la derniere cellule contenant les donnees de l'EC en cours
                $sheet->setCellValue($cn1, $ecm->getEc()->getCode());
                $sheet->getStyle($cn1.':'.$cn2)->applyFromArray($styleArray);
                $sheet->mergeCells($cn1.':'.$cn2);

                $legende .= $ecm->getEc()->getCode() .' = '. $ecm->getEc()->getIntitule() .', ';

                $ctrs = $contratRepository->findContratsClasseForEC($module->getAnneeAcademique(), $ecm->getEc(), $module->getClasse());
                $ligne = $nbLignes + 5; // 5 represente les quatre premieres lignes du fichier (entetes) et la ligne des stats. nbLigne represente le nombre d'etudiant
                $effectif = count($ctrs);
                // $sheet->mergeCells($names[$start].$ligne.':'.$names[$start+7].$ligne);
                $sheet->mergeCells($names[$start].$ligne.':'.$names[$start+$nbItems-1].$ligne);
                $hasValidated = count($contratRepository->findContratsClasseByECModule($module->getAnneeAcademique(), $ecm, $module->getClasse()));
                $percent = number_format(($hasValidated/$effectif)*100, 2).'%';
                // $moyGen = $this->getMoyenneGenerale($ctrs);
                $sheet->setCellValue($names[$start].$ligne, "inscrits : ".$effectif."\n"."validé : ".$hasValidated."\n"."Pourcentage : ".$percent);
                $sheet->getStyle($names[$start].$ligne)->getAlignment()->setWrapText(true);
                // $sheet->getStyle($names[$start].$ligne.':'.$names[$start+7].$ligne)->applyFromArray(['font' => ['bold' => true,'size' => 12], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK,]], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_JUSTIFY,'vertical' => Alignment::VERTICAL_CENTER,]]);
                $sheet->getStyle($names[$start].$ligne.':'.$names[$start+$nbItems-1].$ligne)->applyFromArray(['font' => ['bold' => true,'size' => 12], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THICK,]], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_JUSTIFY,'vertical' => Alignment::VERTICAL_CENTER,]]);
                $sheet->getRowDimension($ligne)->setRowHeight(50);
                // $start += count($data);
                $start += count($data);
            }

            $data2 = $champsModule;
            foreach ($data2 as $key => $value) {
                $header[] = $value;
                $cmp++;
            }
            $nbItemsModule = count($data2)-1;
            $cn1 = $names[$start].'3'; // Correspond a la premiere cellule devant contenir les donnees de la synthese du module en cours
            // $cn2 = $names[$start+4].'3'; // Corespond a la cellule H3
            $cn2 = $names[$start+$nbItemsModule].'3'; // Corespond a la derniere cellule devant contenir les donnees du module en cours
            // $cn6 = $names[$start+4].($nbLignes+4); // Correspond a la colonne H
            $cn6 = $names[$start+$nbItemsModule].($nbLignes+$nbItemsModule);
            $sheet->setCellValue($cn1, 'SYNTHESE UE');
            $sheet->getStyle($cn1.':'.$cn6)->applyFromArray($styleArray2); // La plage de la synthese du module en cours
            $sheet->mergeCells($cn1.':'.$cn2);
            $start += count($data2);

            $cn3 = $names[$num].'2'; // Correspond a la cellule D2
            $cn4 = $names[$num+$cmp-1].'2'; // Correspond a la derniere cellule qui contient les donnees du module en cours
            $sheet->setCellValue($cn3, $module->getIntitule().' - '.$module->getCode());
            $sheet->getStyle($cn3.':'.$cn4)->applyFromArray($styleArray);
            $sheet->mergeCells($cn3.':'.$cn4); // La plage qui contient l'intitule du module
            
            $num += $cmp; // On se positionne pour le module suivant
        }

        if (!$examen && !empty($champsSynthese)) {
            $nbSyntheseItems = count($champsSynthese);
            // Synthese du semestre
            if ($semestre == 1) {
                // On ajoute la synthese du premier semestre
                foreach ($champsSynthese as $key => $value) {
                    $header[] = $value;
                    $cmp++;
                }
                $cn3 = $names[$num].'3';
                $cn4 = $names[$num+$nbSyntheseItems-1].'3';
                $cn5 = $names[$num+$nbSyntheseItems-1].($nbLignes+$nbSyntheseItems-1);
                $sheet->setCellValue($cn3, 'SYNTHESE SEMETRE 1');
                $sheet->getStyle($cn3.':'.$cn5)->applyFromArray($styleArray4);
                $sheet->mergeCells($cn3.':'.$cn4);

            }elseif ($semestre == 2) {
                // On ajoute la synthese du second semestre
                foreach ($champsSynthese as $key => $value) {
                    $header[] = $value;
                    $cmp++;
                }

                $cn3 = $names[$num].'3';
                $cn4 = $names[$num+$nbSyntheseItems-1].'3';
                $cn5 = $names[$num+$nbSyntheseItems-1].($nbLignes+$nbSyntheseItems-1);
                $sheet->setCellValue($cn3, 'SYNTHESE SEMETRE 2');
                $sheet->getStyle($cn3.':'.$cn5)->applyFromArray($styleArray5);
                $sheet->mergeCells($cn3.':'.$cn4);

            }else {
                // On ajoute la sunthese du premier et second semestre et la synthese annuelle
                // On ajoute la synthese du premier semestre
                foreach ($champsSynthese as $key => $value) {
                    $header[] = $value;
                    $cmp++;
                }

                // On ajoute la synthese du second semestre
                foreach ($champsSynthese as $key => $value) {
                    $header[] = $value;
                    $cmp++;
                }

                // On ajoute la synthese annuelle
                foreach ($champsSynthese as $key => $value) {
                    $header[] = $value;
                    $cmp++;
                }
                
                $header[] = 'STATUT';

                $cn3 = $names[$num].'3';
                $cn4 = $names[$num+$nbSyntheseItems-1].'3';
                $cn5 = $names[$num+$nbSyntheseItems-1].($nbLignes+$nbSyntheseItems-1);
                $sheet->setCellValue($cn3, 'SYNTHESE SEMETRE 1');
                $sheet->getStyle($cn3.':'.$cn5)->applyFromArray($styleArray4);
                $sheet->mergeCells($cn3.':'.$cn4);

                $cn3 = $names[$num+$nbSyntheseItems].'3';
                $cn4 = $names[$num+($nbSyntheseItems*2)-1].'3';
                $cn5 = $names[$num+($nbSyntheseItems*2)-1].($nbLignes+$nbSyntheseItems-1);
                $sheet->setCellValue($cn3, 'SYNTHESE SEMETRE 2');
                $sheet->getStyle($cn3.':'.$cn5)->applyFromArray($styleArray5);
                $sheet->mergeCells($cn3.':'.$cn4);

                $cn3 = $names[$num+($nbSyntheseItems*2)].'3';
                $cn4 = $names[$num+($nbSyntheseItems*3)-1].'3';
                $cn5 = $names[$num+($nbSyntheseItems*3)-1].($nbLignes+$nbSyntheseItems-1);
                $sheet->setCellValue($cn3, 'SYNTHESE ANNUELLE');
                $sheet->getStyle($cn3.':'.$cn5)->applyFromArray($styleArray3);
                $sheet->mergeCells($cn3.':'.$cn4);

            }
        }

        $this->legende = $legende;

        return $header;
    }

    /**
     * Cette fonction permet de mettre toutes les notes de l'etudiant dans le pv
     */
    private function addStudentNoteInPVExcel(EtudiantInscris $ins, Array $modules, Examen $examen=null, int $cmp, EntityManagerInterface $doctrine, $semestre=null, array $champsEc, array $champsModule, array $champsSynthese): Array
    {
        $data = [
            $cmp,
            $ins->getEtudiant()->getMatricule(),
            $ins->getEtudiant()->getNomComplet(),
        ];

        $cinq = 5; $deux = 2;

        $creditsAcquisSemestre1 = 0;
        $creditsAcquisSemestre2 = 0;
        $creditsAcquisAnnuel = 0;
        
        foreach ($modules as $module) {
            $contratsExistes = false;
            foreach ($module->getECModules() as $ecm) {
                // On recupere le contrat de l'etudiant pour cette matiere
                $contrat = $doctrine->getRepository(Contrat::class)->findOneBy(['etudiantInscris'=>$ins, 'ecModule'=>$ecm]);
                if (!$contrat) {
                    // On met des cases vides
                    foreach ($champsEc as $champs) {
                        $data[] = '-';
                    }
                }else {
                    $stats = $contrat->statsDef;
                    if ($examen) {
                        if (strtoupper($examen->getCode()) == 'SN') {
                            $stats = $contrat->statsSN;
                        }elseif (strtoupper($examen->getCode()) == 'SR') {
                            $stats = $contrat->statsSR;
                        }else {
                            die("Examen non reconnu !");
                        }
                    }
                    
                    if (in_array('ANNEE', $champsEc)) {
                        $data[] = $ins->getAnneeAcademique()->getDenominationSlash();
                    }
                    if (in_array('TPE', $champsEc)) {
                        $data[] = $this->affiche_zero($stats['noteTPE']);
                    }
                    if (in_array('TP', $champsEc)) {
                        $data[] = $this->affiche_zero($stats['noteTP']);
                    }
                    if (in_array('CC', $champsEc)) {
                        $data[] = $this->affiche_zero($stats['cc']);
                    }
                    if (in_array('EX', $champsEc)) {
                        $data[] = $this->affiche_zero($stats['exam']);
                    }
                    if (in_array('MOY', $champsEc)) {
                        $data[] = $this->affiche_zero($stats['moyenne']);
                    }
                    if (in_array('NOTE', $champsEc)) {
                        $data[] = $this->affiche_zero($stats['note']);
                    }
                    if (in_array('CR', $champsEc)) {
                        $data[] = $ecm->getCredit();
                    }
                    if (in_array('GRA', $champsEc)) {
                        $data[] = $stats['grade'];
                    }
                    if (in_array('DEC', $champsEc)) {
                        $data[] = $stats['decision'];
                    }
                    if (in_array('SV', $champsEc)) {
                        $data[] = $stats['sessionValidation'];
                    }
                    $contratsExistes = true;
                }
            }
            // On ajoute la synthese du module;
            // if (!$examen) {
                $syntheseModulaire = $doctrine->getRepository(SyntheseModulaire::class)->findOneBy(['etudiantInscris'=>$ins, 'module'=>$module]);
                if ($syntheseModulaire) {
                    if (in_array('MOY', $champsModule)) {
                        $data[] = $this->affiche_zero($syntheseModulaire->getMoyenne());
                    }
                    if (in_array('NOTE', $champsModule)) {
                        $data[] = $this->affiche_zero($syntheseModulaire->getNote());
                    }
                    if (in_array('CA', $champsModule)) {
                        $data[] = $syntheseModulaire->getCreditValider();
                    }
                    if (in_array('GRA', $champsModule)) {
                        $data[] = $contratsExistes ? $syntheseModulaire->getGrade() : '';
                    }
                    if (in_array('DEC', $champsModule)) {
                        $data[] = $contratsExistes ? $syntheseModulaire->getDecision() : '';
                    }
                    
                    $creditsAcquisAnnuel += $syntheseModulaire->getCreditValider();
                }else {
                    foreach ($champsModule as $champ) {
                        $data[] = '';
                    }
                }
            // }else {
            //     $data[] = '';$data[] = '';$data[] = '';$data[] = '';$data[] = '';
            // }
            
        }

        if (!$examen) {
            // Synthese du semestre
            if ($semestre == 1) {
                // On ajoute la synthese du premier semestre
                if (in_array('MOY', $champsSynthese)) {
                    $data[] = $this->affiche_zero($ins->getMoyenneSemestre1());
                }
                if (in_array('NOTE', $champsSynthese)) {
                    $data[] = $ins->getMoyenneSemestre1() ? number_format($ins->getMoyenneSemestre1()/$cinq, $deux) : '';
                }
                if (in_array('CA', $champsSynthese)) {
                    $data[] = $ins->getCreditAcquisSemestre1();
                }
                if (in_array('GRA', $champsSynthese)) {
                    $data[] = $ins->getGrade($ins->getMoyenneSemestre1());
                }
                if (in_array('DEC', $champsSynthese)) {
                    $data[] = $ins->getDecision($semestre);
                }

            }elseif ($semestre == 2) {
                // On ajoute la synthese du second semestre
                if (in_array('MOY', $champsSynthese)) {
                    $data[] = $this->affiche_zero($ins->getMoyenneSemestre2());
                }
                if (in_array('NOTE', $champsSynthese)) {
                    $data[] = $ins->getMoyenneSemestre2() ? number_format($ins->getMoyenneSemestre2()/$cinq, $deux) : '';
                }
                if (in_array('CA', $champsSynthese)) {
                    $data[] = $ins->getCreditAcquisSemestre2();
                }
                if (in_array('GRA', $champsSynthese)) {
                    $data[] = $ins->getGrade($ins->getMoyenneSemestre2());
                }
                if (in_array('DEC', $champsSynthese)) {
                    $data[] = $ins->getDecision($semestre);
                }

            }else {
                // On ajoute la sunthese du premier et second semestre et la synthese annuelle
                // On ajoute la synthese du premier semestre
                if (in_array('MOY', $champsSynthese)) {
                    $data[] = $this->affiche_zero($ins->getMoyenneSemestre1());
                }
                if (in_array('NOTE', $champsSynthese)) {
                    $data[] = $ins->getMoyenneSemestre1() ? number_format($ins->getMoyenneSemestre1()/$cinq, $deux) : '';
                }
                if (in_array('CA', $champsSynthese)) {
                    $data[] = $ins->getCreditAcquisSemestre1();
                }
                if (in_array('GRA', $champsSynthese)) {
                    $data[] = $ins->getGrade($ins->getMoyenneSemestre1());
                }
                if (in_array('DEC', $champsSynthese)) {
                    $data[] = $ins->getDecision($semestre);
                }

                // On ajoute la synthese du second semestre
                if (in_array('MOY', $champsSynthese)) {
                    $data[] = $this->affiche_zero($ins->getMoyenneSemestre2());
                }
                if (in_array('NOTE', $champsSynthese)) {
                    $data[] = $ins->getMoyenneSemestre2() ? number_format($ins->getMoyenneSemestre2()/$cinq, $deux) : '';
                }
                if (in_array('CA', $champsSynthese)) {
                    $data[] = $ins->getCreditAcquisSemestre2();
                }
                if (in_array('GRA', $champsSynthese)) {
                    $data[] = $ins->getGrade($ins->getMoyenneSemestre2());
                }
                if (in_array('DEC', $champsSynthese)) {
                    $data[] = $ins->getDecision($semestre);
                }

                // On ajoute la synthese annuelle
                if (in_array('MOY', $champsSynthese)) {
                    $data[] = $this->affiche_zero($ins->getMoyenneObtenue());
                }
                if (in_array('NOTE', $champsSynthese)) {
                    $data[] = $ins->getMoyenneObtenue() ? number_format($ins->getMoyenneObtenue()/$cinq, $deux) : '';
                }
                if (in_array('CA', $champsSynthese)) {
                    $data[] = $creditsAcquisAnnuel;
                }
                if (in_array('GRA', $champsSynthese)) {
                    $data[] = $ins->getGrade($ins->getMoyenneObtenue());
                }
                if (in_array('DEC', $champsSynthese)) {
                    $data[] = $ins->getDecision();
                }
                $admissibilite = 'RED';
                if ($creditsAcquisAnnuel == 60) {
                    $admissibilite = 'ADD';
                }elseif ($creditsAcquisAnnuel >= 45) {
                    $admissibilite = 'ADC';
                }
                $data[] = $admissibilite;

            }
        }
        

        return $data;
    }

    /**
     * On defini ici la largeur des colonne ainsi que la hauteur
     */
    private function configureSheetColumnDimension($sheet)
    {
        $sheet->getDefaultRowDimension()->setRowHeight(17);

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setAutoSize(true);
        $sheet->getColumnDimension('O')->setAutoSize(true);
        $sheet->getColumnDimension('P')->setAutoSize(true);
        $sheet->getColumnDimension('Q')->setAutoSize(true);
        $sheet->getColumnDimension('R')->setAutoSize(true);
        $sheet->getColumnDimension('S')->setAutoSize(true);
        $sheet->getColumnDimension('T')->setAutoSize(true);
        $sheet->getColumnDimension('U')->setAutoSize(true);
        $sheet->getColumnDimension('V')->setAutoSize(true);
        $sheet->getColumnDimension('W')->setAutoSize(true);
        $sheet->getColumnDimension('X')->setAutoSize(true);
        $sheet->getColumnDimension('Y')->setAutoSize(true);
        $sheet->getColumnDimension('Z')->setAutoSize(true);
    }

    /**
     * On defini les styles qu'on va appliquer à notre feuille
     */
    private function setSheetStyles($sheet, $nbLignes)
    {
        $styleArray = [
            'font' => [
                'bold' => true,
                'size' => 12
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
                'bold' => false,
                'size' => 12,
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

        $sheet->getStyle('A4:Z4')->applyFromArray($styleArray);
        if ($nbLignes > 4) {
            $sheet->getStyle('A5:Z'.$nbLignes)->applyFromArray($styleArray2);
        }
    }

    /**
     * Cette fonction defini les titres des colonnes de chaque feuille
     */
    public function setSheetHeader()
    {
        return [
            'MATRICULE',
            "NOM DE L'ETUDIANT",
            'PRENOM',
            'DATE DE NAISSANCE',
            'LIEU DE NAISSANCE',
            'SEXE',
            'TELEPHONE 1',
            'TELEPHONE 2',
            'ADRESSE E-MAIL',
            "NOMBRE D'ENFANTS",
            'SITUATION MATRIMONIALE',
            'CIVILITE',
            'DIPLOME ACADEMIQUE ELEVE',
            "ANNEE D'OBTENTION",
            "DIPLOME D'ENTRER",
            "ANNEE D'OBTENTION",
            "NOM DU PERE",
            "TELEPHONE DU PERE",
            "PROFESSION DU PERE",
            "NOM DE LA MERE",
            "TELEPHONE DE LA MERE",
            "PROFESSION DE LA MERE",
            "ADRESSE DES PARENTS",
            "PERSONNE A CONTACTER EN CAS DE PROBLEME",
            "TELEPHONE EN CAS D'URGENCE",
            "STATUT DANS LA CLASSE"
        ];
    }

    /**
     * Cette fonction va creer le pdf qui contient la liste des étudiants qui suivent une matiere donnée (la matière figure dans leurs contrat)
     * 
     */
    public function genererPDFEtudiantsByEC(array $contrats, AnneeAcademique $annee, EC $ec, Classe $classe=null, $isForNote=false, $withAnonymat=false, Examen $session=null, $withNames=false)
    {
        $template = 'etudiant/pdf_etudiants_matiere.html.twig';
        $fileName = 'liste_etudiants_annee_academique_'.trim(str_replace('-', '_', $annee->getSlug())).'_suivent_'.$ec->getSlug().'.pdf';
        return $this->exportEtudiantsInPDF(['contrats'=>$contrats, 'ec'=>$ec, 'isForNote'=>$isForNote, 'withAnonymat'=>$withAnonymat, 'examen'=>$session, 'withNames'=>$withNames], $annee, $template, $fileName, $classe);
    }

    private function getExcelColNames()
    {
        return["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ","BA","BB","BC","BD","BE","BF","BG","BH","BI","BJ","BK","BL","BM","BN","BO","BP","BQ","BR","BS","BT","BU","BV","BW","BX","BY","BZ","CA","CB","CC","CD","CE","CF","CG","CH","CI","CJ","CK","CL","CM","CN","CO","CP","CQ","CR","CS","CT","CU","CV","CW","CX","CY","CZ","DA","DB","DC","DD","DE","DF","DG","DH","DI","DJ","DK","DL","DM","DN","DO","DP","DQ","DR","DS","DT","DU","DV","DW","DX","DY","DZ","EA","EB","EC","ED","EE","EF","EG","EH","EI","EJ","EK","EL","EM","EN","EO","EP","EQ","ER","ES","ET","EU","EV","EW","EX","EY","EZ","FA","FB","FC","FD","FE","FF","FG","FH","FI","FJ","FK","FL","FM","FN","FO","FP","FQ","FR","FS","FT","FU","FV","FW","FX","FY","FZ","GA","GB","GC","GD","GE","GF","GG","GH","GI","GJ","GK","GL","GM","GN","GO","GP","GQ","GR","GS","GT","GU","GV","GW","GX","GY","GZ","HA","HB","HC","HD","HE","HF","HG","HH","HI","HJ","HK","HL","HM","HN","HO","HP","HQ","HR","HS","HT","HU","HV","HW","HX","HY","HZ","IA","IB","IC","ID","IE","IF","IG","IH","II","IJ","IK","IL","IM","IN","IO","IP","IQ","IR","IS","IT","IU","IV","IW","IX","IY","IZ","JA","JB","JC","JD","JE","JF","JG","JH","JI","JJ","JK","JL","JM","JN","JO","JP","JQ","JR","JS","JT","JU","JV","JW","JX","JY","JZ","KA","KB","KC","KD","KE","KF","KG","KH","KI","KJ","KK","KL","KM","KN","KO","KP","KQ","KR","KS","KT","KU","KV","KW","KX","KY","KZ","LA","LB","LC","LD","LE","LF","LG","LH","LI","LJ","LK","LL","LM","LN","LO","LP","LQ","LR","LS","LT","LU","LV","LW","LX","LY","LZ","MA","MB","MC","MD","ME","MF","MG","MH","MI","MJ","MK","ML","MM","MN","MO","MP","MQ","MR","MS","MT","MU","MV","MW","MX","MY","MZ","NA","NB","NC","ND","NE","NF","NG","NH","NI","NJ","NK","NL","NM","NN","NO","NP","NQ","NR","NS","NT","NU","NV","NW","NX","NY","NZ","OA","OB","OC","OD","OE","OF","OG","OH","OI","OJ","OK","OL","OM","ON","OO","OP","OQ","OR","OS","OT","OU","OV","OW","OX","OY","OZ","PA","PB","PC","PD","PE","PF","PG","PH","PI","PJ","PK","PL","PM","PN","PO","PP","PQ","PR","PS","PT","PU","PV","PW","PX","PY","PZ","QA","QB","QC","QD","QE","QF","QG","QH","QI","QJ","QK","QL","QM","QN","QO","QP","QQ","QR","QS","QT","QU","QV","QW","QX","QY","QZ","RA","RB","RC","RD","RE","RF","RG","RH","RI","RJ","RK","RL","RM","RN","RO","RP","RQ","RR","RS","RT","RU","RV","RW","RX","RY","RZ","SA","SB","SC","SD","SE","SF","SG","SH","SI","SJ","SK","SL","SM","SN","SO","SP","SQ","SR","SS","ST","SU","SV","SW","SX","SY","SZ","TA","TB","TC","TD","TE","TF","TG","TH","TI","TJ","TK","TL","TM","TN","TO","TP","TQ","TR","TS","TT","TU","TV","TW","TX","TY","TZ","UA","UB","UC","UD","UE","UF","UG","UH","UI","UJ","UK","UL","UM","UN","UO","UP","UQ","UR","US","UT","UU","UV","UW","UX","UY","UZ","VA","VB","VC","VD","VE","VF","VG","VH","VI","VJ","VK","VL","VM","VN","VO","VP","VQ","VR","VS","VT","VU","VV","VW","VX","VY","VZ","WA","WB","WC","WD","WE","WF","WG","WH","WI","WJ","WK","WL","WM","WN","WO","WP","WQ","WR","WS","WT","WU","WV","WW","WX","WY","WZ","XA","XB","XC","XD","XE","XF","XG","XH","XI","XJ","XK","XL","XM","XN","XO","XP","XQ","XR","XS","XT","XU","XV","XW","XX","XY","XZ","YA","YB","YC","YD","YE","YF","YG","YH","YI","YJ","YK","YL","YM","YN","YO","YP","YQ","YR","YS","YT","YU","YV","YW","YX","YY","YZ","ZA","ZB","ZC","ZD","ZE","ZF","ZG","ZH","ZI","ZJ","ZK","ZL","ZM","ZN","ZO","ZP","ZQ","ZR","ZS","ZT","ZU","ZV","ZW","ZX","ZY","ZZ"];
    }

    public function getChamps()
    {
        return [
            'matricule'=>'MATRICULE',
            'nom'=>"NOM DE L'ETUDIANT",
            'prenom'=>'PRENOM',
            'dateDeNaissance'=>'DATE DE NAISSANCE',
            'lieuDeNaissance'=>'LIEU DE NAISSANCE',
            'sexe'=>'SEXE',
            'telephone1'=>'TELEPHONE 1',
            'telephone2'=>'TELEPHONE 2',
            'adresseEmail'=>'ADRESSE E-MAIL',
            'nombreDEnfants'=>"NOMBRE D'ENFANTS",
            'situationMatrimoniale'=>'SITUATION MATRIMONIALE',
            'civilite'=>'CIVILITE',
            'diplomeAcademiqueMax'=>'DIPLOME ACADEMIQUE ELEVE',
            'anneeObtentionDiplomeAcademiqueMax'=>"ANNEE D'OBTENTION",
            'diplomeDEntre'=>"DIPLOME D'ENTRER",
            'anneeObtentionDiplomeEntre'=>"ANNEE D'OBTENTION",
            'nomDuPere'=>"NOM DU PERE",
            'numeroDeTelephoneDuPere'=>"TELEPHONE DU PERE",
            'professionDuPere'=>"PROFESSION DU PERE",
            'nomDeLaMere'=>"NOM DE LA MERE",
            'numeroDeTelephoneDeLaMere'=>"TELEPHONE DE LA MERE",
            'professionDeLaMere'=>"PROFESSION DE LA MERE",
            'adresseDesParents'=>"ADRESSE DES PARENTS",
            'personneAContacterEnCasDeProbleme'=>"PERSONNE A CONTACTER EN CAS DE PROBLEME",
            'numeroDUrgence'=>"TELEPHONE EN CAS D'URGENCE",
            'departement' => "Département d'origine",
            'region' => "Région d'origine"
        ];
    }

    public function generateInPDF(Array $data, $format='A4', $orientation='portrait')
    {
        $pdfOptions = new Options();
        $pdfOptions->setDefaultFont('Arial');
        $pdfOptions->setIsRemoteEnabled(true);
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->twig->render('note/pdf_note.html.twig', $data);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($format, $orientation);
        $dompdf->render();
        $fileName = 'NOTES.pdf';
        $output = $dompdf->output();
        // file_put_contents($fileName, $output);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // die($fileName);
        file_put_contents($temp_file, $output);

        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }

    public function getFichePreinscription(EtudiantInscris $inscrit, $htmlFile='preinscription/fiche.html.twig', $format='A4', $orientation='portrait')
    {
        $pdfOptions = new Options();
        $pdfOptions->setDefaultFont('Arial');
        $pdfOptions->setIsRemoteEnabled(true);
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->twig->render($htmlFile, [
            'inscrit' => $inscrit,
            'annee' => $inscrit->getAnneeAcademique()->getAsArray(),
            'inscri' => $inscrit->getASArray(),
            'inPDF' => true,

        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($format, $orientation);
        $dompdf->render();
        $fileName = "fiche-preinscription-".$inscrit->getEtudiant()->getMatricule().'.pdf';
        $output = $dompdf->output();
        // file_put_contents($fileName, $output);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // die($fileName);
        file_put_contents($temp_file, $output);

        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }

    public function getMoyenneGenerale(Array $contrats): ?string
    {
        $moyGen = null; 
        $moyTotal = 0;
        if (!empty($contrats)) {
            foreach ($contrats as $c) {
                $moyTotal += $c->getMoyDefinitive();
            }
            $moyGen = number_format($moyTotal/count($contrats), 2);
        }
            

        return $this->affiche_zero($moyGen);
    }

    private function affiche_zero(?float $val): ?string
    {
        if (!$val) {
            return null;
        }

        $tab = explode('.', $val);
        $partieEntiere = $tab[0];
        $partieDecimale = null;
        if (!empty($tab[1])) {
            $partieDecimale = $tab[1];
        }

        if (strlen($partieEntiere) == 1) {
            $partieEntiere = '0'.$partieEntiere;
        }

        if ($partieDecimale == null) {
            $partieDecimale = '00';
        }elseif (strlen($partieDecimale) == 1) {
            $partieDecimale = $partieDecimale.'0';
        }

        $val = $partieEntiere.','.$partieDecimale;

        return $val;
    }

    public function getDocumentsScolairePdf(Array $data, string $fileName, string $format='A4', string $orientation='portrait')
    {
        $pdfOptions = new Options();
        $pdfOptions->setDefaultFont('Arial');
        $pdfOptions->setIsRemoteEnabled(true);
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->twig->render('document_scolarite/documents.html.twig', $data);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($format, $orientation);
        $dompdf->render();
        $fileName = $fileName;
        $output = $dompdf->output();
        // file_put_contents($fileName, $output);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // die($fileName);
        file_put_contents($temp_file, $output);

        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }
}
