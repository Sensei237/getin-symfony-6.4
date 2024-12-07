<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\ExamenRepository")]
#[UniqueEntity(fields: ["code"])]
#[UniqueEntity(fields: ["intitule"])]
#[UniqueEntity(fields: ["slug"])]
class Examen
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
    private $intitule;

    #[ORM\Column(type: "string", length: 5, unique: true)]
    #[Assert\Length(
        min: 2,
        max: 10,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $code;

    #[ORM\Column(type: "string", length: 150, unique: true)]
    private $slug;

    #[ORM\Column(type: "string", length: 5)]
    private $type;

    #[ORM\Column(type: "integer", nullable: true)]
    private $pourcentage;

    #[ORM\OneToMany(targetEntity: "App\Entity\MatiereASaisir", mappedBy: "examen")]
    private $matiereASaisirs;

    #[ORM\Column(type: "integer", nullable: true)]
    private $pourcentageCC;

    #[ORM\OneToMany(targetEntity: "App\Entity\Anonymat", mappedBy: "examen", orphanRemoval: true)]
    private $anonymats;

    public function __construct()
    {
        $this->matiereASaisirs = new ArrayCollection();
        $this->anonymats = new ArrayCollection();
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
        $this->slug = mb_strtolower(trim(\str_replace(' ', '-', str_replace('é', 'e', str_replace('è', 'e', str_replace('à', 'a', str_replace('ê', 'e', mb_strtolower($slug, 'UTF8'))))))));

        return $this;
    }

    public function getType(): ?string
    {
        return mb_strtoupper($this->type);
    }

    public function setType(string $type): self
    {
        $this->type = mb_strtoupper($type);

        return $this;
    }

    public function getPourcentage(): ?int
    {
        return $this->pourcentage;
    }

    public function setPourcentage(?int $pourcentage): self
    {
        $this->pourcentage = $pourcentage;

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
            $matiereASaisir->setExamen($this);
        }

        return $this;
    }

    public function removeMatiereASaisir(MatiereASaisir $matiereASaisir): self
    {
        if ($this->matiereASaisirs->contains($matiereASaisir)) {
            $this->matiereASaisirs->removeElement($matiereASaisir);
            // set the owning side to null (unless already changed)
            if ($matiereASaisir->getExamen() === $this) {
                $matiereASaisir->setExamen(null);
            }
        }

        return $this;
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

    /**
     * @return Collection|Anonymat[]
     */
    public function getAnonymats(): Collection
    {
        return $this->anonymats;
    }

    public function addAnonymat(Anonymat $anonymat): self
    {
        if (!$this->anonymats->contains($anonymat)) {
            $this->anonymats[] = $anonymat;
            $anonymat->setExamen($this);
        }

        return $this;
    }

    public function removeAnonymat(Anonymat $anonymat): self
    {
        if ($this->anonymats->contains($anonymat)) {
            $this->anonymats->removeElement($anonymat);
            // set the owning side to null (unless already changed)
            if ($anonymat->getExamen() === $this) {
                $anonymat->setExamen(null);
            }
        }

        return $this;
    }
}
