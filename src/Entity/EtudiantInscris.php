<?php

namespace App\Entity;

use App\Entity\ECModule;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: "App\Repository\EtudiantInscrisRepository")]
class EtudiantInscris
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    #[Groups("public")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Etudiant", inversedBy: "etudiantInscris", cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups("public")]
    private $etudiant;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Classe", inversedBy: "etudiantInscris")]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups("public")]
    private $classe;

    #[ORM\OneToMany(targetEntity: "App\Entity\Paiement", mappedBy: "etudiantInscris")]
    private $paiements;

    #[ORM\ManyToOne(targetEntity: "App\Entity\AnneeAcademique", inversedBy: "etudiantInscris")]
    private $anneeAcademique;

    #[ORM\Column(type: "boolean")]
    private $canTakeReleve;

    #[ORM\Column(type: "boolean")]
    private $isRedoublant;

    #[ORM\Column(type: "boolean")]
    private $isADD;

    #[ORM\Column(type: "boolean")]
    private $isADC;

    #[ORM\Column(type: "boolean")]
    private $redouble;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private $moyenneObtenue;

    #[ORM\OneToMany(targetEntity: "App\Entity\Contrat", mappedBy: "etudiantInscris")]
    private $contrats;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $anonymat;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private $moyenneSemestre1;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private $moyenneSemestre2;

    #[ORM\Column(type: "text", nullable: true)]
    private $notesSemestre1;

    #[ORM\Column(type: "text", nullable: true)]
    private $notesSemestre2;

    #[ORM\Column(type: "text", nullable: true)]
    private $notesAnnuelle;

    #[ORM\OneToMany(targetEntity: "App\Entity\SyntheseModulaire", mappedBy: "etudiantInscris", orphanRemoval: true)]
    private $synthesesModulaires;

    #[ORM\Column(type: "boolean")]
    private $isDefinitif;

    #[ORM\Column(type: "integer", nullable: true)]
    private $creditAcquisSemestre1;

    #[ORM\Column(type: "integer", nullable: true)]
    private $creditAcquisSemestre2;

    public $matricule;
    private $createdAt;
    public $formation;
    public $filiere;
    public $specialite;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
        $this->contrats = new ArrayCollection();
        $this->redouble = $this->canTakeReleve = false;
        $this->isADC = $this->isADD = false;
        $this->synthesesModulaires = new ArrayCollection();
        $this->isDefinitif = true;
        $this->createdAt = new \DateTime();
        $this->matricule = $this->getEtudiant() !== null ? $this->getEtudiant()->getMatricule() : '';
        // $this->quituses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtudiant(): ?Etudiant
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Etudiant $etudiant): self
    {
        $this->etudiant = $etudiant;

        return $this;
    }

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(?Classe $classe): self
    {
        $this->classe = $classe;

        return $this;
    }

    /**
     * @return Collection|Paiement[]
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): self
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements[] = $paiement;
            $paiement->setEtudiantInscris($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): self
    {
        if ($this->paiements->contains($paiement)) {
            $this->paiements->removeElement($paiement);
            // set the owning side to null (unless already changed)
            if ($paiement->getEtudiantInscris() === $this) {
                $paiement->setEtudiantInscris(null);
            }
        }

        return $this;
    }

    public function getAnneeAcademique(): ?AnneeAcademique
    {
        return $this->anneeAcademique;
    }

    public function setAnneeAcademique(?AnneeAcademique $anneeAcademique): self
    {
        $this->anneeAcademique = $anneeAcademique;

        return $this;
    }

    public function getCanTakeReleve(): ?bool
    {
        return $this->canTakeReleve;
    }

    public function setCanTakeReleve(bool $canTakeReleve): self
    {
        $this->canTakeReleve = $canTakeReleve;

        return $this;
    }

    public function getIsRedoublant(): ?bool
    {
        return $this->isRedoublant;
    }

    public function setIsRedoublant(bool $isRedoublant): self
    {
        $this->isRedoublant = $isRedoublant;

        return $this;
    }

    public function getIsADD(): ?bool
    {
        return $this->isADD;
    }

    public function setIsADD(bool $isADD): self
    {
        $this->isADD = $isADD;

        return $this;
    }

    public function getIsADC(): ?bool
    {
        return $this->isADC;
    }

    public function setIsADC(bool $isADC): self
    {
        $this->isADC = $isADC;

        return $this;
    }

    public function getRedouble(): ?bool
    {
        return $this->redouble;
    }

    public function setRedouble(bool $redouble): self
    {
        $this->redouble = $redouble;

        return $this;
    }

    public function getMoyenneObtenue(): ?string
    {
        return $this->moyenneObtenue;
    }

    public function setMoyenneObtenue(?string $moyenneObtenue): self
    {
        $this->moyenneObtenue = $moyenneObtenue;

        return $this;
    }

    /**
     * @return Collection|Contrat[]
     */
    public function getContrats(): Collection
    {
        return $this->contrats;
    }

    public function addContrat(Contrat $contrat): self
    {
        if (!$this->contrats->contains($contrat)) {
            $this->contrats[] = $contrat;
            $contrat->setEtudiantInscris($this);
        }

        return $this;
    }

    public function removeContrat(Contrat $contrat): self
    {
        if ($this->contrats->contains($contrat)) {
            $this->contrats->removeElement($contrat);
            // set the owning side to null (unless already changed)
            if ($contrat->getEtudiantInscris() === $this) {
                $contrat->setEtudiantInscris(null);
            }
        }

        return $this;
    }

    /**
     * Cette methode permet de verifier si un ecModule existe deja dans le contrat de l'etudiant
     */
    public function contratExist(ECModule $ecModule): bool
    {
        foreach ($this->contrats as $contrat) {
            if ($contrat->getEcModule()->getId() == $ecModule->getId()) {
                return true;
            }
        }
        return false;
    }

    public function getDataAsArray()
    {
        return [
            $this->getEtudiant()->getMatricule(),
            $this->getEtudiant()->getNom(),
            $this->getEtudiant()->getPrenom(),
            date_format($this->getEtudiant()->getDateDeNaissance(), 'd/m/Y'),
            $this->getEtudiant()->getLieuDeNaissance(),
            $this->getEtudiant()->getSexe(),
            $this->getEtudiant()->getTelephone1(),
            $this->getEtudiant()->getTelephone2(),
            $this->getEtudiant()->getAdresseEmail(),
            $this->getEtudiant()->getNombreDEnfants(),
            $this->getEtudiant()->getSituationMatrimoniale(),
            $this->getEtudiant()->getCivilite(),
            $this->getEtudiant()->getDiplomeAcademiqueMax(),
            $this->getEtudiant()->getAnneeObtentionDiplomeAcademiqueMax(),
            $this->getEtudiant()->getDiplomeDEntre(),
            $this->getEtudiant()->getAnneeObtentionDiplomeEntre(),
            $this->getEtudiant()->getNomDuPere(),
            $this->getEtudiant()->getNumeroDeTelephoneDuPere(),
            $this->getEtudiant()->getProfessionDuPere(),
            $this->getEtudiant()->getNomDeLaMere(),
            $this->getEtudiant()->getNumeroDeTelephoneDeLaMere(),
            $this->getEtudiant()->getProfessionDeLaMere(),
            $this->getEtudiant()->getAdresseDesParents(),
            $this->getEtudiant()->getPersonneAContacterEnCasDeProbleme(),
            $this->getEtudiant()->getNumeroDUrgence(),
            $this->getIsRedoublant() ? "REDOUBLANT" : "NOUVEAU DANS LA CLASSE",
        ];
    }

    public function getAnonymat(): ?string
    {
        return $this->anonymat;
    }

    public function setAnonymat(?string $anonymat): self
    {
        $this->anonymat = $anonymat;

        return $this;
    }

    public function getMoyenneSemestre1(): ?string
    {
        return $this->moyenneSemestre1;
    }

    public function setMoyenneSemestre1(?string $moyenneSemestre1): self
    {
        $this->moyenneSemestre1 = $moyenneSemestre1;

        return $this;
    }

    public function getMoyenneSemestre2(): ?string
    {
        return $this->moyenneSemestre2;
    }

    public function setMoyenneSemestre2(?string $moyenneSemestre2): self
    {
        $this->moyenneSemestre2 = $moyenneSemestre2;

        return $this;
    }

    public function getNotesSemestre1(): ?string
    {
        return $this->notesSemestre1;
    }

    public function setNotesSemestre1(?string $notesSemestre1): self
    {
        $this->notesSemestre1 = $notesSemestre1;

        return $this;
    }

    public function getNotesSemestre2(): ?string
    {
        return $this->notesSemestre2;
    }

    public function setNotesSemestre2(?string $notesSemestre2): self
    {
        $this->notesSemestre2 = $notesSemestre2;

        return $this;
    }

    public function getNotesAnnuelle(): ?string
    {
        return $this->notesAnnuelle;
    }

    public function setNotesAnnuelle(?string $notesAnnuelle): self
    {
        $this->notesAnnuelle = $notesAnnuelle;

        return $this;
    }

    public function getAsArray()
    {
        return [
            'id' => $this->getId(),
            'canTakeReleve' => $this->getCanTakeReleve(),
            'isRedoublant' => $this->getIsRedoublant(),
            'isADD' => $this->getIsADC(),
            'isADC' => $this->getIsADC(),
            'redouble' => $this->getRedouble(),
            'moyenneObtenue' => $this->getMoyenneObtenue(),
            'moyenneSemestre1' => $this->getMoyenneSemestre1(),
            'moyenneSemestre2' => $this->getMoyenneSemestre2(),
            'notesSemestre1' => json_decode($this->getNotesSemestre1(), true),
            'notesSemestre2' => json_decode($this->getNotesSemestre2(), true),
            'notesAnnuelle' => json_decode($this->getNotesAnnuelle(), true),
            'anonymat' => $this->getAnonymat(),
            'etudiant' => $this->getEtudiant()->getAsArray(),
            'classe' => $this->getClasse()->getAsArray(),
            'anneeAcademique' => $this->getAnneeAcademique()->getAsArray(),
        ];
    }

    /**
     * @return Collection|SyntheseModulaire[]
     */
    public function getSynthesesModulaires(): Collection
    {
        return $this->synthesesModulaires;
    }

    public function getPoints()
    {
        $points = 0; $credits = 0;
        foreach ($this->getSynthesesModulaires() as $sm) {
            $credits += $sm->getCredit();
            if ($sm->getPoints()) {
                $points += $sm->getPoints();
            }
        }

        return ['points'=>$points, 'credits'=>$credits];
    }

    public function addSynthesesModulaire(SyntheseModulaire $synthesesModulaire): self
    {
        if (!$this->synthesesModulaires->contains($synthesesModulaire)) {
            $this->synthesesModulaires[] = $synthesesModulaire;
            $synthesesModulaire->setEtudiantInscris($this);
        }

        return $this;
    }

    public function removeSynthesesModulaire(SyntheseModulaire $synthesesModulaire): self
    {
        if ($this->synthesesModulaires->contains($synthesesModulaire)) {
            $this->synthesesModulaires->removeElement($synthesesModulaire);
            // set the owning side to null (unless already changed)
            if ($synthesesModulaire->getEtudiantInscris() === $this) {
                $synthesesModulaire->setEtudiantInscris(null);
            }
        }

        return $this;
    }

    public function getIsDefinitif(): ?bool
    {
        return $this->isDefinitif;
    }

    public function setIsDefinitif(bool $isDefinitif): self
    {
        $this->isDefinitif = $isDefinitif;

        return $this;
    }

    public function getCreditAcquisSemestre1(): ?int
    {
        return $this->creditAcquisSemestre1;
    }

    public function setCreditAcquisSemestre1(?int $creditAcquisSemestre1): self
    {
        $this->creditAcquisSemestre1 = $creditAcquisSemestre1;

        return $this;
    }

    public function getCreditAcquisSemestre2(): ?int
    {
        return $this->creditAcquisSemestre2;
    }

    public function setCreditAcquisSemestre2(?int $creditAcquisSemestre2): self
    {
        $this->creditAcquisSemestre2 = $creditAcquisSemestre2;

        return $this;
    }

    public function getContratsSemestre(int $semestre=null) 
    {
        $contratsSemstre = [];
        foreach ($this->getContrats() as $c) {
            if ($c->getEcModule()->getModule()->getClasse()->getId() == $this->getClasse()->getId() && (!$semestre || $c->getEcModule()->getSemestre() == $semestre)) {
                $contratsSemstre[] = $c;
            }
        }
        return $contratsSemstre;
    }

    public function getCreditsTotalAcquis() {

        return ($this->getCreditAcquisSemestre1() + $this->getCreditAcquisSemestre2());
    }

    public function getDecision(int $semestre=null): string
    {
        $creditsTotal = 0;
        $conts = $this->getContratsSemestre($semestre);
        foreach ($conts as $c) {
            $creditsTotal += $c->getEcModule()->getCredit();
        }

        $decision = "NV";
        if (($semestre == 1 && $creditsTotal == $this->getCreditAcquisSemestre1()) 
            || ($semestre == 2 && $creditsTotal == $this->getCreditAcquisSemestre2())
            || (!$semestre && $creditsTotal ==  $this->getCreditsTotalAcquis())) {
            $decision = 'V';
        }

        return $decision;
    }

    public function getGrade($note, $noteE=7): string
    {
        $note = $note*5;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isTranchePayed($semestre=1): bool
    {
        $isPayed = false;
        $t1 = false;
        $t2 = false;
        foreach ($this->getPaiements() as $p) {
            if ($semestre == 1 || $semestre == 2) {
                // dump($p->getTranche()->getDenomination());die();
                if ($p->getIsPaied() && mb_strtoupper($p->getTranche()->getDenomination()) == 'TRANCHE '.$semestre && mb_strtoupper($p->getTranche()->getPaiementClasse()->getTypeDePaiement()->getDenomination()) == 'SCOLARITE') {
                    $isPayed = true;
                    goto end;
                }
            }else {
                if ($p->getIsPaied() && mb_strtoupper($p->getTranche()->getDenomination()) == 'TRANCHE 1' && mb_strtoupper($p->getTranche()->getPaiementClasse()->getTypeDePaiement()->getDenomination()) == 'SCOLARITE') {
                    $t1 = true;
                }
                elseif ($p->getIsPaied() && mb_strtoupper($p->getTranche()->getDenomination()) == 'TRANCHE 2' && mb_strtoupper($p->getTranche()->getPaiementClasse()->getTypeDePaiement()->getDenomination()) == 'SCOLARITE') {
                    $t2 = true;
                }

                if ($t1 && $t2) {
                    $isPayed = true;
                    goto end;
                }
            }
        }

        end:
        return $isPayed;
    }

}
