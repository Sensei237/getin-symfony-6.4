<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Cette entite permet de gerer les departements des differentes regions. 
 * Les proprietes possibles sont : 
 * - id : autoincrement, une seule methode est disponible et c'est la methode getId()
 * - nom : represente le nom du departement. getNom() et setNom() sont disponibles. 
 * - slug : permet de formater les urls de notre application et permet d'identifier de facon unique un departement. Methodes : setDepartement() et getDepartement().  
 * - region : cette propriete fait reference a la classe Region. Methode : getRegion() et setRegion(). 
 * -------------------------------------------------------------------------------
 * - Vous pouvez aussi faire appel a la methode getEtudiants() qui permet de selectionner la liste des etudiants qui sont originaires de ce departement. 
 * 
 */
#[ORM\Entity(repositoryClass: "App\Repository\DepartementRepository")]
class Departement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 255)]
    private $nom;

    #[ORM\Column(type: "string", length: 255)]
    private $slug;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Region", inversedBy: "departements")]
    private $region;

    #[ORM\OneToMany(targetEntity: "App\Entity\Etudiant", mappedBy: "departement")]
    private $etudiants;

    public function __construct()
    {
        $this->etudiants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return mb_strtoupper($this->nom, 'UTF8');
    }

    public function setNom(string $nom): self
    {
        $this->nom = mb_strtoupper($nom, 'utf8');

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

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): self
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return Collection|Etudiant[]
     */
    public function getEtudiants(): Collection
    {
        return $this->etudiants;
    }

    public function addEtudiant(Etudiant $etudiant): self
    {
        if (!$this->etudiants->contains($etudiant)) {
            $this->etudiants[] = $etudiant;
            $etudiant->setDepartement($this);
        }

        return $this;
    }

    public function removeEtudiant(Etudiant $etudiant): self
    {
        if ($this->etudiants->contains($etudiant)) {
            $this->etudiants->removeElement($etudiant);
            // set the owning side to null (unless already changed)
            if ($etudiant->getDepartement() === $this) {
                $etudiant->setDepartement(null);
            }
        }

        return $this;
    }
}
