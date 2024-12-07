<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\SpecialiteRepository")]
#[UniqueEntity("name")]
#[UniqueEntity("code")]
#[UniqueEntity(
    fields: ["name", "code"],
    errorPath: "code",
    message: "Ce code est déjà utilisé"
)]
class Specialite
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

    #[ORM\Column(type: "string", length: 5)]
    private $letterMatricule;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Filiere", inversedBy: "specialites")]
    #[ORM\JoinColumn(nullable: false)]
    private $filiere;

    #[ORM\OneToMany(targetEntity: "App\Entity\Classe", mappedBy: "specialite", cascade: ["persist", "remove"])]
    private $classes;

    public function __construct()
    {
        $this->classes = new ArrayCollection();
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
        $this->name = mb_strtoupper($name, 'utf8');

        return $this;
    }

    public function getCode(): ?string
    {
        return mb_strtoupper($this->code);
    }

    public function setCode(string $code): self
    {
        $this->code = mb_strtoupper($code, 'utf8');

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

    public function getLetterMatricule(): ?string
    {
        return $this->letterMatricule;
    }

    public function setLetterMatricule(string $letterMatricule): self
    {
        $this->letterMatricule = mb_strtoupper($letterMatricule, 'utf8');

        return $this;
    }

    public function getFiliere(): ?Filiere
    {
        return $this->filiere;
    }

    public function setFiliere(?Filiere $filiere): self
    {
        $this->filiere = $filiere;

        return $this;
    }

    /**
     * @return Collection|Classe[]
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    public function addClass(Classe $class): self
    {
        if (!$this->classes->contains($class)) {
            $this->classes[] = $class;
            $class->setSpecialite($this);
        }

        return $this;
    }

    public function removeClass(Classe $class): self
    {
        if ($this->classes->contains($class)) {
            $this->classes->removeElement($class);
            // set the owning side to null (unless already changed)
            if ($class->getSpecialite() === $this) {
                $class->setSpecialite(null);
            }
        }

        return $this;
    }

}
