<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: "App\Repository\PaiementClasseRepository")]
#[UniqueEntity(
    fields: ["classe", "typeDePaiement"],
    errorPath: "typeDePaiement",
    message: "Ce type de paiement est deja lié à cette classe"
)]
class PaiementClasse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Classe", inversedBy: "paiementClasses")]
    private $classe;

    #[ORM\ManyToOne(targetEntity: "App\Entity\TypeDePaiement", inversedBy: "paiementClasses")]
    private $typeDePaiement;

    #[ORM\Column(type: "integer")]
    private $montant;

    #[ORM\Column(type: "boolean")]
    private $isObligatoire;

    #[ORM\OneToMany(targetEntity: "App\Entity\Tranche", mappedBy: "paiementClasse", cascade: ["persist", "remove"])]
    private $tranches;
    
    public $classes = [];

    public function __construct()
    {
        $this->tranches = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTypeDePaiement(): ?TypeDePaiement
    {
        return $this->typeDePaiement;
    }

    public function setTypeDePaiement(?TypeDePaiement $typeDePaiement): self
    {
        $this->typeDePaiement = $typeDePaiement;

        return $this;
    }

    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(int $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getIsObligatoire(): ?bool
    {
        return $this->isObligatoire;
    }

    public function setIsObligatoire(bool $isObligatoire): self
    {
        $this->isObligatoire = $isObligatoire;

        return $this;
    }

    /**
     * @return Collection|Tranche[]
     */
    public function getTranches(): Collection
    {
        return $this->tranches;
    }

    public function addTranch(Tranche $tranch): self
    {
        if (!$this->tranches->contains($tranch)) {
            $this->tranches[] = $tranch;
            $tranch->setPaiementClasse($this);
        }

        return $this;
    }

    public function removeTranch(Tranche $tranch): self
    {
        if ($this->tranches->contains($tranch)) {
            $this->tranches->removeElement($tranch);
            // set the owning side to null (unless already changed)
            if ($tranch->getPaiementClasse() === $this) {
                $tranch->setPaiementClasse(null);
            }
        }

        return $this;
    }
}
