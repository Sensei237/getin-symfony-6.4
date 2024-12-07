<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\ModuleRepository")]
class Module
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 255)]
    private $intitule;

    #[ORM\Column(type: "string", length: 100)]
    private $code;

    #[ORM\Column(type: "string", length: 255)]
    private $slug;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Classe", inversedBy: "modules")]
    private $classe;

    #[ORM\ManyToOne(targetEntity: "App\Entity\AnneeAcademique", inversedBy: "modules")]
    #[ORM\JoinColumn(nullable: false)]
    private $anneeAcademique;

    #[ORM\OneToMany(targetEntity: "App\Entity\ECModule", mappedBy: "module")]
    private $eCModules;

    #[ORM\OneToMany(targetEntity: "App\Entity\SyntheseModulaire", mappedBy: "module", orphanRemoval: true)]
    private $synthesesModulaires;

    public function __construct()
    {
        $this->eCModules = new ArrayCollection();
        $this->synthesesModulaires = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntitule(): ?string
    {
        return mb_strtoupper($this->intitule, 'utf8');
    }

    public function setIntitule(string $intitule): self
    {
        $this->intitule = mb_strtoupper($intitule, 'utf8');

        return $this;
    }

    public function getCode(): ?string
    {
        return mb_strtoupper($this->code);
    }

    public function setCode(string $code): self
    {
        $this->code = mb_strtoupper($code);

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = mb_strtolower(trim(\str_replace([' ', '(', ')', '/', "\\", '.', '+', '*', '@', '!', '?', '>', '<', '\'', '"', '&', '%', '$', '#', ',', ';', ':'], '-', str_replace('é', 'e', str_replace('è', 'e', str_replace('à', 'a', str_replace('ê', 'e', mb_strtolower($slug, 'UTF8'))))))));

        return $this;
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

    public function getAnneeAcademique(): ?AnneeAcademique
    {
        return $this->anneeAcademique;
    }

    public function setAnneeAcademique(?AnneeAcademique $anneeAcademique): self
    {
        $this->anneeAcademique = $anneeAcademique;

        return $this;
    }

    /**
     * @return Collection|ECModule[]
     */
    public function getECModules(): Collection
    {
        return $this->eCModules;
    }

    public function addECModule(ECModule $eCModule): self
    {
        if (!$this->eCModules->contains($eCModule)) {
            $this->eCModules[] = $eCModule;
            $eCModule->setModule($this);
        }

        return $this;
    }

    public function removeECModule(ECModule $eCModule): self
    {
        if ($this->eCModules->contains($eCModule)) {
            $this->eCModules->removeElement($eCModule);
            // set the owning side to null (unless already changed)
            if ($eCModule->getModule() === $this) {
                $eCModule->setModule(null);
            }
        }

        return $this;
    }

    public function getAsArray()
    {

        return [
            'code' => $this->getCode(),
            'intitule' => $this->getIntitule(),
            'id' => $this->getId(),
        ];
    }

    /**
     * @return Collection|SyntheseModulaire[]
     */
    public function getSynthesesModulaires(): Collection
    {
        return $this->synthesesModulaires;
    }

    public function addSynthesesModulaire(SyntheseModulaire $synthesesModulaire): self
    {
        if (!$this->synthesesModulaires->contains($synthesesModulaire)) {
            $this->synthesesModulaires[] = $synthesesModulaire;
            $synthesesModulaire->setModule($this);
        }

        return $this;
    }

    public function removeSynthesesModulaire(SyntheseModulaire $synthesesModulaire): self
    {
        if ($this->synthesesModulaires->contains($synthesesModulaire)) {
            $this->synthesesModulaires->removeElement($synthesesModulaire);
            // set the owning side to null (unless already changed)
            if ($synthesesModulaire->getModule() === $this) {
                $synthesesModulaire->setModule(null);
            }
        }

        return $this;
    }
}
