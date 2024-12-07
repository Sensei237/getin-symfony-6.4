<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\TrancheRepository")]
#[ORM\EntityListeners(["App\EntityListener\TrancheListener"])]
#[ORM\HasLifecycleCallbacks]
class Tranche
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 255)]
    private $denomination;

    #[ORM\Column(type: "integer")]
    private $montant;

    #[ORM\Column(type: "string", length: 255)]
    private $slug;

    #[ORM\ManyToOne(targetEntity: "App\Entity\PaiementClasse", inversedBy: "tranches")]
    private $paiementClasse;

    #[ORM\OneToMany(targetEntity: "App\Entity\Paiement", mappedBy: "tranche")]
    private $paiements;

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
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

    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(int $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = time().'-'.mb_strtolower(trim(\str_replace(' ', '-', str_replace('é', 'e', str_replace('è', 'e', str_replace('à', 'a', str_replace('ê', 'e', mb_strtolower($slug, 'UTF8'))))))));

        return $this;
    }

    public function getPaiementClasse(): ?PaiementClasse
    {
        return $this->paiementClasse;
    }

    public function setPaiementClasse(?PaiementClasse $paiementClasse): self
    {
        $this->paiementClasse = $paiementClasse;

        return $this;
    }

    /**
     * @return Collection|Paiement[]
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): self
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements[] = $paiement;
            $paiement->setTranche($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): self
    {
        if ($this->paiements->contains($paiement)) {
            $this->paiements->removeElement($paiement);
            // set the owning side to null (unless already changed)
            if ($paiement->getTranche() === $this) {
                $paiement->setTranche(null);
            }
        }

        return $this;
    }
}
