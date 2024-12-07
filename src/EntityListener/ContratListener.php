<?php
namespace App\EntityListener;

use App\Entity\Contrat;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class ContratListener
{
    private $session;
    private $pourcentageCC; // pourcentage de cc pour la session normale
    private $pourcentageCC2; // pourcentage de cc pour la session de rattrapage
    private $pourcentageSN; // pourcentage de l'examen de session normale
    private $pourcentageSR; // pourcentage du rattrapage

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
        $this->pourcentageCC = $requestStack->getSession()->get('pourcentageCC');
        $this->pourcentageCC2 = $requestStack->getSession()->get('pourcentageCC2'); // Pourcentage de CC pour le rattrapage
        $this->pourcentageSN = $requestStack->getSession()->get('pourcentageSN');
        $this->pourcentageSR = $requestStack->getSession()->get('pourcentageSR');
    }

    public function postLoad(Contrat $contrat, LifecycleEventArgs $args)
    {
        $pourcentageCC = $this->pourcentageCC;
        $pourcentageSN = $this->pourcentageSN;
        $pourcentageCC2 = $this->pourcentageCC2;
        $pourcentageSR = $this->pourcentageSR;
        
        $diviseur4 = 4;
        $diviseur5 = 5;
        $diviseur20 = 20;
        $nbChiffresApresVirgule = 2;
        $annee = $contrat->getEtudiantInscris()->getAnneeAcademique();

        // SN
        $statsSN['cc'] = $contrat->getNoteCC();
        $statsSN['exam'] = $contrat->getNoteSN();
        $statsSN['noteTP'] = $contrat->getNoteTP();
        $statsSN['noteTPE'] = $contrat->getNoteTPE();
        $statsSN['sessionValidation'] = $contrat->getIsValidated() ? $contrat->getSessionValidation() : '';
        $statsSN['moyenne'] = $this->calculerMoyenne($contrat->getNoteCC(), $contrat->getNoteSN(), $pourcentageCC, $pourcentageSN, $diviseur20, $diviseur5, $nbChiffresApresVirgule);
        $statsSN['note'] = $statsSN['moyenne'] ? number_format($statsSN['moyenne']/$diviseur5, $nbChiffresApresVirgule) : null;
        $statsSN['decision'] = $this->showDecision($statsSN['moyenne'], $annee->getConfiguration()->getNotePourValiderUneMatiere());
        $statsSN['grade'] = $this->showGrade($statsSN['moyenne']*$diviseur5, $annee->getConfiguration()->getNoteEliminatoire());
        $statsSN['isValidated'] = $statsSN['moyenne'] >= $annee->getConfiguration()->getNotePourValiderUneMatiere() ? true : false;

        // SR
        $statsSR['cc'] = $contrat->getNoteCC();
        $statsSR['exam'] = $contrat->getNoteSR();
        $statsSR['noteTP'] = $contrat->getNoteTP();
        $statsSR['noteTPE'] = $contrat->getNoteTPE();
        $statsSR['sessionValidation'] = $contrat->getIsValidated() ? $contrat->getSessionValidation() : '';
        $statsSR['moyenne'] = $this->calculerMoyenne($contrat->getNoteCC(), $contrat->getNoteSR(), $pourcentageCC2, $pourcentageSR, $diviseur20, $diviseur5, $nbChiffresApresVirgule);
        $statsSR['note'] = $statsSR['moyenne'] ? number_format($statsSR['moyenne']/$diviseur5, $nbChiffresApresVirgule) : null;
        $statsSR['decision'] = $this->showDecision($statsSR['moyenne'], $annee->getConfiguration()->getNotePourValiderUneMatiere());
        $statsSR['grade'] = $this->showGrade($statsSR['moyenne']*$diviseur5, $annee->getConfiguration()->getNoteEliminatoire());
        $statsSR['isValidated'] = $statsSR['moyenne'] >= $annee->getConfiguration()->getNotePourValiderUneMatiere() ? true : false;

        // FINALE
        $statsDef['cc'] = $contrat->getNoteCC();
        $statsDef['exam'] = $contrat->getNoteDefinitive();
        $statsDef['moyenne'] = $contrat->getMoyDefinitive();
        $statsDef['note'] = $contrat->getNote();
        $statsDef['decision'] = $contrat->getDecision();
        $statsDef['grade'] = $contrat->getGrade();
        $statsDef['isValidated'] = $contrat->getIsValidated();
        $statsDef['noteTP'] = $contrat->getNoteTP();
        $statsDef['noteTPE'] = $contrat->getNoteTPE();
        $statsDef['sessionValidation'] = $contrat->getIsValidated() ? $contrat->getSessionValidation() : '';

        $contrat->statsDef = $statsDef;
        $contrat->statsSN = $statsSN;
        $contrat->statsSR = $statsSR;

    }

    // Les valeurs possibles sont V=validé, VC=validé avec compensation, NV=non validé
    private function showDecision($note, float $noteV=10): string
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

    private function calculerMoyenne($noteCC, $noteExam, $pourcentageCC, $pourcentageSN, $diviseur20, $diviseur5, $nbChiffresApresVirgule)
    {
        if (!$noteCC || !is_numeric($noteCC) || !$noteExam || !is_numeric($noteExam)) {
            return null;
        }

        return number_format(((($noteCC*$pourcentageCC) + ($noteExam*$pourcentageSN))/$diviseur20)/$diviseur5, $nbChiffresApresVirgule);
    }
}