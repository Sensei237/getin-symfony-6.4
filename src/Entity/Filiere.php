<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\FiliereRepository")]
#[UniqueEntity(fields: ["code"])]
#[UniqueEntity(fields: ["name"])]
class Filiere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 150, unique: true)]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $name;

    #[ORM\Column(type: "string", length: 100, unique: true)]
    #[Assert\Length(
        min: 2,
        max: 20,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $code;

    #[ORM\Column(type: "string", length: 150, unique: true)]
    private $slug;

    #[ORM\OneToMany(targetEntity: "App\Entity\Specialite", mappedBy: "filiere")]
    private $specialites;

    #[ORM\Column(type: "string", length: 10)]
    private $lettrePourLeMatricule;

    public function __construct()
    {
        $this->specialites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return mb_strtoupper($this->name, 'utf8');
    }

    public function setName(string $name): self
    {
        $this->name = mb_strtoupper($name, 'UTF8');

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
        $this->slug = mb_strtolower(trim(\str_replace(' ', '-', str_replace('é', 'e', str_replace('è', 'e', str_replace('à', 'a', str_replace('ê', 'e', mb_strtolower($slug, 'UTF8'))))))), 'UTF8');
        
        return $this;
    }

    /**
     * @return Collection|Specialite[]
     */
    public function getSpecialites(): Collection
    {
        return $this->specialites;
    }

    public function addSpecialite(Specialite $specialite): self
    {
        if (!$this->specialites->contains($specialite)) {
            $this->specialites[] = $specialite;
            $specialite->setFiliere($this);
        }

        return $this;
    }

    public function removeSpecialite(Specialite $specialite): self
    {
        if ($this->specialites->contains($specialite)) {
            $this->specialites->removeElement($specialite);
            // set the owning side to null (unless already changed)
            if ($specialite->getFiliere() === $this) {
                $specialite->setFiliere(null);
            }
        }

        return $this;
    }

    public function getLettrePourLeMatricule(): ?string
    {
        return $this->lettrePourLeMatricule;
    }

    public function setLettrePourLeMatricule(string $lettrePourLeMatricule): self
    {
        $this->lettrePourLeMatricule = $lettrePourLeMatricule;

        return $this;
    }
}
