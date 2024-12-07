<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: "App\Repository\TypeDePaiementRepository")]
#[UniqueEntity("denomination")]
#[ORM\EntityListeners(["App\EntityListener\TypePaiementListener"])]
#[ORM\HasLifecycleCallbacks]
class TypeDePaiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 150)]
    private $denomination;

    #[ORM\Column(type: "string", length: 255)]
    private $slug;

    #[ORM\OneToMany(targetEntity: "App\Entity\PaiementClasse", mappedBy: "typeDePaiement", cascade: ["persist", "remove"])]
    private $paiementClasses;

    public function __construct()
    {
        $this->paiementClasses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDenomination(): ?string
    {
        return mb_strtoupper($this->denomination, 'UTF8');
    }

    public function setDenomination(string $denomination): self
    {
        $this->denomination = mb_strtoupper($denomination, 'UTF8');

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = mb_strtolower(trim(\str_replace(' ', '-', str_replace('é', 'e', str_replace('è', 'e', str_replace('à', 'a', str_replace('ê', 'e', mb_strtolower($slug, 'UTF8'))))))));

        return $this;
    }

    /**
     * @return Collection|PaiementClasse[]
     */
    public function getPaiementClasses(): Collection
    {
        return $this->paiementClasses;
    }

    public function addPaiementClass(PaiementClasse $paiementClass): self
    {
        if (!$this->paiementClasses->contains($paiementClass)) {
            $this->paiementClasses[] = $paiementClass;
            $paiementClass->setTypeDePaiement($this);
        }

        return $this;
    }

    public function removePaiementClass(PaiementClasse $paiementClass): self
    {
        if ($this->paiementClasses->contains($paiementClass)) {
            $this->paiementClasses->removeElement($paiementClass);
            // set the owning side to null (unless already changed)
            if ($paiementClass->getTypeDePaiement() === $this) {
                $paiementClass->setTypeDePaiement(null);
            }
        }

        return $this;
    }
}
