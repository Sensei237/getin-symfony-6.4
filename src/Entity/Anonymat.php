<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\AnonymatRepository")]
class Anonymat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Contrat", inversedBy: "anonymats")]
    #[ORM\JoinColumn(nullable: false)]
    private $contrat;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Examen", inversedBy: "anonymats")]
    #[ORM\JoinColumn(nullable: false)]
    private $examen;

    #[ORM\Column(type: "string", length: 255)]
    private $anonymat;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtudiantInscris(): ?EtudiantInscris
    {
        return null;
    }

    public function setEtudiantInscris(?EtudiantInscris $etudiantInscris): self
    {

        return $this;
    }

    public function getContrat(): ?Contrat
    {
        return $this->contrat;
    }

    public function setContrat(?Contrat $contrat): self
    {
        $this->contrat = $contrat;

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

    public function getAnonymat(): ?string
    {
        return $this->anonymat;
    }

    public function setAnonymat(string $anonymat): self
    {
        $this->anonymat = $anonymat;

        return $this;
    }
}
