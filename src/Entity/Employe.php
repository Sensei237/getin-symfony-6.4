<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Cette classe permet de gerer les employes de l'ecole. 
 * - id
 * - service
 * - nom
 * - prenom
 * - dateDeNaissance
 * - lieuDeNaissance
 * - sexe
 * - telephone
 * - photo
 * - telephone2
 * - adresseEmail
 * - grade
 * - situationMatrimoniale
 * - nombreDenfants
 * - nomConjoint
 * - telephoneConjoint
 */
#[ORM\Entity(repositoryClass: "App\Repository\EmployeRepository")]
#[ORM\EntityListeners(["App\EntityListener\EmployeListener"])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ["telephone"], errorPath: "telephone", message: "Ce numéro de téléphone existe déjà")]
#[UniqueEntity(fields: ["telephone2"], errorPath: "telephone2", message: "Ce numéro de téléphone existe déjà")]
#[UniqueEntity(fields: ["adresseEmail"], errorPath: "adresseEmail", message: "Cet email est déjà utilisé")]
class Employe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Service", inversedBy: "employes")]
    #[Assert\NotBlank]
    private $service;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $nom;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $prenom;

    #[ORM\Column(type: "date")]
    #[Assert\NotBlank]
    #[Assert\Date]
    private $dateDeNaissance;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank]
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

    #[ORM\Column(type: "string", length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 9,
        max: 9,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $telephone;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $photo;

    public $image;

    #[ORM\Column(type: "string", length: 20, nullable: true, unique: true)]
    #[Assert\Length(
        min: 9,
        max: 9,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $telephone2;

    #[ORM\Column(type: "string", length: 100, nullable: true, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email(message: "'{{ value }}' n'est pas un email valide.")]
    private $adresseEmail;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    #[Assert\NotBlank]
    private $grade;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank]
    private $situationMatrimoniale;

    #[ORM\Column(type: "integer")]
    #[Assert\NotBlank]
    #[Assert\Type(
        type: "integer",
        message: "Doit être un entier positif"
    )]
    #[Assert\GreaterThanOrEqual(0)]
    private $nombreDenfants;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $nomConjoint;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    #[Assert\Length(
        min: 6,
        max: 12,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $telephoneConjoint;

    #[ORM\OneToOne(targetEntity: "App\Entity\User", mappedBy: "employe")]
    private $user;

    #[ORM\Column(type: "string", length: 255, nullable: true, unique: true)]
    private $reference;

    #[ORM\Column(type: "boolean")]
    private $isVisible;

    public $nomComplet;

    #[ORM\Column(type: "boolean")]
    private $isGone;

    public function __construct()
    {
        $this->isVisible = true;
        $this->isGone = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): self
    {
        $this->service = $service;

        return $this;
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
        return strtoupper($this->sexe) === 'M' ? 'Masculin' : 'Feminin';
    }

    public function setSexe(string $sexe): self
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;

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

    public function getGrade(): ?string
    {
        return $this->grade;
    }

    public function setGrade(?string $grade): self
    {
        $this->grade = $grade;

        return $this;
    }

    public function getSituationMatrimoniale(): ?string
    {
        return $this->situationMatrimoniale;
    }

    public function setSituationMatrimoniale(string $situationMatrimoniale): self
    {
        $this->situationMatrimoniale = $situationMatrimoniale;

        return $this;
    }

    public function getNombreDenfants(): ?int
    {
        return $this->nombreDenfants;
    }

    public function setNombreDenfants(int $nombreDenfants): self
    {
        $this->nombreDenfants = $nombreDenfants;

        return $this;
    }

    public function getNomConjoint(): ?string
    {
        return $this->nomConjoint;
    }

    public function setNomConjoint(?string $nomConjoint): self
    {
        $this->nomConjoint = $nomConjoint;

        return $this;
    }

    public function getTelephoneConjoint(): ?string
    {
        return $this->telephoneConjoint;
    }

    public function setTelephoneConjoint(?string $telephoneConjoint): self
    {
        $this->telephoneConjoint = $telephoneConjoint;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = time().'-'.mb_strtolower(trim(\str_replace(' ', '-', str_replace('é', 'e', str_replace('è', 'e', str_replace('à', 'a', str_replace('ê', 'e', mb_strtolower($reference, 'UTF8'))))))));

        return $this;
    }

    public function getIsVisible(): ?bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): self
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    public function getNomComplet(): ?string
    {
        return $this->nom.' '.$this->prenom;
    }

    public function getIsGone(): ?bool
    {
        return $this->isGone;
    }

    public function setIsGone(bool $isGone): self
    {
        $this->isGone = $isGone;

        return $this;
    }
}
