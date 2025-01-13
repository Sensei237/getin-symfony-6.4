<?php
namespace App\Service;

use Exception;
use App\Entity\EC;
use App\Entity\Classe;
use App\Entity\Examen;
use App\Entity\Module;
use App\Entity\Contrat;
use App\Entity\Employe;
use App\Entity\Filiere;
use App\Entity\Service;
use App\Entity\Anonymat;
use App\Entity\ECModule;
use App\Entity\Etudiant;
use App\Entity\Paiement;
use App\Entity\Formation;
use App\Entity\Specialite;
use App\Entity\PaiementClasse;
use App\Entity\TypeDePaiement;
use App\Service\EtudiantUtils;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use App\Repository\ContratRepository;
use App\Repository\ECModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Permet de gerer toutes les importations de fichiers (Excel)
 * @author : MBOZO'O METOU'OU Emmanuel Beranger Alias Sensei emmaberanger2@gmail.com
 */
class ImportUtils
{
    /**
     * Les valeurs possibles pour le parametre type sont : 
     *  - e : pour les etudiants 
     *  - p : pour les programmes academiques
     *  - n : pour les notes 
     *  - a : pour les anonymats
     */
    public function doImportation(EntityManagerInterface $entityManager, $form, $type, AnneeAcademique $annee, $sampleDirectory, TypeDePaiement $tp=null)
    {
        $originalName = $_FILES['upload_program']['name']['fichier'];
        $parts = explode('.', $originalName);
        $extension = $parts[count($parts)-1];
        if ($extension != 'xlsx') {
            throw new Exception("Vous devez selectionner un fichier excel avec l'extension .xlsx");
            
        }
        $flashAlert = '';
        $file = $form->getData()['fichier'];
        $filename = md5(uniqid()).'.xlsx';
        try {
            $file->move($sampleDirectory, $filename);
        } catch (FileException $e) {
            die($e->getMessage());
        }
        $inputFileName = $sampleDirectory.'/'.$filename;
        $inputFileType = trim(strtoupper($extension)) == 'XLSX' ? 'Xlsx' : 'Csv';
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);

        $worksheetData = $reader->listWorksheetInfo($inputFileName);
        
        if ($type == 'personnel' || $type == 'services' || $type == 'classes' || $type == 'filieres' || $type == 'specialites') {
            $flashAlert = $this->import($worksheetData, $reader, $inputFileName, $entityManager, $entityManager, $type);
            $entityManager->flush();
            $entityManager->clear();
        }elseif ($type == 'quitus') {
            $flashAlert = $this->importerQuitus($worksheetData, $annee, $reader, $inputFileName, $entityManager, $entityManager, $tp);
        }
        else {
            // die(var_dump("dfkdkj ".$form->getData()['hasMatricule']));
            $genererMatricules = empty($form->getData()['hasMatricule'])?true:false;
            // on parcourt la liste des feuilles de notre fichier sachant que chaque feuille est un programme
            foreach ($worksheetData as $worksheet) {
                $sheetName = $worksheet['worksheetName'];
                if ($worksheet['totalRows'] > 4) {
                    $reader->setLoadSheetsOnly($sheetName);
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($inputFileName);
                    $sheet = $spreadsheet->getActiveSheet();
                    $flashAlert .= $this->saveData($type, $sheet, $sheetName, $entityManager, $worksheet['totalRows'], $annee, $entityManager, $genererMatricules);
                }
            }
        }
        if (file_exists($inputFileName)) {
            unlink($inputFileName);
        }
        
        return $flashAlert;
    }

    private function importerQuitus($worksheetData, AnneeAcademique $annee, $reader, $inputFileName, EntityManagerInterface $manager, EntityManagerInterface $doctrine, TypeDePaiement $tp)
    {
        $flashAlert = '';
        
        foreach ($worksheetData as $worksheet) {
            $sheetName = $worksheet['worksheetName'];
            if ($worksheet['totalRows'] > 2) {
                $reader->setLoadSheetsOnly($sheetName);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($inputFileName);
                $sheet = $spreadsheet->getActiveSheet();
                for ($i=3; $i <=$worksheet['totalRows']; $i++) {
                    $matricule = $sheet->getCell('A'.$i)->getValue();
                    $fullName = $sheet->getCell('B'.$i)->getValue();
                    $quitus1 = $sheet->getCell('C'.$i)->getValue();
                    $quitus2 = $sheet->getCell('D'.$i)->getValue();
                    $etudiant = $doctrine->getRepository(Etudiant::class)->findOneBy(['matricule'=>$matricule]);
                    
                    if ($quitus1 || $quitus2) {
                        if ($etudiant) {
                            $inscris = $doctrine->getRepository(EtudiantInscris::class)->findOneBy(['etudiant'=>$etudiant, 'anneeAcademique'=>$annee]);
                            if ($inscris) {
                                $tpc = $doctrine->getRepository(PaiementClasse::class)->findOneBy(['classe'=>$inscris->getClasse(), 'typeDePaiement'=>$tp]);
                                if ($tpc) {
                                    foreach ($tpc->getTranches() as $t) {
                                        if (mb_strtolower($t->getDenomination()) == 'tranche 1' || mb_strtolower($t->getDenomination()) == 'tranche 2') {
                                            $numeroQuitus = null;
                                            if (mb_strtolower($t->getDenomination()) == 'tranche 1' && !empty($quitus1)) {
                                                $numeroQuitus = $quitus1;
                                            }elseif (mb_strtolower($t->getDenomination()) == 'tranche 2' && !empty($quitus2)) {
                                                $numeroQuitus = $quitus2;
                                            }

                                            if ($numeroQuitus) {
                                                $paiement = $doctrine->getRepository(Paiement::class)->findOneBy(['etudiantInscris'=>$inscris, 'tranche'=>$t]);
                                                if (!$paiement) {
                                                    $paiement = new Paiement();
                                                    $paiement->setIsPaied(false);
                                                }

                                                $paiement->setEtudiantInscris($inscris)
                                                        ->setTranche($t)
                                                        ->setNumeroQuitus($numeroQuitus);

                                                $manager->persist($paiement);
                                            }    
                                        }
                                    }
                                }
                            }else {
                                $flashAlert .= "La feuille ".$sheetName.", ligne $i, l'etudiant dont le matricule est $matricule n'est pas inscris pour cette année académique<br>";
                            }
                        }else {
                            $flashAlert .= "La feuille ".$sheetName.", ligne $i, l'etudiant dont le matricule est $matricule n'existe pas<br>";
                        }
                    }else {
                        $flashAlert .= "La feuille ".$sheetName.", ligne $i, l'etudiant n'a pas de quitus<br>";
                    }   
                }
            }else {
                $flashAlert .= "La feuille ".$sheetName." n'est pas au bon format<br>";
            }
        }

        return $flashAlert;
    }

    private function import($worksheetData, $reader, $inputFileName, $manager, $doctrine, $type='services')
    {
        $flashAlert = '';

        foreach ($worksheetData as $worksheet) {
            $sheetName = $worksheet['worksheetName'];
            $reader->setLoadSheetsOnly($sheetName);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($inputFileName);
            $sheet = $spreadsheet->getActiveSheet();
            if ($type == 'services') {
                for ($i=2; $i <=$worksheet['totalRows']; $i++) {
                    // dump($sheet->getCell('A'.$i)->getValue());die();
                    $service = $doctrine->getRepository(Service::class)->findOneBy(['code'=>$sheet->getCell('B'.$i)->getValue()]);
                    if (!$service && !empty($sheet->getCell('A'.$i)->getValue()) && !empty($sheet->getCell('B'.$i)->getValue())) {
                        $service = new Service();
                        $service->setNom($sheet->getCell('A'.$i)->getValue())
                                ->setCode($sheet->getCell('B'.$i)->getValue())
                                ->setSlug($sheet->getCell('A'.$i)->getValue());
                        // dump($service);die();
                        $manager->persist($service);
                    }else {
                        $flashAlert .= "Ligne ".$i." de la feuille ".$sheetName .", le nom et le code du service sont obligatoires ou le service dont le code est ".$sheet->getCell('B'.$i)->getValue()." existe déjà<br>";
                    }
                }
            }
            elseif ($type == 'classes' || $type == 'filieres' || $type == 'specialites') {
                for ($i=2; $i <=$worksheet['totalRows']; $i++) {
                    $object = new Filiere();
                    if ($type == 'filieres') {
                        $object = $doctrine->getRepository(Filiere::class)->findOneBy(['code'=>strtoupper($sheet->getCell('B'.$i)->getValue())]);
                        if (!$object) {
                            $object = new Filiere();
                        }
                        $object->setName($sheet->getCell('A'.$i)->getValue())
                                ->setCode($sheet->getCell('B'.$i)->getValue())
                                ->setSlug($sheet->getCell('A'.$i)->getValue())
                                ->setLettrePourLeMatricule($sheet->getCell('C'.$i)->getValue());
                    }
                    elseif ($type == 'specialites') {
                        $filiere = $doctrine->getRepository(Filiere::class)->findOneBy(['code'=>strtoupper($sheet->getCell('A'.$i)->getValue())]);
                        if ($filiere) {
                            $object = $doctrine->getRepository(Specialite::class)->findOneBy(['code'=>strtoupper($sheet->getCell('C'.$i)->getValue())]);
                            if (!$object) {
                                $object = new Specialite();
                            }
                            $object->setFiliere($filiere)
                                   ->setName($sheet->getCell('B'.$i)->getValue())
                                   ->setCode($sheet->getCell('C'.$i)->getValue())
                                   ->setLetterMatricule($sheet->getCell('D'.$i)->getValue())
                                   ->setSlug($sheet->getCell('B'.$i)->getValue());
                            if (is_numeric($sheet->getCell('E'.$i)->getValue()) && is_numeric($sheet->getCell('F'.$i)->getValue()) && !empty($sheet->getCell('G'.$i)->getValue())) {
                                $min = (int) $sheet->getCell('E'.$i)->getValue();
                                $max = (int) $sheet->getCell('F'.$i)->getValue();
                                // dump([$min, $max]); die();
                                if ($max > $min) {
                                    $format = $doctrine->getRepository(Formation::class)->findOneBy(['code'=>strtoupper($sheet->getCell('G'.$i)->getValue())]);
                                    if ($format) {
                                        for ($i=$min; $i<= $max; $i++) {
                                            $codeClasse = $sheet->getCell('C'.$i)->getValue().$i;
                                            $classeName = $sheet->getCell('B'.$i)->getValue().' '.$i;
                                            $classe = $doctrine->getRepository(Classe::class)->findOneBy(['code'=>strtoupper($codeClasse)]);
                                            if (!$classe) {
                                                $classe = new Classe();
                                            }
                                            $classe->setFormation($format)
                                                    ->setNom($classeName)
                                                    ->setNiveau($i)
                                                    ->setSlug($classeName)
                                                    ->setCode($codeClasse)
                                                    ->setSpecialite($object);
                                            $manager->persist($classe);
                                        }
                                    }
                                }
                            }
                            
                        }
                    }elseif ($type == 'classes') {
                        $specialite = $doctrine->getRepository(Specialite::class)->findOneBy(['code'=>strtoupper($sheet->getCell('A'.$i)->getValue())]);
                        if ($specialite) {
                            $formation = $doctrine->getRepository(Formation::class)->findOneBy(['code'=>strtoupper($sheet->getCell('B'.$i)->getValue())]);
                            if ($formation) {
                                $object = $doctrine->getRepository(Classe::class)->findOneBy(['code'=>strtoupper($sheet->getCell('D'.$i)->getValue())]);
                                if (!$object) {
                                    $object = new Classe();
                                }
                                $object->setSpecialite($specialite)
                                       ->setFormation($formation)
                                       ->setNom($sheet->getCell('C'.$i)->getValue())
                                       ->setCode($sheet->getCell('D'.$i)->getValue())
                                       ->setNiveau($sheet->getCell('E'.$i)->getValue())
                                       ->setSlug($sheet->getCell('C'.$i)->getValue());
                            }
                        }
                    }

                    $manager->persist($object);
                }
            }
            elseif ($type == 'personnel') {
                for ($i=2; $i <=$worksheet['totalRows']; $i++) {
                    $service = $doctrine->getRepository(Service::class)->findOneBy(['code'=>$sheet->getCell('N'.$i)->getValue()]);
                    if ($service && !empty($sheet->getCell('A'.$i)->getValue()) && !empty($sheet->getCell('C'.$i)->getValue()) && !empty($sheet->getCell('D'.$i)->getValue()) && 
                        !empty($sheet->getCell('E'.$i)->getValue()) && !empty($sheet->getCell('F'.$i)->getValue()) && 
                        !empty($sheet->getCell('J'.$i)->getValue()) && !empty($sheet->getCell('K'.$i)->getValue())) {
                        $employe = new Employe();
                        $employe->setNom($sheet->getCell('A'.$i)->getValue())
                                ->setPrenom($sheet->getCell('B'.$i)->getValue())
                                ->setDateDeNaissance($this->convertToDate($sheet->getCell('C'.$i)->getValue()))
                                ->setLieuDeNaissance($sheet->getCell('D'.$i)->getValue())
                                ->setSexe($sheet->getCell('E'.$i)->getValue())
                                ->setTelephone($sheet->getCell('F'.$i)->getValue())
                                ->setTelephone2($sheet->getCell('G'.$i)->getValue())
                                ->setAdresseEmail($sheet->getCell('H'.$i)->getValue())
                                ->setGrade($sheet->getCell('I'.$i)->getValue())
                                ->setSituationMatrimoniale($sheet->getCell('J'.$i)->getValue())
                                ->setNombreDEnfants($sheet->getCell('K'.$i)->getValue())
                                ->setNomConjoint($sheet->getCell('L'.$i)->getValue())
                                ->setTelephoneConjoint($sheet->getCell('M'.$i)->getValue())
                                ->setService($service)
                                ->setReference($sheet->getCell('A'.$i)->getValue())
                                ->setIsVisible(true)
                                ->setIsGone(false)
                        ;

                        $manager->persist($employe);
                    }else {
                        $flashAlert .= "Ligne ". $i." de la feuille ". $sheetName.", le nom, la date de naissance, le sexe, le numero de telephone, le nombre d'enfants et la situation matrimoniale sont obligatoires<br>";
                    }
                }
            }
        }
    }

    private function saveData($type, $sheet, $sheetName, EntityManagerInterface $manager, $totalRows, AnneeAcademique $annee, EntityManagerInterface $doctrine, bool $genererMatricules=false)
    {
        $flashAlert = '';
        $codeClasse = $sheet->getCell('B2')->getValue();
        $classe = $doctrine->getRepository(Classe::class)->findOneBy(['code'=>$codeClasse]);
        if ($classe) {
            // On va commencer à enregistrer les données de la feuille en fonction du type. 
            if (method_exists ($this, 'manage'.strtoupper($type))) {
                switch (strtoupper($type)) {
                    case 'E':
                        // On importe les etudiants
                        $flashAlert .= $this->manageE($sheet, $doctrine, $classe, $sheetName, $manager, $totalRows, $annee, $genererMatricules);
                        break;
                    case 'P':
                        // On importe les programmes
                        $flashAlert .= $this->manageP($sheet, $doctrine, $classe, $manager, $totalRows, $annee);
                        break;
                    case 'N':
                        // On importe les notes des etudiants
                        $flashAlert .= $this->manageN($sheet, $doctrine, $classe, $manager, $totalRows, $annee);
                        break;
                    case 'A':
                        // On importe les anonymats des etudiants
                        $examen = $doctrine->getRepository(Examen::class)->findOneBy(['code'=>$sheet->getCell('C2')->getValue()]);
                        if ($examen) {
                            $flashAlert .= $this->manageA($sheet, $totalRows, $annee, $examen, $classe, $doctrine, $manager);
                        }else {
                            $flashAlert .= "Feuille ".$sheetName.". L'examen dont le code est ".$sheet->getCell('C2')->getValue()." n'existe pas !<br>";
                        }
                        break;
                }
            }
        }else {
            $flashAlert .= "Feuille ".$sheetName." La classe ".$codeClasse." n'existe pas <br>";
        }
        return $flashAlert;
    }

    private function convertToDate($arg)
    {
        $date = new \DateTime();
        if (is_numeric($arg)) {
            // On va convertir l'entier en une date 
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($arg);
        }
        else {
            $str = explode('/', $arg);
            if (count($str) == 3) {
                $date = new \DateTime(trim($str[2]).'-'.trim($str[1]).'-'.trim($str[0]).'');
            }
        }

        // dump(date_format($date, 'd/m/Y'));die();

        return $date;
    }

    /**
     * Fonction permettant d'importer les etudiants
     */
    private function manageE($sheet, EntityManagerInterface $doctrine, Classe $classe, $sheetName, EntityManagerInterface $manager, $totalRows, AnneeAcademique $annee, bool $genererMatricules=false)
    {
        $flashAlert = '';
        $etudiantUtils = new EtudiantUtils();
        for ($i=5; $i <=$totalRows; $i++) { 
            if ($genererMatricules) {
                // On considère que dans le fichier, les etudiants n'ont pas de matricules donc la colone A contient le nom de l'étudiant
                $etudiant = new Etudiant();
                $etudiant->setNom($sheet->getCell('A'.$i)->getValue())
                         ->setPrenom($sheet->getCell('B'.$i)->getValue())
                         ->setDateDeNaissance($this->convertToDate($sheet->getCell('C'.$i)->getValue()))
                         ->setLieuDeNaissance($sheet->getCell('D'.$i)->getValue())
                         ->setSexe($sheet->getCell('E'.$i)->getValue())
                         ->setTelephone1($sheet->getCell('F'.$i)->getValue())
                         ->setTelephone2($sheet->getCell('G'.$i)->getValue())
                         ->setAdresseEmail($sheet->getCell('H'.$i)->getValue())
                         ->setNombreDEnfants($sheet->getCell('I'.$i)->getValue())
                         ->setSituationMatrimoniale($sheet->getCell('J'.$i)->getValue())
                         ->setCivilite($sheet->getCell('K'.$i)->getValue())
                         ->setDiplomeAcademiqueMax($sheet->getCell('L'.$i)->getValue())
                         ->setAnneeObtentionDiplomeAcademiqueMax($sheet->getCell('M'.$i)->getValue())
                         ->setDiplomeDEntre($sheet->getCell('N'.$i)->getValue())
                         ->setAnneeObtentionDiplomeEntre($sheet->getCell('O'.$i)->getValue())
                         ->setNomDuPere($sheet->getCell('P'.$i)->getValue())
                         ->setNumeroDeTelephoneDuPere($sheet->getCell('Q'.$i)->getValue())
                         ->setProfessionDuPere($sheet->getCell('R'.$i)->getValue())
                         ->setNomDeLaMere($sheet->getCell('S'.$i)->getValue())
                         ->setNumeroDeTelephoneDeLaMere($sheet->getCell('T'.$i)->getValue())
                         ->setProfessionDeLaMere($sheet->getCell('U'.$i)->getValue())
                         ->setAdresseDesParents($sheet->getCell('V'.$i)->getValue())
                         ->setPersonneAContacterEnCasDeProbleme($sheet->getCell('W'.$i)->getValue())
                         ->setNumeroDUrgence($sheet->getCell('X'.$i)->getValue())
                        ;
                $inscription = new EtudiantInscris();
                $isRedoublant = strtoupper($sheet->getCell('Y'.$i)->getValue()) == 'REDOUBLANT' || strtoupper($sheet->getCell('Y'.$i)->getValue()) == 'REDOUBLE' || strtoupper($sheet->getCell('Y'.$i)->getValue()) == 'O' || strtoupper($sheet->getCell('Y'.$i)->getValue()) == 'R' ? true : false;
                $inscription->setAnneeAcademique($annee)
                            ->setClasse($classe)
                            ->setEtudiant($etudiant)
                            ->setIsRedoublant($isRedoublant);
                $manager->persist($inscription);
                $etudiantUtils->genererMatricule($annee, $inscription, $doctrine);
            }else {
                // Les fichier contient déjà les matricules des etudiants. C'est la colone A
                $flashAlert .= $this->ajouterUnEtudiantAyantLeMatricule($sheet, $i, $annee, $classe, $doctrine, $manager);
            }
            // On fait une materialisation dans la base de données. 
            $manager->flush();
        }
        return trim($flashAlert);
    }

    /**
     * Cette fonction va soit ajouter l'etudiant s'il ne figure pas encore dans le systeme
     * Soit modifier ses donnee s'il existe déjà. 
     * Dans ce cas precis on a deux hypotheses
     *  - Soit l'étudiant existe déjà au quel cas il faudra verifier s'il est déjà inscrit ou pas
     *  - Soit l'étudiant n'existe pas dans ce cas, on l'inscrit normalement
     */
    private function ajouterUnEtudiantAyantLeMatricule($sheet, int $i, AnneeAcademique $annee, Classe $classe, EntityManagerInterface $doctrine, EntityManagerInterface $manager)
    {
        $flashAlert = '';
        if ($sheet->getCell('A'.$i)->getValue() && trim($sheet->getCell('A'.$i)->getValue()) != '') {
            $etudiant = $doctrine->getRepository(Etudiant::class)->findOneBy(['matricule'=>$sheet->getCell('A'.$i)->getValue()]);
            if (!$etudiant) {
                $etudiant = new Etudiant();
                $etudiant->setMatricule(trim($sheet->getCell('A'.$i)->getValue()));
            }
            // On fait la mise a jour des informations concernant les etudiants.
            $etudiant->setNom($sheet->getCell('B'.$i)->getValue())
                    ->setPrenom($sheet->getCell('C'.$i)->getValue())
                    ->setDateDeNaissance($this->convertToDate($sheet->getCell('D'.$i)->getValue()))
                    ->setLieuDeNaissance($sheet->getCell('E'.$i)->getValue())
                    ->setSexe($sheet->getCell('F'.$i)->getValue())
                    ->setTelephone1($sheet->getCell('G'.$i)->getValue())
                    ->setTelephone2($sheet->getCell('H'.$i)->getValue())
                    ->setAdresseEmail($sheet->getCell('I'.$i)->getValue())
                    ->setNombreDEnfants($sheet->getCell('J'.$i)->getValue())
                    ->setSituationMatrimoniale($sheet->getCell('K'.$i)->getValue())
                    ->setCivilite($sheet->getCell('L'.$i)->getValue())
                    ->setDiplomeAcademiqueMax($sheet->getCell('M'.$i)->getValue())
                    ->setAnneeObtentionDiplomeAcademiqueMax($sheet->getCell('N'.$i)->getValue())
                    ->setDiplomeDEntre($sheet->getCell('O'.$i)->getValue())
                    ->setAnneeObtentionDiplomeEntre($sheet->getCell('P'.$i)->getValue())
                    ->setNomDuPere($sheet->getCell('Q'.$i)->getValue())
                    ->setNumeroDeTelephoneDuPere($sheet->getCell('R'.$i)->getValue())
                    ->setProfessionDuPere($sheet->getCell('S'.$i)->getValue())
                    ->setNomDeLaMere($sheet->getCell('T'.$i)->getValue())
                    ->setNumeroDeTelephoneDeLaMere($sheet->getCell('U'.$i)->getValue())
                    ->setProfessionDeLaMere($sheet->getCell('V'.$i)->getValue())
                    ->setAdresseDesParents($sheet->getCell('W'.$i)->getValue())
                    ->setPersonneAContacterEnCasDeProbleme($sheet->getCell('X'.$i)->getValue())
                    ->setNumeroDUrgence($sheet->getCell('Y'.$i)->getValue())
                ;
            $inscription = $doctrine->getRepository(EtudiantInscris::class)->findOneBy(['etudiant'=>$etudiant, 'anneeAcademique'=>$annee]);
            if (!$inscription) {
                $inscription = new EtudiantInscris();
            }
            $isRedoublant =  strtoupper($sheet->getCell('Z'.$i)->getValue()) == 'R' || strtoupper($sheet->getCell('Z'.$i)->getValue()) == 'REDOUBLANT' || strtoupper($sheet->getCell('Z'.$i)->getValue()) == 'REDOUBLE' || strtoupper($sheet->getCell('Z'.$i)->getValue()) == 'O' ? true : false;
            $inscription->setAnneeAcademique($annee)
                        ->setClasse($classe)
                        ->setEtudiant($etudiant)
                        ->setIsRedoublant($isRedoublant);
            $manager->persist($inscription);
        }else {
            $flashAlert = "Dans la liste de la classe ".$classe->getCode()." à la ligne <b>$i</b>, le matricule n'existe <br>";
        }
        return $flashAlert;
    }

    public function isValidProgramme($sheet, $totalRows, Classe $classe)
    {
        $hasError = false; $msg = '';
        for ($i=5; $i<=$totalRows; $i++) {
            if (empty($sheet->getCell('A'.$i)->getValue()) 
            || empty($sheet->getCell('B'.$i)->getValue()) 
            || empty($sheet->getCell('C'.$i)->getValue()) 
            || empty($sheet->getCell('D'.$i)->getValue()) 
            || empty($sheet->getCell('E'.$i)->getValue()) 
            || empty($sheet->getCell('F'.$i)->getValue())) {
                $hasError = true;
                $msg .= "Le programme de ". $classe->getNom()." a une erreur à la ligne ".$i.'; ';
            }
        }
        return ['hasError'=>$hasError, 'msg'=>$msg.'<br>'];
    }

    /**
     * Fonction permettant d'importer les programmes academiques
     */
    private function manageP($sheet, EntityManagerInterface $doctrine, Classe $classe, EntityManagerInterface $manager, $totalRows, AnneeAcademique $annee)
    {
        $flashAlert = NULL;
        $modules = $doctrine->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
        $isValid = $this->isValidProgramme($sheet, $totalRows, $classe);
        $flashAlert = $isValid['msg'];
        if (!$isValid['hasError']) {
            if (!$modules) {
                // La classe n'a pas encore de progamme academique
                $lastCodeModule = '';
                $lastModule=null;
                for ($i=5; $i<=$totalRows; $i++) {
                    $nomModule = $sheet->getCell('A'.$i)->getValue();
                    $codeModule = $sheet->getCell('B'.$i)->getValue();
                    $nomModule = trim($nomModule);
                    $codeModule = trim($codeModule);
                    if ($lastCodeModule != $codeModule || !$lastModule) {
                        $module = $doctrine->getRepository(Module::class)->findOneBy(['classe'=>$classe, 'anneeAcademique'=>$annee, 'code'=>$codeModule]);
                        if ($module == null) {
                            $module = new Module();
                            $module->setSlug($nomModule.'-'.time()+rand(0, 200000000))
                                ->setClasse($classe)
                                ->setAnneeAcademique($annee)
                                ->setIntitule($nomModule)
                                ->setCode($codeModule);
                            $manager->persist($module);
                        }
                        $lastModule = $module;
                        $lastCodeModule = $codeModule;
                    } else {
                        $module = $lastModule;
                    }
                    $nomEC = trim(\mb_strtoupper($sheet->getCell('C'.$i)->getValue(), 'UTF8'));
                    $codeEC = trim(strtoupper($sheet->getCell('D'.$i)->getValue()));
                    $credit = trim($sheet->getCell('E'.$i)->getValue());
                    $semestre = trim($sheet->getCell('F'.$i)->getValue());
                    $ec = $doctrine->getRepository(EC::class)->findOneBy(['code'=>$codeEC]);
                    if (!$ec) {
                        $ec = new EC();
                        $ec->setCode($codeEC)
                            ->setIntitule($nomEC)
                            ->setSlug($nomEC.'-'.time()+rand(10000, 2000000000));
                        $manager->persist($ec);
                    }
                    $ecModule = new ECModule();
                    $ecModule->setModule($module)
                                ->setEc($ec)
                                ->setCredit($credit)
                                ->setSemestre($semestre);
                    $manager->persist($ecModule);
                }
            } else {
                $flashAlert = "La classe ".$classe->getNom()." a déjà un programme académique !<br>";
            }
        }
        return $flashAlert;
    }

    /**
     * Cette fonction permet de gerer l'importation des notes des etudiants
     */
    private function manageN($sheet, EntityManagerInterface $doctrine, Classe $classe, EntityManagerInterface $manager, $totalRows, AnneeAcademique $annee)
    {
        $codeEC = $sheet->getCell('D1')->getValue();
        $ec = $doctrine->getRepository(EC::class)->findOneBy(['code'=>$codeEC]);
        if (!$ec) {
            return "L'EC donc le code est ".$codeEC." n'existe pas !";
        }

        $ecModuleRepository = $doctrine->getRepository(ECModule::class);
        $contratRepository = $doctrine->getRepository(Contrat::class);
        if (!$ecModuleRepository instanceof ECModuleRepository || !$contratRepository instanceof ContratRepository) {
            return;
        }

        $ecModule = $ecModuleRepository->findOneByYearClasseAndEc($annee, $classe, $ec);
        if (!$ecModule) {
            return "L'EC donc le code est ".$codeEC." ne figure pas dans le programme academique de la classe ".$classe->getCode()." pour cette année !";
        }
        $contrats = $contratRepository->findContratsClasseForEC($annee, $ec, $classe);
        if (!$contrats) {
            return "Aucun contrat trouvé pour l'EC ".$codeEC." dans la classe ".$classe->getCode();
        }
        $msg = '';
        for ($i=5; $i<=$totalRows; $i++) {
            $existe = false;
            $matricule = $sheet->getCell('A'.$i)->getValue();
            foreach ($contrats as $key => $c) {
                if (strtoupper($matricule) == strtoupper($c->getEtudiantInscris()->getEtudiant()->getMatricule())) {
                    $noteCC = $sheet->getCell('C'.$i)->getValue();
                    $noteSN = $sheet->getCell('D'.$i)->getValue();
                    $noteSR = $sheet->getCell('E'.$i)->getValue();
                    $noteTP = $sheet->getCell('F'.$i)->getValue();
                    $noteTPE = $sheet->getCell('G'.$i)->getValue();
                    // $c->setNoteCC($noteCC)->setNoteSN($noteSN)->setNoteSR($noteSR);
                    if (!empty($noteCC) && is_numeric($noteCC) && $noteCC <= 20 && $noteCC >= 0) {
                        $c->setNoteCC($noteCC);
                    }
                    if (!empty($noteSN) && is_numeric($noteSN) && $noteSN <= 20 && $noteSN >= 0) {
                        $c->setNoteSN($noteSN);
                    }
                    if (!empty($noteSR) && is_numeric($noteSR) && $noteSR <= 20 && $noteSR >= 0) {
                        $c->setNoteSR($noteSR);
                    }
                    if (!empty($noteTP) && is_numeric($noteTP) && $noteTP <= 20 && $noteTP >= 0) {
                        $c->setNoteTP($noteTP);
                    }
                    if (!empty($noteTPE) && is_numeric($noteTPE) && $noteTPE <= 20 && $noteTPE >= 0) {
                        $c->setNoteTPE($noteSR);
                    }
                    $existe = true;
                    unset($contrats[$key]);
                    break;
                }
            }
            if (!$existe) {
                $msg .= "L'etudiant donc le matricule est ".$matricule." n'a pas été retrouvé pour la matière ".$ec->getIntitule()."<br>";
            }
        }
        return $msg;
    }

    /**
     * Cette fonction permet d'importer les anonymats
     */
    private function manageA($sheet, $totalRows, AnneeAcademique $annee, Examen $examen, Classe $classe, EntityManagerInterface $doctrine,  EntityManagerInterface $manager)
    {
        $ecModuleRepository = $doctrine->getRepository(ECModule::class);
        $contratRepository = $doctrine->getRepository(Contrat::class);
        if (!$ecModuleRepository instanceof ECModuleRepository || !$contratRepository instanceof ContratRepository) {
            return;
        }

        $codeEC = $sheet->getCell('D1')->getValue();
        $ec = $doctrine->getRepository(EC::class)->findOneBy(['code'=>$codeEC]);
        if (!$ec) {
            return "L'EC donc le code est ".$codeEC." n'existe pas !";
        }
        $ecModule = $ecModuleRepository->findOneByYearClasseAndEc($annee, $classe, $ec);
        if (!$ecModule) {
            return "L'EC donc le code est ".$codeEC." ne figure pas dans le programme academique de la classe ".$classe->getCode()." pour cette année !";
        }
        $contrats = $contratRepository->findContratsClasseForEC($annee, $ec, $classe);
        if (!$contrats) {
            return "Aucun contrat trouvé pour l'EC ".$codeEC." dans la classe ".$classe->getCode();
        }
        $msg = '';
        for ($i=5; $i<=$totalRows; $i++) {
            $existe = false;
            $matricule = $sheet->getCell('A'.$i)->getValue();
            foreach ($contrats as $key => $c) {
                if (strtoupper($matricule) == strtoupper($c->getEtudiantInscris()->getEtudiant()->getMatricule())) {
                    $code = $sheet->getCell('C'.$i)->getValue();
                    $anonymat = new Anonymat();
                    $anonymat->setContrat($c)->setAnonymat($code)->setExamen($examen);
                    $manager->persist($anonymat);
                    $existe = true;
                    unset($contrats[$key]);
                    break;
                }
                if (!$existe) {
                    $msg .= "L'etudiant donc le matricule est ".$matricule." n'a pas été retrouvé pour la matière<br>";
                }
            }
        }

        return $msg;
    }

}
