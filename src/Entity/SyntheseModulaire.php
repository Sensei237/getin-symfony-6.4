<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\SyntheseModulaireRepository")]
class SyntheseModulaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "App\Entity\EtudiantInscris", inversedBy: "synthesesModulaires")]
    #[ORM\JoinColumn(nullable: false)]
    private $etudiantInscris;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Module", inversedBy: "synthesesModulaires")]
    #[ORM\JoinColumn(nullable: false)]
    private $module;

    #[ORM\Column(type: "float", nullable: true)]
    private $moyenne;

    #[ORM\Column(type: "float", nullable: true)]
    private $note;

    #[ORM\Column(type: "integer", nullable: true)]
    private $credit;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private $grade;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private $decision;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Examen")]
    private $examen;

    #[ORM\Column(type: "float", nullable: true)]
    private $points;

    #[ORM\Column(type: "integer", nullable: true)]
    private $creditValider;

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

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): self
    {
        $this->module = $module;

        return $this;
    }

    public function getMoyenne(): ?float
    {
        return $this->moyenne;
    }

    public function setMoyenne(?float $moyenne): self
    {
        $this->moyenne = $moyenne;

        return $this;
    }

    public function getNote(): ?float
    {
        return $this->note;
    }

    public function setNote(?float $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getCredit(): ?int
    {
        return $this->credit;
    }

    public function setCredit(?int $credit): self
    {
        $this->credit = $credit;

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

    public function getExamen(): ?Examen
    {
        return $this->examen;
    }

    public function setExamen(?Examen $examen): self
    {
        $this->examen = $examen;

        return $this;
    }

    public function getPoints(): ?float
    {
        return $this->points;
    }

    public function setPoints(?float $points): self
    {
        $this->points = $points;

        return $this;
    }

    public function getCreditValider(): ?int
    {
        return $this->creditValider;
    }

    public function setCreditValider(?int $creditValider): self
    {
        $this->creditValider = $creditValider;

        return $this;
    }
}
