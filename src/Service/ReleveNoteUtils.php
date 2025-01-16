<?php
namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

use App\Entity\Examen;
use App\Entity\Module;
use App\Entity\Contrat;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use App\Entity\SyntheseModulaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Permet de gerer le processus de calcul et la generation des releves de notes des etudiants
 * @author : MBOZO'O METOU'OU Emmanuel Beranger Alias Sensei emmaberanger2@gmail.com
 */
class ReleveNoteUtils
{
    private $twig;
    private $encoders;
    private $normalizers;
    private $serializer;

    private $creditsAcquisSemestre1;
    private $creditsAcquisSemestre2;
    private $annee;
    private EntityManagerInterface $managerRegistry;
    private EntityManagerInterface $objectManager;

    public function __construct(Environment $twig, EntityManagerInterface $managerRegistry)
    {
        $this->twig = $twig;
        $this->managerRegistry = $managerRegistry;
        $this->objectManager = $managerRegistry;

        $this->creditsAcquisSemestre1 = $this->creditsAcquisSemestre2 = 0;

        $this->encoders = [new XmlEncoder(), new JsonEncoder()];
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getId();
            },
        ];
        $this->normalizers = [new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)];
        $this->serializer = new Serializer($this->normalizers, $this->encoders);
    }

    public function calculerNotesModule(Array $inscris, AnneeAcademique $annee, ?int $semestre=null, $save=true)
    {
        $data = []; $synthesesModulaires = [];
        foreach ($inscris as $ins) {
            $cmResult = $this->getContratsByModule($ins, $semestre);
            if ($semestre == 1) {
                $ins->setCreditAcquisSemestre1($this->creditsAcquisSemestre1);
            }elseif($semestre == 2){
                $ins->setCreditAcquisSemestre2($this->creditsAcquisSemestre2);
            }
            
            $synthesesModulaires[] = $cmResult['synthesesModulaires'];
            $d = [
                'inscri' => $ins->getAsArray(),
                'contratsByModule' => $cmResult['contratsByModule'],
            ];
            // dump($d);die();
            $data[] = $d;
            if ($save) {
                $jsonData = json_encode($d);
                // $jsonData = $this->serializer->serialize($d, 'json');
                // header('content-type:application/json');
                // dump($jsonData);die();
                if (!$semestre) {
                    $ins->setNotesAnnuelle($jsonData);
                } elseif ($semestre == 1) {
                    $ins->setNotesSemestre1($jsonData);
                } else {
                    $ins->setNotesSemestre2($jsonData);
                }
            }
            // dump($this->creditsAcquisSemestre1 . ' ~ '. $this->creditsAcquisSemestre2);
            $this->creditsAcquisSemestre2 = $this->creditsAcquisSemestre1 = 0;
        }
        return ['data' => $data, 'synthesesModulaires' => $synthesesModulaires];
    }

    private function getReleveFields(Array $etudiants, int $semestre=null)
    {
        $data = [];
        foreach ($etudiants as $et) {
            if ($et instanceof EtudiantInscris) {
                $d = [];
                if (!$semestre) {
                    $d = json_decode($et->getNotesAnnuelle(), true);
                } elseif ($semestre == 1) {
                    $d = json_decode($et->getNotesSemestre1(), true);
                } else {
                    $d = json_decode($et->getNotesSemestre2(), true);
                }
                // header('content-type:application/json');dump($d);die();
                if (!empty($d)) {
                    $data[] = $d;
                }
            }
        }

        return $data;
    }

    public function genererReleves(Array $inscris, AnneeAcademique $annee, $inPDF=false, $htmlFile='releves.html.twig', $semestre=null, ?string $filename=null)
    {
        // die(var_dump($inscris));
        // dump($this->getReleveFields($inscris, $semestre));die();
        $html = $this->twig->render('note/'.$htmlFile, [
            'data' => $this->getReleveFields($inscris, $semestre),
            'annee' => $annee->getAsArray(),
            'semestre' => $semestre,
            'inPDF' => $inPDF,
            'logoUniversityBase64' => $annee->getConfiguration()->getLogoUniversityBase64(),
            'logoEcoleBase64' => $annee->getConfiguration()->getLogoEcoleBase64(),
            'filigraneBase64' => $annee->getConfiguration()->getFiligrane(),
        ]);
        if (!$inPDF) {
            return $html;
        }

        $pdfOptions = new Options();
        $pdfOptions->setDefaultFont('Arial');
        $pdfOptions->setIsRemoteEnabled(true);
        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        
        $fileName = $filename;
        $dompdf->stream($fileName);
        $output = $dompdf->output();
        // file_put_contents($fileName, $output);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        // die($fileName);
        file_put_contents($temp_file, $output);

        return ['temp_file' => $temp_file, 'fileName' => $fileName];
    }

    private function getContratsByModule(EtudiantInscris $inscris, int $semestre=null)
    {
        $contrats = $inscris->getContrats();
        
        $contratsByModule = [];
        $synthesesModulaires = [];
        // On ordonne les contrats academiques de l'etudiant par module
        foreach ($contrats as $contrat) {
            // if ($contrat->getEcModule()->getModule()->getClasse()->getId() == $inscris->getClasse()->getId()) {
            if (!$contrat->getIsDette()) {
                $actualModuleId = $contrat->getEcModule()->getModule()->getId();
                if ($semestre == $contrat->getEcModule()->getSemestre()) {
                    $contratsByModule[$actualModuleId]['contrats'][] = $contrat->getAsArray();
                    $contratsByModule[$actualModuleId]['module']['mod'] = $contrat->getEcModule()->getModule()->getAsArray();
                }
                elseif ($semestre === null) {
                    $contratsByModule[$actualModuleId]['contrats'][] = $contrat->getAsArray();
                    $contratsByModule[$actualModuleId]['module']['mod'] = $contrat->getEcModule()->getModule()->getAsArray();
                }
            }
        }
        // On calcule les notes du module
        $nbPointsTotal = 0; $nbCreditsTotal = 0;
        $moyenne = $decision = $mention = $grade = $noteSur4 = null;
        foreach ($contratsByModule as $key => $item) {
            $moduleValuesReturn = $this->getModuleValues($item, $inscris->getAnneeAcademique());
            $data = $moduleValuesReturn['data'];
            $item['module']['details'] = $data;
            $contratsByModule[$key]['module']['details'] = $data;
            $contratsByModule[$key]['contrats'] = $item['contrats'] = $moduleValuesReturn['contratActualises'];
            $nbPointsTotal += $data['pointsModule'];
            $nbCreditsTotal += $data['creditModule'];
            $synthesesModulaires[] = $data;
        }

        if ($nbCreditsTotal > 0) {
            $moyenne = number_format($nbPointsTotal/$nbCreditsTotal, 2);
        }

        // Il faut faire la mise a jour des contrats qui ont ete valides par compensation
        // avant de les enregistrer dans les champs qui gerent les releves de note. 
        $creditAcquis = null;
        if (!$semestre) {
            $inscris->setMoyenneObtenue($moyenne);
            $creditAcquis = $this->creditsAcquisSemestre1 + $this->creditsAcquisSemestre2;
        }elseif ($semestre == 1) {
            $inscris->setMoyenneSemestre1($moyenne);
            $creditAcquis = $this->creditsAcquisSemestre1;
        }
        else {
            $inscris->setMoyenneSemestre2($moyenne);
            $creditAcquis = $this->creditsAcquisSemestre2;
        }

        if ($moyenne) {
            $grade = $this->showGrade($moyenne*5);
            $noteSur4 = number_format($moyenne/5, 2);
            $mention = $this->getMention($moyenne);
            $decision = $this->getDecisionFinale($creditAcquis, $nbCreditsTotal);
        }

        return [
            'contratsByModule' => [
                'contratsByModule' => $contratsByModule, 
                'final' => [
                    'moyenne' => $moyenne,
                    'noteSur4' => $noteSur4,
                    'grade' => $grade,
                    'credit' => $creditAcquis,
                    'mention' => $mention,
                    'decision' => $decision,
                ],
            ],
            'synthesesModulaires' => $synthesesModulaires,
        ];

    }

    /**
     * - Cette fonction permet de calculer les notes sur un module de l'etudiant
     * - Elle permet de determiner les validations par compensation
     * - 
     */
    private function getModuleValues(Array $contratsByModuleItem, AnneeAcademique $annee)
    {
        $note = 0; $nb = 0; $creditModule = 0; $sessionValidation = 'N';
        $pointsModule = 0;
        $creditValider = 0; 
        $moduleId = null;
        $etudiantInscrisId = null;
        foreach ($contratsByModuleItem['contrats'] as $contrat) {
            // $note += $contrat->statsDef['moyenne'];
            // $nb++;
            $moduleId = $contrat['moduleId'];
            $etudiantInscrisId = $contrat['etudiantInscrisId'];
            $creditModule += $contrat['credit'];
            if (strtoupper($contrat['sessionValidation']) !== 'N') {
                $sessionValidation = $contrat['sessionValidation'];
            }
            $pointsModule += ($contrat['credit']*$contrat['moyDefinitive']);
            if ($contrat['isValidated']) {
                $creditValider += $contrat['credit'];
                if ($contrat['semestre'] == 1) {
                    $this->creditsAcquisSemestre1 += $contrat['credit'];
                }else {
                    $this->creditsAcquisSemestre2 += $contrat['credit'];
                }
            }
        }
        
        if ($creditModule > 0) {
            // on calcul la moyenne obtenue par l'etudiant pour ce module
            $note = $pointsModule/$creditModule;
            // $note = $note/$nb;
        }

        $dec = $this->showDecision($note, $annee->getConfiguration()->getNotePourValiderUnModule());

        if ($note && $note >= $annee->getConfiguration()->getNotePourValiderUnModule() && $dec == 'V') {
            // On cherche s'il y a des contrats qui ne sont pas valider mais dont la note est superieure à la note eliminatoire 
            // pour les mettre en VC (Valider par compensation)
            foreach ($contratsByModuleItem['contrats'] as $contrat) {
                $cont = $this->managerRegistry->getRepository(Contrat::class)->findOneBy(['id'=>$contrat['id']]);
                if ($cont) {
                    if (!$cont->getIsValidated() && $cont->getMoyDefinitive() > $annee->getConfiguration()->getNoteEliminatoire()) {
                        // dump($cont);die;
                        $cont->setIsValidated(true)
                            ->setDecision("VC")
                            ->setAnneeValidation($annee->getDenominationSlash());
                        $creditValider += $contrat['credit'];
                        if ($contrat['semestre'] == 1) {
                            $this->creditsAcquisSemestre1 += $contrat['credit'];
                        }else {
                            $this->creditsAcquisSemestre2 += $contrat['credit'];
                        }
                        // dump($cont);die;
                    }
                    elseif ($cont->getMoyDefinitive() <= $annee->getConfiguration()->getNoteEliminatoire()) {
                        // Si l'etudiant a une note eliminatoire sur une matiere alors tout le module est NV et on ne calcul ni la note sur 4 ni la moyenne
                        $cont->setIsValidated(false)->setDecision("NV");
                        $note = null;
                        $sessionValidation = null;
                        $dec = "NV";
                    }

                    $this->objectManager->flush();
                    // $this->objectManager->clear();
                }
            }
        }else {
            // Le module n'est pas validé
            $note = null;
            $sessionValidation = null;
        }
        $this->objectManager->flush();
        // $this->objectManager->clear();

        $contratActualises = [];
        foreach ($contratsByModuleItem['contrats'] as $contrat) {
            $c = $this->managerRegistry->getRepository(Contrat::class)->findOneBy(['id'=>$contrat['id']]);
            if ($c) {
                $contratActualises[] = $c->getAsArray();
            }
        }

        return [
            'data' =>[
                'noteSur20' => $note ? number_format($note, 2) : null,
                'grade' => $this->showGrade($note*5),
                'sessionValidation' => $sessionValidation,
                'decision' => $dec,
                'noteSur4' => $note ? number_format($note/5, 2) : null,
                'creditModule' => $creditModule,
                'pointsModule' => $pointsModule,
                'moduleId' => $moduleId,
                'etudiantInscrisId' => $etudiantInscrisId,
                'creditValider' => $creditValider,
                'anneeValidation' => $dec == 'V' ? $annee->getDenominationSlash() : null,
            ],
            'contratActualises' => $contratActualises,
        ];
    }

    private function showDecision(float $note, float $noteV=10): string
    {
        $decision = "NV";
        if ($note >= $noteV) {
            $decision = "V";
        }
        return $decision;
    }

    private function showGrade($note, $noteE=7): string
    {
        $grade = "E";
        if ($note >= 90) {
            $grade = "A+";
        }
        elseif ($note<90 && $note>=85) {
            $grade = "A";
        }
        elseif ($note<85 && $note>=80) {
            $grade = "A-";
        }
        elseif ($note>=75 && $note<80) {
            $grade = "B+";
        }
        elseif ($note>=70 && $note<75) {
            $grade = "B";
        }
        elseif ($note>=65 && $note<70) {
            $grade = "B-";
        }
        elseif ($note>=60 && $note<65) {
            $grade = "C+";
        }
        elseif ($note>=55 && $note<60) {
            $grade = "C";
        }
        elseif ($note>=50 && $note<55) {
            $grade = "C-";
        }
        elseif ($note>=45 && $note<50) {
            $grade = "D+";
        }
        elseif ($note>=40 && $note<35) {
            $grade = "D";
        }else {
            $grade = "E";
        }

        return $grade;
    }

    private function getDecisionFinale($creditsAcquis, $creditsTotal)
    {
        return $creditsAcquis >= $creditsTotal ? "VALIDE" : "NON VALIDE";
    }

    private function getMention($moyenne){
        $mention = '-';
        if (is_numeric($moyenne) && $moyenne > 2 && $moyenne <= 4) {
            $mention = 'TRES FAIBLE';
        }elseif (is_numeric($moyenne) && $moyenne > 4 && $moyenne <= 6) {
            $mention = 'FAIBLE';
        }elseif (is_numeric($moyenne) && $moyenne > 6 && $moyenne <= 8) {
            $mention = 'INSUFFISANT';
        }elseif (is_numeric($moyenne) && $moyenne > 8 && $moyenne < 10) {
            $mention = 'MEDIOCRE';
        }elseif (is_numeric($moyenne) && $moyenne >= 10 && $moyenne < 12) {
            $mention = 'PASSABLE';
        }elseif (is_numeric($moyenne) && $moyenne >= 12 && $moyenne < 14) {
            $mention = 'ASSEZ-BIEN';
        }elseif (is_numeric($moyenne) && $moyenne >= 14 && $moyenne < 16) {
            $mention = 'BIEN';
        }elseif (is_numeric($moyenne) && $moyenne >= 16 && $moyenne < 18) {
            $mention = 'TRES BIEN';
        }elseif (is_numeric($moyenne) && $moyenne >= 18 && $moyenne <= 20){
            $mention = 'EXCELENT';
        }elseif (is_numeric($moyenne) && $moyenne <= 2){
            $mention = 'NULL';
        }
        return $mention;
    }

    public function commencerProcessusDeCalcul(Examen $examen, Array $etudiants, AnneeAcademique $annee, ?int $semestre, $pourcentageCC, $pourcentageSN, EntityManagerInterface $manager, EntityManagerInterface $doctrine)
    {
        $this->annee = $annee;
        $i = 1;
        foreach ($etudiants as $etudiant) {
            $this->lancerCalculs($examen, $etudiant, $annee, $semestre, $pourcentageCC, $pourcentageSN, $manager, $i, $doctrine);
            switch ($semestre) {
                case 1:
                    $etudiant->setNotesSemestre1(null);
                    break;
                case 2:
                    $etudiant->setNotesSemestre2(null);
                    break;
                
                default:
                    $etudiant->setNotesAnnuelle(null);
                    break;
            }
            $i++;
        }
        $manager->flush();
        // $manager->clear();

        $results = $this->calculerNotesModule($etudiants, $annee, $semestre);
        $manager->flush();
        // $manager->clear();
        // on met jour les notes annuelles
        $this->calculerNotesModule($etudiants, $annee, null);
        $manager->flush();
        // $manager->clear();
        
        $this->editSynthesesModulaires($results['synthesesModulaires'], $manager, $doctrine);

        $this->verifierIfADD($etudiants, $annee);
        $manager->flush();
        // $manager->clear();
    }

    /**
     * Cette fonction applique les critères de calcul sur chaque contrat de l'etudiant 
     */
    public function lancerCalculs(Examen $examen, EtudiantInscris $etudiant, AnneeAcademique $annee, int $semestre, $pourcentageCC, $pourcentageSN, EntityManagerInterface $manager, $cmp, EntityManagerInterface $doctrine)
    {
        $diviseur20 = 20;
        $diviseur5 = 5;
        $diviseur4 = 4;
        $nbChiffresApresVirgule = 2;
        $dataHasChange = false;
        foreach ($etudiant->getContrats() as $contrat) {
            // On parcours tous les contrat du semestre concerné 
            // if (!$contrat->getIsValidated()){
                if ($contrat->getEcModule()->getSemestre() == $semestre) {
                    $moyAvtRat = null;
                    $moyAprRat = $decision = null;
                    $moyAprJury = $grade = null;
                    $moyDefinitive = $note = null;
                    $isValidated = false;
                    $noteDefinitive = null; // Represente la note de l'examen qui est utilisée dans les calculs
                    $sessionValidation = null;

                    $ccPercent = $pourcentageCC;
                    $examPercent = $pourcentageSN;
                    $tpePercent = 0;
                    $tpPercent = 0;

                    $percent = $contrat->getEcModule()->getPourcentageECModule();
                    if ($percent !== null) {
                        if ($percent->getPourcentageCC() !== null) {
                            $ccPercent = $percent->getPourcentageCC();
                        }
                        if ($percent->getPourcentageExam() !== null) {
                            $examPercent = $percent->getPourcentageExam();
                        }
                        if ($percent->getPourcentageCC() !== null) {
                            $tpePercent = $percent->getPourcentageTPE();
                        }
                        if ($percent->getPourcentageCC() !== null) {
                            $tpPercent = $percent->getPourcentageTP();
                        }
                    }
                    
                    if ($contrat->getNoteCC() && $contrat->getNoteSN()) {
                        // L'étudiant a une note pour la SN ainsi que pour le CC il faut avoir ces deux notes pour la suite
                        if (strtoupper($examen->getType()) == 'E') {
                            $moyAvtRat = number_format(((($contrat->getNoteCC()*$ccPercent) + ($contrat->getNoteSN()*$examPercent) + ($contrat->getNoteTP()*$tpPercent) + ($contrat->getNoteTPE()*$tpePercent))/$diviseur20)/$diviseur5, $nbChiffresApresVirgule);
                            $noteDefinitive = $contrat->getNoteSN();
                            $moyDefinitive = $moyAvtRat;
                            $sessionValidation = 'N';
                        } elseif (strtoupper($examen->getType()) == 'R') {
                            /*
                                La condition suivante doit etre remplie pour calculer les notes de rattrapage
                                - L'etudiant doit avoir une note de rattrapage
                                    => Si oui
                                        + Il faut en plus verifier si
                                            ~ le contrat n'est pas validé
                                                = OUI
                                                    alors on calcule
                                                = NON
                                                    alors on verifie si on a autoriser aux etudiants de pouvoir ameliorer leurs notes dans les configurations
                                    => Si NON
                                        + On ne calcule pas
                            */
                            // if ($contrat->getNoteSR() && (!$contrat->getIsValidated() || $annee->getConfiguration()->getIsRattrapageSurToutesLesMatieres())) {
                            if ($contrat->getNoteSR() && (!$contrat->getIsValidated() || $annee->getConfiguration()->getIsRattrapageSurToutesLesMatieres())) {
                                $moyAprRat = number_format(((($contrat->getNoteCC()*$pourcentageCC) + ($contrat->getNoteSR()*$pourcentageSN) + ($contrat->getNoteTP()*$tpPercent) + ($contrat->getNoteTPE()*$tpePercent))/$diviseur20)/$diviseur5, $nbChiffresApresVirgule);
                                
                                $moyDefinitive = $moyAprRat;
                                $noteDefinitive = $contrat->getNoteSR();
                                $sessionValidation = 'R';
                            }
                        }

                        if ($contrat->getNoteJury()) {
                            $moyAprJury = $contrat->getMoyApresJury();
                            $moyDefinitive = $moyAprJury;
                            $noteDefinitive = $contrat->getNoteJury();
                        }
                        if ($moyDefinitive) {
                            $note = number_format($moyDefinitive/$diviseur5, $nbChiffresApresVirgule);
                            $decision = $this->showDecision($moyDefinitive, $annee->getConfiguration()->getNotePourValiderUneMatiere());
                            $grade = $this->showGrade($moyDefinitive*$diviseur5, $annee->getConfiguration()->getNoteEliminatoire());
                            $isValidated = $moyDefinitive >= $annee->getConfiguration()->getNotePourValiderUneMatiere() ? true : false;
                        }
                    }
                    if ($moyDefinitive) {
                        $anneeValidation = $isValidated ? $annee->getDenominationSlash() : null;
                        if ($contrat->getAnneeValidation()) {
                            $anneeValidation = $contrat->getAnneeValidation();
                        }
                        $contrat->setIsValidated($isValidated)
                            ->setMoyAvantRattrapage($moyAvtRat)
                            ->setMoyApresRattrapage($moyAprRat)
                            ->setMoyApresJury($moyAprJury)
                            ->setMoyDefinitive($moyDefinitive)
                            ->setGrade($grade)
                            ->setDecision($decision)
                            ->setNote($note)
                            ->setNoteDefinitive($noteDefinitive)
                            ->setIsTransferable($isValidated)
                            ->setSessionValidation($sessionValidation)
                            ->setAnneeValidation($anneeValidation)
                            ->setIsDataHasChange(false);

                        if ($contrat->getIsDette() && $contrat->getIsValidated() && $contrat->getContratPrecedent()) {
                            $et = $this->updateContratPrecedent($contrat);
                            $this->commencerProcessusDeCalcul($examen, [$et], $et->getAnneeAcademique(), $semestre, $pourcentageCC, $pourcentageSN, $manager, $doctrine);
                        }
                    }
                }
            // }
        }

        if ($cmp%10 == 0) {
            $manager->flush();
            // $manager->clear();
        }
    }

    /**
     * Cette fonction servira à mettre a jour les contrats qui sont considere comme dette
     */
    private function updateContratPrecedent(Contrat $contrat, Contrat $validatedContrat=null): EtudiantInscris
    {
        if (!$validatedContrat) {
            $validatedContrat = $contrat;
        }
        
        // Le contrat qui est envoye est une dette et il est valide et le contrat precedent existe
        if ($contrat->getContratPrecedent()->getContratPrecedent()) {
            // Tant qu'on est pas sur la premiere dette liee a cette matiere pour cet etudiant on continue de chercher
            $this->updateContratPrecedent($contrat->getContratPrecedent(), $validatedContrat);
        }

        $notesArchive = $contrat->getContratPrecedent()->getAsArray();

        $contrat->getContratPrecedent()->setNoteCC($validatedContrat->getNoteCC())
                                       ->setNoteSN($validatedContrat->getNoteSN())
                                       ->setNoteSR($validatedContrat->getNoteSR())
                                       ->setIsValidated($validatedContrat->getIsValidated())
                                       ->setMoyAvantRattrapage($validatedContrat->getMoyAvantRattrapage())
                                       ->setMoyApresRattrapage($validatedContrat->getMoyApresRattrapage())
                                       ->setMoyApresJury($validatedContrat->getMoyApresJury())
                                       ->setGrade($validatedContrat->getGrade())
                                       ->setDecision($validatedContrat->getDecision())
                                       ->setNote($validatedContrat->getNote())
                                       ->setNoteJury($validatedContrat->getNoteJury())
                                       ->setNoteDefinitive($validatedContrat->getNoteDefinitive())
                                       ->setSessionValidation($validatedContrat->getSessionValidation())
                                       ->setIsTransferable($validatedContrat->getIsTransferable())
                                       ->setAnneeValidation($validatedContrat->getAnneeValidation())
                                       ->setNotesArchive($notesArchive);

        return $contrat->getContratPrecedent()->getEtudiantInscris();

    }

    /**
     * Cette function permet de verifier si l'etudiant a validé toutes ces matieres ou pas
     */
    public function verifierIfADD($etudiants, AnneeAcademique $annee)
    {
        foreach ($etudiants as $etudiant) {
            $nbContratsValides = 0;
            $nbContrats = count($etudiant->getContrats());
            if ($nbContrats > 0) {
                foreach ($etudiant->getContrats() as $contrat) {
                    if ($contrat->getIsValidated()) {
                        $nbContratsValides++;
                    }
                }
                $moyenne = 0;
                $PC = $etudiant->getPoints();
                if ($PC['credits'] > 0) {
                    $moyenne = $PC['points']/$PC['credits'];
                }
                $moyenne = number_format($moyenne, 2);
                $etudiant->setMoyenneObtenue($moyenne);

                $pourcentageECValide = ($nbContratsValides*100)/$nbContrats;
                if ($pourcentageECValide >= 100) {
                    $etudiant->setIsADD(true)
                             ->setRedouble(false)
                             ->setIsADC(false);
                }elseif ($annee->getConfiguration()->getPourcentageECForADC() <= $pourcentageECValide) {
                    $etudiant->setIsADC(true)
                             ->setRedouble(false)
                             ->setIsADD(false);
                }else {
                    $etudiant->setRedouble(true)
                             ->setIsADD(false)
                             ->setIsADC(false);
                }
            }
        }
    }

    /**
     * Cette function permet de savoir le statut de l'etudiant pour chaque module.
     */
    public function editSynthesesModulaires(Array $synthesesModulaires, EntityManagerInterface $objectManager, EntityManagerInterface $doctrine)
    {
        // dump($synthesesModulaires);die();
        foreach ($synthesesModulaires as $sms) {
            foreach ($sms as $sm) {
                $module = $doctrine->getRepository(Module::class)->find($sm['moduleId']);
                $etudiantInscris = $doctrine->getRepository(EtudiantInscris::class)->find($sm['etudiantInscrisId']);
                if ($module && $etudiantInscris) {
                    $syntheseModulaire = $doctrine->getRepository(SyntheseModulaire::class)->findOneBy(['etudiantInscris'=>$etudiantInscris, 'module'=>$module]);
                    $persist = false;
                    if (!$syntheseModulaire) {
                        $syntheseModulaire = new SyntheseModulaire();
                        $persist = true;
                    }
                    $syntheseModulaire->setEtudiantInscris($etudiantInscris)
                                    ->setModule($module)
                                    ->setMoyenne($sm['noteSur20'])
                                    ->setNote($sm['noteSur4'])
                                    ->setCredit($sm['creditModule'])
                                    ->setGrade($sm['grade'])
                                    ->setDecision($sm['decision'])
                                    ->setPoints($sm['pointsModule'])
                                    ->setCreditValider($sm['creditValider']);
                    if ($persist) {
                        $objectManager->persist($syntheseModulaire);
                    }
                }
            }
                
        }
    }
}