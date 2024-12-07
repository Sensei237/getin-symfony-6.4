<?php

namespace App\Controller\Extraction;

use App\Form\UploadFileType;
use App\Repository\AnneeAcademiqueRepository;
use App\Repository\ClasseRepository;
use App\Repository\ContratRepository;
use App\Repository\ECModuleRepository;
use App\Repository\ECRepository;
use App\Repository\ModuleRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FromExcelFileController extends AbstractController
{
    /**
     * @Route("/extraction/from/excel/file", name="extraction_from_excel_file", methods={"GET","POST"})
     */
    public function index(Request $request, EntityManagerInterface $manager, AnneeAcademiqueRepository $anneeAcademiqueRepository, ECModuleRepository $eCModuleRepository, ContratRepository $contratRepository, ECRepository $eCRepository, ClasseRepository $classeRepository)
    {
        $form = $this->createForm(UploadFileType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->getData()['file'];
            $filename = md5(uniqid()).'.xlsx';
            $sampleDirectory = $this->getParameter('sample_directory');
            try {
                $file->move($sampleDirectory, $filename);
            } catch (FileException $e) {
                die($e->getMessage());
            }
            $inputFileName = $sampleDirectory.'/'.$filename;
            $inputFileType = 'Xlsx';
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

            $worksheetData = $reader->listWorksheetInfo($inputFileName);

            $annee = $anneeAcademiqueRepository->findOneBy(['isArchived' => false]);

            foreach ($worksheetData as $worksheet) {
                $sheetName = $worksheet['worksheetName'];
                $totalRows = $worksheet['totalRows'];
                if ($totalRows > 10) {
                    $reader->setLoadSheetsOnly($sheetName);
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($inputFileName);
                    $sheet = $spreadsheet->getActiveSheet();
                    $codeClasse = $sheet->getCell('A1')->getValue();
                    $codeEC = $sheet->getCell('B1')->getValue();
                    $classe = $classeRepository->findOneBy(['code' => $codeClasse]);
                    // dump($classe, $codeClasse);die;
                    if($classe === null) {
                        echo($sheetName);die;
                    }
                    $ecModule = $eCModuleRepository->findECModuleByYearAndClasseAndCodeEC($annee, $classe, $codeEC);
                    if($ecModule === null) {
                        echo("Problème -> Feuille = ".$sheetName." -> code EC = ".$codeEC);die;
                    }
                    $ec = $ecModule->getEC();
                    $contrats = $contratRepository->findContratsClasseForEC($annee, $ec, $classe);

                    
                    for ($i=10; $i <= $totalRows; $i++) { 
                        $matricule = $sheet->getCell('C' . $i)->getValue();
                        $tabNom = explode(' ', trim($sheet->getCell('D' . $i)->getValue()));
                        $tab2 = [];
                        foreach ($tabNom as $key => $value) {
                            if (trim($value) != '') {
                                $tab2[] = trim($value);
                            }
                        }
                        $nomEtudiant = trim(implode(" ", $tab2));

                        $noteTPE = $sheet->getCell('E' . $i)->getValue();
                        $noteCC = $sheet->getCell('F' . $i)->getValue();
                        $noteTP = $sheet->getCell('G' . $i)->getValue();
                        $noteExam = $sheet->getCell('H' . $i)->getValue();
                        $moyenne = $sheet->getCell('I' . $i)->getValue();
                        foreach ($contrats as $key => $c) {
                            // dump($c->getEtudiantInscris()->getEtudiant()->getNomComplet(), $nomEtudiant, strtoupper($nomEtudiant), strtoupper($c->getEtudiantInscris()->getEtudiant()->getNomComplet()), strtoupper($nomEtudiant) === strtoupper($c->getEtudiantInscris()->getEtudiant()->getNomComplet()), strcmp(strtoupper($nomEtudiant), strtoupper($c->getEtudiantInscris()->getEtudiant()->getNomComplet())));die;
                            // echo(mb_strtoupper($nomEtudiant, 'UTF-8'));echo " === ";echo(mb_strtoupper($c->getEtudiantInscris()->getEtudiant()->getNomComplet(), "UTF-8")); echo " => ";
                            // if (mb_strtoupper($nomEtudiant, "UTF-8") == mb_strtoupper($c->getEtudiantInscris()->getEtudiant()->getNomComplet(), "UTF-8")) {
                            //     echo("EGALE");
                            // } else {
                            //     echo("PAS EGALE ". strlen($nomEtudiant)." " . strlen($c->getEtudiantInscris()->getEtudiant()->getNomComplet()));
                            // }
                            // echo "<br>--------------------------------------------------------------------------------<br>";
                            // if (strcmp(strtoupper($nomEtudiant), strtoupper($c->getEtudiantInscris()->getEtudiant()->getNomComplet()))) {
                            if (strtoupper($matricule) === strtoupper($c->getEtudiantInscris()->getEtudiant()->getMatricule())) {
                                if (!empty($noteCC) && is_numeric($noteCC) && $noteCC <= 20 && $noteCC >= 0) {
                                    $c->setNoteCC($noteCC);
                                }
                                if (!empty($noteTPE) && is_numeric($noteTPE) && $noteTPE <= 20 && $noteTPE >= 0) {
                                    $c->setNoteTPE($noteTPE);
                                }
                                if (!empty($noteTP) && is_numeric($noteTP) && $noteTP <= 20 && $noteTP >= 0) {
                                    $c->setNoteTP($noteTP);
                                }
                                if (!empty($noteExam) && is_numeric($noteExam) && $noteExam <= 20 && $noteExam >= 0) {
                                    $c->setNoteSN($noteExam);
                                }
                                $existe = true;
                                unset($contrats[$key]);
                                break;
                            }
                        }
                        // echo "======================================================================================================<br>";
                    }
                }
            }

            $manager->flush();

            return $this->redirectToRoute('extraction_from_excel_file');
        }

        return $this->render('extraction/from_excel_file/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    // /**
    //  * @Route("/extraction/delete", name="delete_extraction", methods={"GET","POST"})
    //  */
    // public function deleteABOPC(ObjectManager $manager, ClasseRepository $classeRepository, ECModuleRepository $eCModuleRepository, ContratRepository $contratRepository, ModuleRepository $moduleRepository, ECRepository $eCRepository)
    // {
    //     $classe = $classeRepository->findOneBy(['code' => 'BCL4']);
    //     if ($classe == null) {
    //         die("La classe ABO4 n'existe pas");
    //     }
        
    //     $etudiants = $classe->getEtudiantInscris();
    //     foreach ($etudiants as $etudiant) {
    //         foreach ($etudiant->getContrats() as $contrat) {
    //             $manager->remove($contrat);
    //         }
    //     }

    //     foreach ($classe->getModules() as $m) {
    //         foreach ($m->getECModules() as $ecModule) {
    //             $manager->remove($ecModule);
    //         }
    //         $manager->remove($m);
    //     }

    //     $manager->flush();

    //     die("FAIT");
    // }
}
