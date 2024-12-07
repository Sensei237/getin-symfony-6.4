<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\PaiementRepository")]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "App\Entity\EtudiantInscris", inversedBy: "paiements")]
    private $etudiantInscris;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Tranche", inversedBy: "paiements")]
    private $tranche;

    #[ORM\Column(type: "boolean")]
    private $isPaied;

    #[ORM\Column(type: "string", length: 255)]
    private $numeroQuitus;

    #[ORM\Column(type: "datetime")]
    private $saveAt;

    public function __construct()
    {
        $this->saveAt = new \DateTime();
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

    public function getTranche(): ?Tranche
    {
        return $this->tranche;
    }

    public function setTranche(?Tranche $tranche): self
    {
        $this->tranche = $tranche;

        return $this;
    }

    public function getIsPaied(): ?bool
    {
        return $this->isPaied;
    }

    public function setIsPaied(bool $isPaied): self
    {
        $this->isPaied = $isPaied;

        return $this;
    }

    public function getNumeroQuitus(): ?string
    {
        return $this->numeroQuitus;
    }

    public function setNumeroQuitus(string $numeroQuitus): self
    {
        $this->numeroQuitus = $numeroQuitus;

        return $this;
    }

    public function getSaveAt(): ?\DateTimeInterface
    {
        return $this->saveAt;
    }

    public function setSaveAt(\DateTimeInterface $saveAt): self
    {
        $this->saveAt = $saveAt;

        return $this;
    }
}
