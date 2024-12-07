<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Cette classe represente en fait une matiere d'une classe. Puisque un module a plusieurs EC et que un EC peut etre dispense dans plusieurs salles de classes, cette classe permet donc de savoir les modules qui utilisent la matiere comme EC. 
 * La liste des proprietes disponibles est donc : 
 * - id : propriete permettant d'identifier de facon unique une matiere d'un module donnee. Seule la methode getId() est disponible. 
 * - module : Permet de savoir dans quel module se trouve cette matiere et donc dans quelle classe. C'est donc une instance de la classe Module. Methodes : getModule() et setModule(). 
 * - ec : c'est une instance de la classe EC qui permet de savoir la matiere qui est concernee ici. Methodes : getEc() et setEC(). 
 * - credit : c'est le coefficient de la matiere. Methodes associees : getCredit() et setCredit(). 
 * - semestre : le semestre auquel passe la matiere. Methodes : getSemestre() et setSemestre(). 
 * -------------------------------------------------------------------------------------
 * D'autres methodes permettent sont disponibles
 * - getContrats() : permet de recuperer tous les contrats qui sont associes a cette matiere. 
 * 
 */
#[ORM\Entity(repositoryClass: "App\Repository\ECModuleRepository")]
class ECModule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Module", inversedBy: "eCModules")]
    #[ORM\JoinColumn(nullable: false)]
    private $module;

    #[ORM\ManyToOne(targetEntity: "App\Entity\EC", inversedBy: "eCModules")]
    #[ORM\JoinColumn(nullable: false)]
    private $ec;

    #[ORM\Column(type: "float")]
    private $credit;

    #[ORM\Column(type: "integer")]
    private $semestre;

    #[ORM\OneToMany(targetEntity: "App\Entity\MatiereASaisir", mappedBy: "ecModule")]
    private $matiereASaisirs;

    #[ORM\OneToMany(targetEntity: "App\Entity\Contrat", mappedBy: "ecModule")]
    private $contrats;

    #[ORM\OneToOne(targetEntity: "App\Entity\PourcentageECModule", mappedBy: "ecModule", cascade: ["persist", "remove"])]
    private $pourcentageECModule;

    #[ORM\Column(type: "boolean")]
    private $isOptionnal;

    public function __construct()
    {
        $this->matiereASaisirs = new ArrayCollection();
        $this->contrats = new ArrayCollection();
        $this->isOptionnal = false;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEc(): ?EC
    {
        return $this->ec;
    }

    public function setEc(?EC $ec): self
    {
        $this->ec = $ec;

        return $this;
    }

    public function getCredit(): ?float
    {
        return $this->credit;
    }

    public function setCredit(float $credit): self
    {
        $this->credit = $credit;

        return $this;
    }

    public function getSemestre(): ?int
    {
        return $this->semestre;
    }

    public function setSemestre(int $semestre): self
    {
        $this->semestre = $semestre;

        return $this;
    }

    /**
     * @return Collection|MatiereASaisir[]
     */
    public function getMatiereASaisirs(): Collection
    {
        return $this->matiereASaisirs;
    }

    public function addMatiereASaisir(MatiereASaisir $matiereASaisir): self
    {
        if (!$this->matiereASaisirs->contains($matiereASaisir)) {
            $this->matiereASaisirs[] = $matiereASaisir;
            $matiereASaisir->setEcModule($this);
        }

        return $this;
    }

    public function removeMatiereASaisir(MatiereASaisir $matiereASaisir): self
    {
        if ($this->matiereASaisirs->contains($matiereASaisir)) {
            $this->matiereASaisirs->removeElement($matiereASaisir);
            // set the owning side to null (unless already changed)
            if ($matiereASaisir->getEcModule() === $this) {
                $matiereASaisir->setEcModule(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Contrat[]
     */
    public function getContrats(): Collection
    {
        return $this->contrats;
    }

    public function addContrat(Contrat $contrat): self
    {
        if (!$this->contrats->contains($contrat)) {
            $this->contrats[] = $contrat;
            $contrat->setEcModule($this);
        }

        return $this;
    }

    public function removeContrat(Contrat $contrat): self
    {
        if ($this->contrats->contains($contrat)) {
            $this->contrats->removeElement($contrat);
            // set the owning side to null (unless already changed)
            if ($contrat->getEcModule() === $this) {
                $contrat->setEcModule(null);
            }
        }

        return $this;
    }

    public function getNom(): string
    {
        return $this->ec->getIntitule().' ('.$this->module->getClasse()->getNom().')';
    }

    public function getPourcentageECModule(): ?PourcentageECModule
    {
        return $this->pourcentageECModule;
    }

    public function setPourcentageECModule(PourcentageECModule $pourcentageECModule): self
    {
        $this->pourcentageECModule = $pourcentageECModule;

        // set the owning side of the relation if necessary
        if ($this !== $pourcentageECModule->getEcModule()) {
            $pourcentageECModule->setEcModule($this);
        }

        return $this;
    }

    public function getIsOptionnal(): ?bool
    {
        return $this->isOptionnal;
    }

    public function setIsOptionnal(bool $isOptionnal): self
    {
        $this->isOptionnal = $isOptionnal;

        return $this;
    }
}
