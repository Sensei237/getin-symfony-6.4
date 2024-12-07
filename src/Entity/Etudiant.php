<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Cette classe permet de definir toutes les informations personnelles d'un etudiant
 * La liste des proprietes est : 
 * - id : seule la methode getId() est disponible. 
 * Les autres proprietes ont leur getter et leur setter
 * - nom
 * - prenom : cette propriete n'est pas obligatoire. 
 * - dateDeNaissance
 * - lieuDeNaissance
 * - sexe
 * - nomDuPere
 * - numeroDeTelephoneDuPere
 * - nomDeLaMere
 * - numeroDeTelephoneDeLaMere
 * - adresseDesParents
 * - telephone1 : le premier numero de telephone de l'etudiant. Il est obligatoire. 
 * - telephone2 : il n'est pas obligatoire. 
 * - adresseEmail
 * - nombreDEnfants
 * - situationMatrimoniale
 * - civilite
 * - diplomeAcademiqueMax
 * - anneeObtentionDiplomeAcademiqueMax
 * - diplomeDEntre
 * - anneeObtentionDiplomeEntre
 * - photo
 * - professionDuPere
 * - professionDeLaMere
 * - personneAContacterEnCasDeProbleme
 * - numeroDUrgence
 * - departement
 */
#[ORM\Entity(repositoryClass: "App\Repository\EtudiantRepository")]
#[Vich\Uploadable]
#[ORM\EntityListeners(['App\EntityListener\EtudiantListener'])]
#[ORM\HasLifecycleCallbacks]
class Etudiant
{
    #[ORM\Id()]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: "integer")]
    #[Groups("public")]
    private $id;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    #[Groups("public")]
    private $nom;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    #[Groups("public")]
    private $prenom;

    #[ORM\Column(type: "date")]
    #[Assert\NotBlank]
    #[Assert\Date]
    private $dateDeNaissance;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $lieuDeNaissance;

    #[ORM\Column(type: "string", length: 20)]
    #[Assert\NotBlank]
    private $sexe;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $nomDuPere;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 9,
        max: 9,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $numeroDeTelephoneDuPere;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $nomDeLaMere;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 9,
        max: 9,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $numeroDeTelephoneDeLaMere;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $adresseDesParents;

    #[ORM\Column(type: "string", length: 20, nullable: true, unique: true)]
    #[Assert\Length(
        min: 9,
        max: 9,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $telephone1;

    #[ORM\Column(type: "string", length: 255, nullable: true, unique: true)]
    #[Assert\Length(
        min: 9,
        max: 9,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $telephone2;

    #[ORM\Column(type: "string", length: 255, nullable: true, unique: true)]
    #[Assert\Email(
        message: "'{{ value }}' n'est pas un email valide."
    )]
    private $adresseEmail;

    #[ORM\Column(type: "integer")]
    #[Assert\NotBlank]
    #[Assert\Type(
        type: "integer",
        message: "Doit être un entier positif"
    )]
    #[Assert\GreaterThanOrEqual(0)]
    private $nombreDEnfants;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 90,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $situationMatrimoniale;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 40,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $civilite;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $diplomeAcademiqueMax;

    #[ORM\Column(type: "integer", nullable: true)]
    #[Assert\Type(
        type: "integer",
        message: "Doit être un entier positif"
    )]
    #[Assert\GreaterThanOrEqual(1970)]
    private $anneeObtentionDiplomeAcademiqueMax;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $diplomeDEntre;

    #[ORM\Column(type: "integer", nullable: true)]
    #[Assert\Type(
        type: "integer",
        message: "Doit être un entier positif"
    )]
    #[Assert\GreaterThanOrEqual(1970)]
    private $anneeObtentionDiplomeEntre;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $photo;

    private $photoBase64;

    #[Vich\UploadableField(mapping: "etudiant_photo", fileNameProperty: "photo")]
    #[Assert\NotNull(groups: ['media_object_create'])]
    public ?File $image = null;

    public $region;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $professionDuPere;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $professionDeLaMere;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $personneAContacterEnCasDeProbleme;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    #[Assert\Length(
        min: 6,
        max: 12,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $numeroDUrgence;

    #[ORM\OneToMany(targetEntity: "App\Entity\EtudiantInscris", mappedBy: "etudiant")]
    private $etudiantInscris;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Departement", inversedBy: "etudiants")]
    private $departement;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $autreDiplomeMax;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $autreDiplomeEntre;

    #[ORM\Column(type: "string", length: 25, nullable: true)]
    #[Groups("public")]
    private $matricule;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Pays", inversedBy: "etudiants")]
    private $pays;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $localisation;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\Length(
        min: 20,
        max: 1000,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $skills;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $autreFormation;

    #[ORM\Column(type: "string", length: 25, nullable: true)]
    private $codeSecret;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUpdateAt = null;

    public function __construct()
    {
        $this->etudiantInscris = new ArrayCollection();
        $this->sexe = "M";
        $this->dateDeNaissance = new \DateTime('2005-01-01');
        $this->nombreDEnfants = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNomComplet(): ?string
    {
        return trim(mb_strtoupper(trim($this->getNom()), "UTF8")." ".trim(ucwords($this->getPrenom()))); 
    }

    public function getDateDeNaissance(): ?\DateTimeInterface
    {
        return $this->dateDeNaissance;
    }

    public function setDateDeNaissance(\DateTimeInterface $dateDeNaissance): self
    {
        $this->dateDeNaissance = $dateDeNaissance;

        return $this;
    }

    public function getLieuDeNaissance(): ?string
    {
        return $this->lieuDeNaissance;
    }

    public function setLieuDeNaissance(string $lieuDeNaissance): self
    {
        $this->lieuDeNaissance = $lieuDeNaissance;

        return $this;
    }

    public function getSexe(): ?string
    {
        return strtoupper($this->sexe);
    }

    public function getSexeL(): ?string
    {
        return strtoupper($this->sexe) === 'M' ? 'Masculin' : 'Feminin';
    }

    public function setSexe(string $sexe): self
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getNomDuPere(): ?string
    {
        return $this->nomDuPere;
    }

    public function setNomDuPere(?string $nomDuPere): self
    {
        $this->nomDuPere = $nomDuPere;

        return $this;
    }

    public function getNumeroDeTelephoneDuPere(): ?string
    {
        return $this->numeroDeTelephoneDuPere;
    }

    public function setNumeroDeTelephoneDuPere(?string $numeroDeTelephoneDuPere): self
    {
        $this->numeroDeTelephoneDuPere = $numeroDeTelephoneDuPere;

        return $this;
    }

    public function getNomDeLaMere(): ?string
    {
        return $this->nomDeLaMere;
    }

    public function setNomDeLaMere(?string $nomDeLaMere): self
    {
        $this->nomDeLaMere = $nomDeLaMere;

        return $this;
    }

    public function getNumeroDeTelephoneDeLaMere(): ?string
    {
        return $this->numeroDeTelephoneDeLaMere;
    }

    public function setNumeroDeTelephoneDeLaMere(?string $numeroDeTelephoneDeLaMere): self
    {
        $this->numeroDeTelephoneDeLaMere = $numeroDeTelephoneDeLaMere;

        return $this;
    }

    public function getAdresseDesParents(): ?string
    {
        return $this->adresseDesParents;
    }

    public function setAdresseDesParents(?string $adresseDesParents): self
    {
        $this->adresseDesParents = $adresseDesParents;

        return $this;
    }

    public function getTelephone1(): ?string
    {
        return $this->telephone1;
    }

    public function setTelephone1(?string $telephone1): self
    {
        $this->telephone1 = $telephone1;

        return $this;
    }

    public function getTelephone2(): ?string
    {
        return $this->telephone2;
    }

    public function setTelephone2(?string $telephone2): self
    {
        $this->telephone2 = $telephone2;

        return $this;
    }

    public function getAdresseEmail(): ?string
    {
        return $this->adresseEmail;
    }

    public function setAdresseEmail(?string $adresseEmail): self
    {
        $this->adresseEmail = $adresseEmail;

        return $this;
    }

    public function getNombreDEnfants(): ?int
    {
        return $this->nombreDEnfants;
    }

    public function setNombreDEnfants(?int $nombreDEnfants): self
    {
        $this->nombreDEnfants = $nombreDEnfants?$nombreDEnfants:0;

        return $this;
    }

    public function getSituationMatrimoniale(): ?string
    {
        return $this->situationMatrimoniale;
    }

    public function setSituationMatrimoniale(?string $situationMatrimoniale): self
    {
        $this->situationMatrimoniale = $situationMatrimoniale;

        return $this;
    }

    public function getCivilite(): ?string
    {
        return $this->civilite;
    }

    public function setCivilite(?string $civilite): self
    {
        $this->civilite = $civilite;

        return $this;
    }

    public function getDiplomeAcademiqueMax(): ?string
    {
        return $this->diplomeAcademiqueMax;
    }

    public function setDiplomeAcademiqueMax(?string $diplomeAcademiqueMax): self
    {
        $this->diplomeAcademiqueMax = $diplomeAcademiqueMax;

        return $this;
    }

    public function getAnneeObtentionDiplomeAcademiqueMax(): ?int
    {
        return $this->anneeObtentionDiplomeAcademiqueMax;
    }

    public function setAnneeObtentionDiplomeAcademiqueMax(?int $anneeObtentionDiplomeAcademiqueMax): self
    {
        $this->anneeObtentionDiplomeAcademiqueMax = $anneeObtentionDiplomeAcademiqueMax;

        return $this;
    }

    public function getDiplomeDEntre(): ?string
    {
        return $this->diplomeDEntre;
    }

    public function setDiplomeDEntre(?string $diplomeDEntre): self
    {
        $this->diplomeDEntre = $diplomeDEntre;

        return $this;
    }

    public function getAnneeObtentionDiplomeEntre(): ?int
    {
        return $this->anneeObtentionDiplomeEntre;
    }

    public function setAnneeObtentionDiplomeEntre(?int $anneeObtentionDiplomeEntre): self
    {
        $this->anneeObtentionDiplomeEntre = $anneeObtentionDiplomeEntre;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    public function getProfessionDuPere(): ?string
    {
        return $this->professionDuPere;
    }

    public function setProfessionDuPere(?string $professionDuPere): self
    {
        $this->professionDuPere = $professionDuPere;

        return $this;
    }

    public function getProfessionDeLaMere(): ?string
    {
        return $this->professionDeLaMere;
    }

    public function setProfessionDeLaMere(?string $professionDeLaMere): self
    {
        $this->professionDeLaMere = $professionDeLaMere;

        return $this;
    }

    public function getPersonneAContacterEnCasDeProbleme(): ?string
    {
        return $this->personneAContacterEnCasDeProbleme;
    }

    public function setPersonneAContacterEnCasDeProbleme(?string $personneAContacterEnCasDeProbleme): self
    {
        $this->personneAContacterEnCasDeProbleme = $personneAContacterEnCasDeProbleme;

        return $this;
    }

    public function getNumeroDUrgence(): ?string
    {
        return $this->numeroDUrgence;
    }

    public function setNumeroDUrgence(?string $numeroDUrgence): self
    {
        $this->numeroDUrgence = $numeroDUrgence;

        return $this;
    }

    /**
     * @return Collection|EtudiantInscris[]
     */
    public function getEtudiantInscris(): Collection
    {
        return $this->etudiantInscris;
    }

    public function addEtudiantInscri(EtudiantInscris $etudiantInscri): self
    {
        if (!$this->etudiantInscris->contains($etudiantInscri)) {
            $this->etudiantInscris[] = $etudiantInscri;
            $etudiantInscri->setEtudiant($this);
        }

        return $this;
    }

    public function removeEtudiantInscri(EtudiantInscris $etudiantInscri): self
    {
        if ($this->etudiantInscris->contains($etudiantInscri)) {
            $this->etudiantInscris->removeElement($etudiantInscri);
            // set the owning side to null (unless already changed)
            if ($etudiantInscri->getEtudiant() === $this) {
                $etudiantInscri->setEtudiant(null);
            }
        }

        return $this;
    }

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(?Departement $departement): self
    {
        $this->departement = $departement;

        return $this;
    }

    public function getAutreDiplomeMax(): ?string
    {
        return $this->autreDiplomeMax;
    }

    public function setAutreDiplomeMax(?string $autreDiplomeMax): self
    {
        $this->autreDiplomeMax = $autreDiplomeMax;

        return $this;
    }

    public function getAutreDiplomeEntre(): ?string
    {
        return $this->autreDiplomeEntre;
    }

    public function setAutreDiplomeEntre(?string $autreDiplomeEntre): self
    {
        $this->autreDiplomeEntre = $autreDiplomeEntre;

        return $this;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(?string $matricule): self
    {
        $this->matricule = trim(htmlspecialchars(strtoupper($matricule)));

        return $this;
    }

    public function getPays(): ?Pays
    {
        return $this->pays;
    }

    public function setPays(?Pays $pays): self
    {
        $this->pays = $pays;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): self
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getSkills(): ?string
    {
        return $this->skills;
    }

    public function setSkills(?string $skills): self
    {
        $this->skills = $skills;

        return $this;
    }

    public function getAutreFormation(): ?string
    {
        return $this->autreFormation;
    }

    public function setAutreFormation(?string $autreFormation): self
    {
        $this->autreFormation = $autreFormation;

        return $this;
    }

    public function getAsArray()
    {
        return [
            'nomComplet' => $this->getNomComplet(),
            'dateDeNaissance' => date_format($this->getDateDeNaissance(), 'd/m/Y'),
            'lieuDeNaissance' => $this->getLieuDeNaissance(),
            'sexe' => $this->getSexe(),
            'photoBase64' => $this->getPhotoBase64(),
            'matricule' => $this->getMatricule(),
            'nom' => $this->getNom(),
            'prenom' => $this->getPrenom(),
            'nomDuPere' => $this->getNomDuPere(),
            'numeroDeTelephoneDuPere' => $this->getNumeroDeTelephoneDuPere(),
            'nomDeLaMere' => $this->getNomDeLaMere(),
            'numeroDeTelephoneDeLaMere' => $this->getNumeroDeTelephoneDeLaMere(),
            'adresseDesParents' => $this->getAdresseDesParents(),
            'telephone1' => $this->getTelephone1(),
            'telephone2' => $this->getTelephone2(),
            'adresseEmail' => $this->getAdresseDesParents(),
            'nombreDEnfants' => $this->getNombreDEnfants(),
            'situationMatrimoniale' => $this->getSituationMatrimoniale(),
            'civilite' => $this->getCivilite(),
            'diplomeAcademiqueMax' => $this->getDiplomeAcademiqueMax(),
            'anneeObtentionDiplomeAcademiqueMax' => $this->getAnneeObtentionDiplomeAcademiqueMax(),
            'diplomeDEntre' => $this->getDiplomeDEntre(),
            'anneeObtentionDiplomeEntre' => $this->getAnneeObtentionDiplomeEntre(),
            'professionDuPere' => $this->getProfessionDuPere(),
            'professionDeLaMere' => $this->getProfessionDeLaMere(),
            'personneAContacterEnCasDeProbleme' => $this->getPersonneAContacterEnCasDeProbleme(),
            'numeroDUrgence' => $this->getNumeroDUrgence(),
            'departement' => $this->getDepartement() ? $this->getDepartement()->getNom() : 'non definit',
            'region' => $this->getDepartement() && $this->getDepartement()->getRegion() ? $this->getDepartement()->getRegion()->getNom() : 'non definit',
        ];
    }

    public function getCodeSecret(): ?string
    {
        return $this->codeSecret;
    }

    public function setCodeSecret(?string $codeSecret): self
    {
        $this->codeSecret = $codeSecret;

        return $this;
    }

    /**
     * Get the value of photoBase64
     */ 
    public function getPhotoBase64()
    {
        return $this->photoBase64;
    }

    /**
     * Set the value of photoBase64
     *
     * @return  self
     */ 
    public function setPhotoBase64($photoBase64)
    {
        $this->photoBase64 = $photoBase64;

        return $this;
    }

    public function getLastUpdateAt(): ?\DateTimeImmutable
    {
        return $this->lastUpdateAt;
    }

    public function setLastUpdateAt(?\DateTimeImmutable $lastUpdateAt): static
    {
        $this->lastUpdateAt = $lastUpdateAt;

        return $this;
    }
}
