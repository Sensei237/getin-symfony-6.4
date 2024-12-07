<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\MatiereASaisirRepository")]
class MatiereASaisir
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "App\Entity\ECModule", inversedBy: "matiereASaisirs")]
    #[ORM\JoinColumn(nullable: false)]
    private $ecModule;

    public $ecsModule = [];
    public $filiere;
    public $specialite;
    public $classe;
    public $formation;

    #[ORM\ManyToOne(targetEntity: "App\Entity\User", inversedBy: "matiereASaisirs")]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Examen", inversedBy: "matiereASaisirs")]
    #[ORM\JoinColumn(nullable: false)]
    private $examen;

    #[ORM\Column(type: "datetime")]
    private $dateExpiration;

    #[ORM\Column(type: "boolean")]
    private $isSaisie;

    #[ORM\ManyToOne(targetEntity: "App\Entity\AnneeAcademique")]
    #[ORM\JoinColumn(nullable: false)]
    private $anneeAcademique;

    #[ORM\Column(type: "boolean")]
    private $isSaisiAnonym;

    public function __construct()
    {
        $this->isSaisiAnonym = false;
        $this->isSaisie = false;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getExamen(): ?Examen
    {
        return $this->examen;
    }

    public function setExamen(?Examen $examen): self
    {
        $this->examen = $examen;

        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(\DateTimeInterface $dateExpiration): self
    {
        $this->dateExpiration = $dateExpiration;

        return $this;
    }

    public function getIsSaisie(): ?bool
    {
        return $this->isSaisie;
    }

    public function setIsSaisie(bool $isSaisie): self
    {
        $this->isSaisie = $isSaisie;

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

    public function getIsSaisiAnonym(): ?bool
    {
        return $this->isSaisiAnonym;
    }

    public function setIsSaisiAnonym(bool $isSaisiAnonym): self
    {
        $this->isSaisiAnonym = $isSaisiAnonym;

        return $this;
    }

    public function hasExpired(): bool
    {
        $now = new \DateTime();

        return $now > $this->dateExpiration;
    }
}
