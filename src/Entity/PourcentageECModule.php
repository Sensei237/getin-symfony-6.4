<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\PourcentageECModuleRepository")]
class PourcentageECModule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "integer", nullable: true)]
    private $pourcentageCC;

    #[ORM\Column(type: "integer", nullable: true)]
    private $pourcentageTPE;

    #[ORM\Column(type: "integer", nullable: true)]
    private $pourcentageTP;

    #[ORM\Column(type: "integer", nullable: true)]
    private $pourcentageExam;

    #[ORM\OneToOne(targetEntity: "App\Entity\ECModule", inversedBy: "pourcentageECModule", cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(nullable: false)]
    private $ecModule;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPourcentageCC(): ?int
    {
        return $this->pourcentageCC;
    }

    public function setPourcentageCC(?int $pourcentageCC): self
    {
        $this->pourcentageCC = $pourcentageCC;

        return $this;
    }

    public function getPourcentageTPE(): ?int
    {
        return $this->pourcentageTPE;
    }

    public function setPourcentageTPE(?int $pourcentageTPE): self
    {
        $this->pourcentageTPE = $pourcentageTPE;

        return $this;
    }

    public function getPourcentageTP(): ?int
    {
        return $this->pourcentageTP;
    }

    public function setPourcentageTP(?int $pourcentageTP): self
    {
        $this->pourcentageTP = $pourcentageTP;

        return $this;
    }

    public function getPourcentageExam(): ?int
    {
        return $this->pourcentageExam;
    }

    public function setPourcentageExam(?int $pourcentageExam): self
    {
        $this->pourcentageExam = $pourcentageExam;

        return $this;
    }

    public function getEcModule(): ?ECModule
    {
        return $this->ecModule;
    }

    public function setEcModule(ECModule $ecModule): self
    {
        $this->ecModule = $ecModule;

        return $this;
    }
}
