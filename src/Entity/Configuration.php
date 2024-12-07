<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cette classe vous permet d'entrer les donnees propre a l'ecole. Les proprietes disponibles sont : 
 * - id : propriete auto increment ainsi seule la methode getId() est disponible. 
 * - anneeAcademique : cette propriete fait reference a une annee academique. getAnneeAcademique() et setAnneeAcademique() sont disponibles.
 * - nomEcole : represente le nom de l'ecole. getNomEcole() et setNomEcole() sont disponibles. 
 * - initiale : c'est le nom court de l'ecole. getInitiale() et setInitiale() sont disponibles. 
 * - slogan : c'est le slogan de l'ecole. getSlogan() et setSlogan() sont disponibles. 
 * - logo : une image qui represente le logo de l'ecole. getLogo() et setLogo() sont disponibles. 
 * - ville : le nom de la ville ou est localisée l'ecole. getVille() et setVille() sont disponibles. 
 * - email : l'e-mail de l'ecole pour les messages electroniques. getEmail() et setEmail() sont disponibles. 
 * - telephone : le numero de telephone de l'ecole. getTelephone() et setTelephone() sont disponibles. 
 * - adresse : la localisation exacte de l'ecole. getAdresse() et setAdresse() sont disponibles. 
 * - notePourValiderUneMatiere : permet de savoir la note a partir de laquelle une matiere est consideree comme validee. getNotePourValiderUneMatiere() et setNotePourValiderUneMatiere() sont disponibles. 
 * - isValidationModulaire : un booleen qui permet de determiner le systeme de validation adopté par l'ecole. es-ce une validation modulaire ou une validation par matiere ? getIsValidationModulaire() et setIsValidationModulaire() sont disponibles. 
 * - notePourValiderUnModule : permet de savoir la note a partir de laquelle une module est consideree comme valide. getNotePourValiderUnModule() et setNotePourValiderUnModule() sont disponibles. 
 * - noteEliminatoire : permet de savoir quelle est la note eliminatoire. getNoteEliminatoire() et setNoteEliminatoire() sont disponibles. 
 * - boitePostale : la boite postale de l'ecole si elle en a une. getBoitePostale() et setBoitePostale() sont disponibles.
 * - isSREcraseSN : c'est une propriete qui permet de savoir si la na note de la session de rattrapage ecrase la note de session normale ou s'il faut choisir la meilleure des deux notes. getIsSREcraseSN() et setIsSREcraseSN() sont disponibles
 * - isRattrapageSurToutesLesMatieres : permet de delimiter les eleves qui vont au rattrapage. si cette propriete est à true, cela veut dire que meme un etudiant qui a validé une matiere peut recomposer cette matiere au rattrapage pour essayer d'ameliorer sa note. Si elle est a false, cela voudrait dire que seuls les etudiants qui n'ont pas valides une matiere donnee seront autorises a recomposer la dite matiere pendant la session de rattrapage. getIsRattrapageSurToutesLesMatieres() et setIsRattrapageSurToutesLesMatieres() sont disponibles 
 * 
 */
#[ORM\Entity(repositoryClass: "App\Repository\ConfigurationRepository")]
#[ORM\EntityListeners(['App\EntityListener\ConfigurationListener'])]
#[ORM\HasLifecycleCallbacks]
class Configuration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\OneToOne(targetEntity: "App\Entity\AnneeAcademique", inversedBy: "configuration", cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(nullable: false)]
    private $anneeAcademique;

    #[ORM\Column(type: "string", length: 255)]
    private $nomEcole;

    #[ORM\Column(type: "string", length: 255)]
    private $initiale;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $slogan;

    #[ORM\Column(type: "string", length: 255)]
    private $logo;

    #[ORM\Column(type: "string", length: 255)]
    private $ville;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $email;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $telephone;

    #[ORM\Column(type: "string", length: 255)]
    private $adresse;

    #[ORM\Column(type: "float", nullable: true)]
    private $notePourValiderUneMatiere;

    #[ORM\Column(type: "boolean")]
    private $isValidationModulaire;

    #[ORM\Column(type: "float", nullable: true)]
    private $notePourValiderUnModule;

    #[ORM\Column(type: "float", nullable: true)]
    private $noteEliminatoire;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $boitePostale;

    #[ORM\Column(type: "boolean")]
    private $isSREcraseSN;

    #[ORM\Column(type: "boolean")]
    private $isRattrapageSurToutesLesMatieres;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $nomEcoleEn;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $logoUniversity;

    // Ces propriétés ne semblent pas être persistées en base de données.
    public $logo2;
    public $logo1;

    #[ORM\Column(type: "integer")]
    private $pourcentageECForADC;

    private $logoUniversityBase64;
    private $logoEcoleBase64;

    private $filigrane;

    public function __construct()
    {
        $this->nomEcole = "ECOLE NATIONALE SUPERIEURE DU CAMEROUN";
        $this->initiale = "ENSC";
        $this->slogan = "Effort - Travail - Succes";
        $this->logo = "logo_ecole.jpg";
        $this->logoUniversity = "logo_universite.jpg";
        $this->nomEcoleEn = "NATIONAL HIGHT SCHOOL OF CAMEROON";
        $this->ville = "EBOLOWA";
        $this->adresse = "1234 ebolowa";
        $this->email = "ecole@mail.com";
        $this->telephone = "690909090";
        $this->notePourValiderUneMatiere = 10;
        $this->isValidationModulaire = true;
        $this->notePourValiderUnModule = 10;
        $this->noteEliminatoire = 7;
        $this->boitePostale = "118 Ebolowa";
        $this->isSREcraseSN = true;
        $this->isRattrapageSurToutesLesMatieres = false;
        $this->pourcentageECForADC = 70;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnneeAcademique(): ?AnneeAcademique
    {
        return $this->anneeAcademique;
    }

    public function setAnneeAcademique(AnneeAcademique $anneeAcademique): self
    {
        $this->anneeAcademique = $anneeAcademique;

        return $this;
    }

    public function getNomEcole(): ?string
    {
        return mb_strtoupper($this->nomEcole, 'utf8');
    }

    public function setNomEcole(string $nomEcole): self
    {
        $this->nomEcole = mb_strtoupper($nomEcole, 'utf8');

        return $this;
    }

    public function getInitiale(): ?string
    {
        return mb_strtoupper($this->initiale, 'utf8');
    }

    public function setInitiale(string $initiale): self
    {
        $this->initiale = mb_strtoupper($initiale, 'utf8');

        return $this;
    }

    public function getSlogan(): ?string
    {
        return mb_strtoupper($this->slogan, 'utf8');
    }

    public function setSlogan(?string $slogan): self
    {
        $this->slogan = mb_strtoupper($slogan, 'utf8');

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getVille(): ?string
    {
        return mb_strtoupper($this->ville, 'utf8');
    }

    public function setVille(string $ville): self
    {
        $this->ville = mb_strtoupper($ville, 'utf8');

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return mb_strtoupper($this->adresse, 'utf8');
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = mb_strtoupper($adresse, 'utf8');

        return $this;
    }

    public function getNotePourValiderUneMatiere(): ?float
    {
        return $this->notePourValiderUneMatiere;
    }

    public function setNotePourValiderUneMatiere(?float $notePourValiderUneMatiere): self
    {
        $this->notePourValiderUneMatiere = $notePourValiderUneMatiere;

        return $this;
    }

    public function getIsValidationModulaire(): ?bool
    {
        return $this->isValidationModulaire;
    }

    public function setIsValidationModulaire(bool $isValidationModulaire): self
    {
        $this->isValidationModulaire = $isValidationModulaire;

        return $this;
    }

    public function getNotePourValiderUnModule(): ?float
    {
        return $this->notePourValiderUnModule;
    }

    public function setNotePourValiderUnModule(?float $notePourValiderUnModule): self
    {
        $this->notePourValiderUnModule = $notePourValiderUnModule;

        return $this;
    }

    public function getNoteEliminatoire(): ?float
    {
        return $this->noteEliminatoire;
    }

    public function setNoteEliminatoire(?float $noteEliminatoire): self
    {
        $this->noteEliminatoire = $noteEliminatoire;

        return $this;
    }

    public function getBoitePostale(): ?string
    {
        return $this->boitePostale;
    }

    public function setBoitePostale(?string $boitePostale): self
    {
        $this->boitePostale = $boitePostale;

        return $this;
    }

    public function getIsSREcraseSN(): ?bool
    {
        return $this->isSREcraseSN;
    }

    public function setIsSREcraseSN(bool $isSREcraseSN): self
    {
        $this->isSREcraseSN = $isSREcraseSN;

        return $this;
    }

    public function getIsRattrapageSurToutesLesMatieres(): ?bool
    {
        return $this->isRattrapageSurToutesLesMatieres;
    }

    public function setIsRattrapageSurToutesLesMatieres(bool $isRattrapageSurToutesLesMatieres): self
    {
        $this->isRattrapageSurToutesLesMatieres = $isRattrapageSurToutesLesMatieres;

        return $this;
    }

    public function getNomEcoleEn(): ?string
    {
        return mb_strtoupper($this->nomEcoleEn, 'utf8');
    }

    public function setNomEcoleEn(?string $nomEcoleEn): self
    {
        $this->nomEcoleEn = mb_strtoupper($nomEcoleEn, 'utf8');

        return $this;
    }

    public function getLogoUniversity(): ?string
    {
        return $this->logoUniversity;
    }

    public function setLogoUniversity(?string $logoUniversity): self
    {
        $this->logoUniversity = $logoUniversity;

        return $this;
    }

    public function getPourcentageECForADC(): ?int
    {
        return $this->pourcentageECForADC;
    }

    public function setPourcentageECForADC(int $pourcentageECForADC): self
    {
        $this->pourcentageECForADC = $pourcentageECForADC;

        return $this;
    }

    /**
     * Get the value of logoEcoleBase64
     */ 
    public function getLogoEcoleBase64()
    {
        return $this->logoEcoleBase64;
    }

    /**
     * Get the value of logoUniversityBase64
     */ 
    public function getLogoUniversityBase64()
    {
        return $this->logoUniversityBase64;
    }

    /**
     * Set the value of logoUniversityBase64
     *
     * @return  self
     */ 
    public function setLogoUniversityBase64($logoUniversityBase64)
    {
        $this->logoUniversityBase64 = $logoUniversityBase64;

        return $this;
    }

    /**
     * Set the value of logoEcoleBase64
     *
     * @return  self
     */ 
    public function setLogoEcoleBase64($logoEcoleBase64)
    {
        $this->logoEcoleBase64 = $logoEcoleBase64;

        return $this;
    }

    /**
     * Get the value of filigrane
     */ 
    public function getFiligrane()
    {
        return $this->filigrane;
    }

    /**
     * Set the value of filigrane
     *
     * @return  self
     */ 
    public function setFiligrane($filigrane)
    {
        $this->filigrane = $filigrane;

        return $this;
    }
}
