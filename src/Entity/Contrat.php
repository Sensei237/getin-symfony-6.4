<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Le contrat represente la liste des matieres que doit faire un etudiant au courant d'une annee donnee. En fait pendant l'incription, chaque etudiant se voit attribuer une liste de matiere qu'il doit valider. On dit donc qu'un etudiant signe un contrat avec une matiere. 
 * Liste des proprietes est donc : 
 * - id : c'est un nombre qui est unique et qui permet d'identifier une matiere du contrat academique d'un etudiant. Seule la methode getId() est donc disponible pour cette propriete. 
 * - etudiantInscris : il s'agit ici d'une reference a la classe EtudiantInscris qui permet de determiner a quel etudiant appartient ce contrat. 
 * - ecModule : c'est une reference a la classe EcModule qui permet de determiner quel EC est concerné par le contrat. Lees methodes getEcModule() et setEcModule() sont disponibles. 
 * - noteCC : comme son nom l'indique, cette proprite represente la note de Controle continu de l'etudiant pour ce contrat. getNoteCC() et setNoteCC() sont disponibles pour la propriete. 
 * - noteSN : cette propriete represente la note de session normale. getNoteSN() et setNoteSN() sont disponibles. 
 * - noteSR : cette propriete represente la note de la session de rattrapage. getNoteSR() et setNoteSR() sont disponibles. 
 * - isDette : c'est un booleen qui permet de savoir si le contrat est une dette. getIsDette() et setIsDette(). 
 * - contratPrecedent : c'est une reference vers la classe Contrat elle meme. Cette propriete a une valeur NULL si le contrat n'est pas une dette dans le cas contraire, elle contient le contrat precedent. 
 * - isValidated : c'est un booleen qui permet de savoir si le contrat est validé ou pas. Methodes : getIsValidated() et setIsValidated()
 * - moyAvantRattrapage : c'est la moyenne obtenu avant le rattrapage. Methodes : getMoyAvantRattrapage() et setMoyAvantRattrapage()
 * - moyApresRattrapage : c'est la moyenne obtenu apres le rattrapage. Methodes : getMoyApresRattrapage() et setMoyApresRattrapage()
 * - moyApresJury : c'est la note donnee par le jury dans le cas des deliberations, Methodes: getMoyApresJury() et setMoyApresJury()
 * - moyDefinitive : c'est la note definitive qui sera consideree. Methodes : getMoyDefinitive() et setMoyDefinitive()
 * - grade : les valeurs possibles sont A+, A, A-, B+, B, B-, C+, C, C-. getGrade() et setGrade() sont disponibles. 
 * - decision : les valeurs possibles sont VA (Validé), NV (Non validé), VNT (Validé Non Transferable), EL (Eliminé), VC (Validé par Compensation). getDecision() et setDecision() sont disponibles. 
 * - note : ici on stockera la valeur de note sur 4 ou 5. getNote() et setNote() sont disponibles. 
 * 
 */
#[ORM\Entity(repositoryClass: "App\Repository\ContratRepository")]
#[ORM\EntityListeners(["App\EntityListener\ContratListener"])]
#[ORM\HasLifecycleCallbacks]
class Contrat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "App\Entity\EtudiantInscris", inversedBy: "contrats")]
    #[ORM\JoinColumn(nullable: false)]
    private $etudiantInscris;

    #[ORM\ManyToOne(targetEntity: "App\Entity\ECModule", inversedBy: "contrats")]
    #[ORM\JoinColumn(nullable: false)]
    private $ecModule;

    #[ORM\Column(type: "float", nullable: true)]
    private $noteCC;

    #[ORM\Column(type: "float", nullable: true)]
    private $noteSN;

    #[ORM\Column(type: "float", nullable: true)]
    private $noteSR;

    #[ORM\Column(type: "boolean")]
    private $isDette;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Contrat", inversedBy: "contrats")]
    private $contratPrecedent;

    #[ORM\OneToMany(targetEntity: "App\Entity\Contrat", mappedBy: "contratPrecedent")]
    private $contrats;

    #[ORM\Column(type: "boolean")]
    private $isValidated;

    #[ORM\Column(type: "float", nullable: true)]
    private $moyAvantRattrapage;

    #[ORM\Column(type: "float", nullable: true)]
    private $moyApresRattrapage;

    #[ORM\Column(type: "float", nullable: true)]
    private $moyApresJury;

    #[ORM\Column(type: "float", nullable: true)]
    private $moyDefinitive;

    #[ORM\Column(type: "string", length: 10, nullable: true)]
    private $grade;

    #[ORM\Column(type: "string", length: 25, nullable: true)]
    private $decision;

    #[ORM\Column(type: "float", nullable: true)]
    private $note;

    #[ORM\Column(type: "float", nullable: true)]
    private $noteJury;

    public $statsSN;
    public $statsSR;
    public $statsDef;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $noteDefinitive;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $sessionValidation;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isTransferable;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isOptionnal;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Anonymat", mappedBy="contrat", orphanRemoval=true)
     */
    private $anonymats;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $anneeValidation;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notesArchive;

    
    private $isDataHasChange;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $noteTP;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $noteTPE;

    public function __construct()
    {
        $this->contrats = new ArrayCollection();
        $this->isOptionnal = false;
        $this->anonymats = new ArrayCollection();
        $this->isDataHasChange = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtudiantInscris(): ?EtudiantInscris
    {
        return $this->etudiantInscris;
    }

    public function setEtudiantInscris(?EtudiantInscris $etudiantInscris): self
    {
        $this->etudiantInscris = $etudiantInscris;

        return $this;
    }

    public function getEcModule(): ?ECModule
    {
        return $this->ecModule;
    }

    public function setEcModule(?ECModule $ecModule): self
    {
        $this->ecModule = $ecModule;

        return $this;
    }

    public function getNoteCC(): ?float
    {
        if ($this->noteCC) {
            return number_format($this->noteCC/1, 2);
        }
        return $this->noteCC;
    }

    public function setNoteCC(?float $noteCC): self
    {
        if ($noteCC) {
            $noteCC = number_format($noteCC/1, 2);
        }
        $this->noteCC = $noteCC;

        return $this;
    }

    public function getNoteSN(): ?float
    {
        if ($this->noteSN) {
            return number_format($this->noteSN/1, 2);
        }

        return $this->noteSN;
    }

    public function setNoteSN(?float $noteSN): self
    {
        if ($noteSN) {
            $noteSN = number_format($noteSN/1, 2);
        }
        $this->noteSN = $noteSN;

        return $this;
    }

    public function getNoteSR(): ?float
    {
        if ($this->noteSR) {
            return number_format($this->noteSR/1, 2);
        }

        return $this->noteSR;
    }

    public function setNoteSR(?float $noteSR): self
    {
        if ($noteSR) {
            $noteSR = number_format($noteSR/1, 2);
        }
        $this->noteSR = $noteSR;

        return $this;
    }

    public function getIsDette(): ?bool
    {
        return $this->isDette;
    }

    public function setIsDette(bool $isDette): self
    {
        $this->isDette = $isDette;

        return $this;
    }

    public function getContratPrecedent(): ?self
    {
        return $this->contratPrecedent;
    }

    public function setContratPrecedent(?self $contratPrecedent): self
    {
        $this->contratPrecedent = $contratPrecedent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getContrats(): Collection
    {
        return $this->contrats;
    }

    public function addContrat(self $contrat): self
    {
        if (!$this->contrats->contains($contrat)) {
            $this->contrats[] = $contrat;
            $contrat->setContratPrecedent($this);
        }

        return $this;
    }

    public function removeContrat(self $contrat): self
    {
        if ($this->contrats->contains($contrat)) {
            $this->contrats->removeElement($contrat);
            // set the owning side to null (unless already changed)
            if ($contrat->getContratPrecedent() === $this) {
                $contrat->setContratPrecedent(null);
            }
        }

        return $this;
    }

    public function getIsValidated(): ?bool
    {
        return $this->isValidated;
    }

    public function setIsValidated(bool $isValidated): self
    {
        $this->isValidated = $isValidated;

        return $this;
    }

    public function getMoyAvantRattrapage(): ?float
    {
        if ($this->moyAvantRattrapage) {
            return number_format($this->moyAvantRattrapage, 2);
        }

        return $this->moyAvantRattrapage;
    }

    public function setMoyAvantRattrapage(?float $moyAvantRattrapage): self
    {
        if ($moyAvantRattrapage) {
            $moyAvantRattrapage = number_format($moyAvantRattrapage/1, 2);
        }
        $this->moyAvantRattrapage = $moyAvantRattrapage;

        return $this;
    }

    public function getMoyApresRattrapage(): ?float
    {
        if ($this->moyApresRattrapage) {
            return number_format($this->moyApresRattrapage, 2);
        }

        return $this->moyApresRattrapage;
    }

    public function setMoyApresRattrapage(?float $moyApresRattrapage): self
    {
        if ($moyApresRattrapage) {
            $moyApresRattrapage = number_format($moyApresRattrapage/1, 2);
        }
        $this->moyApresRattrapage = $moyApresRattrapage;

        return $this;
    }

    public function getMoyApresJury(): ?float
    {
        if ($this->moyApresJury) {
            return number_format($this->moyApresJury, 2);
        }

        return $this->moyApresJury;
    }

    public function setMoyApresJury(?float $moyApresJury): self
    {
        if ($moyApresJury) {
            $moyApresJury = number_format($moyApresJury/1, 2);
        }
        $this->moyApresJury = $moyApresJury;

        return $this;
    }

    public function getMoyDefinitive(): ?float
    {
        if ($this->moyDefinitive) {
            return number_format($this->moyDefinitive, 2);
        }

        return $this->moyDefinitive;
    }

    public function setMoyDefinitive(?float $moyDefinitive): self
    {
        if ($moyDefinitive) {
            $moyDefinitive = number_format($moyDefinitive/1, 2);
        }
        $this->moyDefinitive = $moyDefinitive;

        return $this;
    }

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): self
    {
        $this->grade = $grade;

        return $this;
    }

    public function getDecision(): ?string
    {
        return $this->decision;
    }

    public function setDecision(?string $decision): self
    {
        $this->decision = $decision;

        return $this;
    }

    public function getNote(): ?float
    {
        return $this->note;
    }

    public function setNote(?float $note): self
    {
        if ($note) {
            $note = number_format($note/1, 2);
        }
        $this->note = $note;

        return $this;
    }

    public function getNoteJury(): ?float
    {
        if ($this->noteJury) {
            return number_format($this->noteJury, 2);
        }

        return $this->noteJury;
    }

    public function setNoteJury(?float $noteJury): self
    {
        if ($noteJury) {
            $noteJury = number_format($noteJury/1, 2);
        }
        $this->noteJury = $noteJury;

        return $this;
    }

    public function getNoteDefinitive(): ?float
    {
        if ($this->noteDefinitive) {
            return number_format($this->noteDefinitive, 2);
        }
        
        return $this->noteDefinitive;
    }

    public function setNoteDefinitive(?float $noteDefinitive): self
    {
        if ($noteDefinitive) {
            $noteDefinitive = number_format($noteDefinitive/1, 2);
        }
        $this->noteDefinitive = $noteDefinitive;

        return $this;
    }

    public function getSessionValidation(): ?string
    {
        return $this->sessionValidation;
    }

    public function setSessionValidation(?string $sessionValidation): self
    {
        $this->sessionValidation = $sessionValidation;

        return $this;
    }

    public function getIsTransferable(): ?bool
    {
        return $this->isTransferable;
    }

    public function setIsTransferable(?bool $isTransferable): self
    {
        $this->isTransferable = $isTransferable;

        return $this;
    }

    public function getIsOptionnal(): ?bool
    {
        return $this->isOptionnal;
    }

    public function setIsOptionnal(bool $isOptionnal): self
    {
        $this->isOptionnal = $isOptionnal;

        return $this;
    }

    public function getAsArray()
    {

        return [
            'statsDef' => $this->statsDef,
            'statsSN' => $this->statsSN,
            'statsSR' => $this->statsSR,
            'isTransferable' => $this->getIsTransferable(),
            'sessionValidation' => $this->getSessionValidation(),
            'ec' => $this->getEcModule()->getEc()->getAsArray(),
            'anneeAcademique' => $this->getEtudiantInscris()->getAnneeAcademique()->getAsArray(),
            'credit' => $this->getEcModule()->getCredit(),
            'moduleId' => $this->getEcModule()->getModule()->getId(),
            'ecModuleId' => $this->getEcModule()->getId(),
            'etudiantInscrisId' => $this->getEtudiantInscris()->getId(),
            'isValidated' => $this->getIsValidated(),
            'anneeValidation' => $this->getAnneeValidation(),
            'noteCC' => $this->getNoteCC(),
            'noteSN' => $this->getNoteSN(),
            'noteSR' => $this->getNoteSR(),
            'isDette' => $this->getIsDette(),
            'moyAvantRattrapage' => $this->getMoyAvantRattrapage(),
            'moyApresRattrapage' => $this->getMoyApresRattrapage(),
            'moyApresJury' => $this->getMoyApresJury(),
            'moyDefinitive' => $this->getMoyDefinitive(),
            'grade' => $this->getGrade(),
            'decision' => $this->getDecision(),
            'note' => $this->getNote(),
            'noteJury' => $this->getNoteJury(),
            'noteDefinitive' => $this->getNoteDefinitive(),
            'semestre' => $this->getEcModule()->getSemestre(),
            'id' => $this->getId(),
            'moyDefinitiveStr' => $this->affiche_zero($this->getMoyDefinitive()),
            'noteStr' => $this->affiche_zero($this->getNote()),
            'noteTPE' => $this->affiche_zero($this->getNoteTPE()),
            'noteTP' => $this->affiche_zero($this->getNoteTP()),
        ];
    }

    /**
     * @return Collection|Anonymat[]
     */
    public function getAnonymats(): Collection
    {
        return $this->anonymats;
    }

    public function addAnonymat(Anonymat $anonymat): self
    {
        if (!$this->anonymats->contains($anonymat)) {
            $this->anonymats[] = $anonymat;
            $anonymat->setContrat($this);
        }

        return $this;
    }

    public function removeAnonymat(Anonymat $anonymat): self
    {
        if ($this->anonymats->contains($anonymat)) {
            $this->anonymats->removeElement($anonymat);
            // set the owning side to null (unless already changed)
            if ($anonymat->getContrat() === $this) {
                $anonymat->setContrat(null);
            }
        }

        return $this;
    }

    public function getAnneeValidation(): ?string
    {
        return $this->anneeValidation;
    }

    public function setAnneeValidation(?string $anneeValidation): self
    {
        $this->anneeValidation = $anneeValidation;

        return $this;
    }

    public function getNotesArchive(): ?array
    {

        return json_decode($this->notesArchive, true);
    }

    public function setNotesArchive(?array $notesArchive): self
    {
        $this->notesArchive = json_encode($notesArchive);

        return $this;
    }

    public function getIsDataHasChange(): ?bool
    {
        return $this->isDataHasChange;
    }

    public function setIsDataHasChange(bool $isDataHasChange): self
    {
        $this->isDataHasChange = $isDataHasChange;

        return $this;
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

    public function getNoteTP(): ?float
    {
        return $this->noteTP;
    }

    public function setNoteTP(?float $noteTP): self
    {
        $this->noteTP = $noteTP;

        return $this;
    }

    public function getNoteTPE(): ?float
    {
        return $this->noteTPE;
    }

    public function setNoteTPE(?float $noteTPE): self
    {
        $this->noteTPE = $noteTPE;

        return $this;
    }
}
