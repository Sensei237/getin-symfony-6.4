<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: "App\Repository\ServiceRepository")]
#[UniqueEntity("code")]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 255)]
    private $nom;

    #[ORM\Column(type: "string", length: 255)]
    private $slug;

    #[ORM\Column(type: "string", length: 20, nullable: true, unique: true)]
    private $code;

    #[ORM\OneToMany(targetEntity: "App\Entity\Employe", mappedBy: "service")]
    private $employes;

    public function __construct()
    {
        $this->employes = new ArrayCollection();
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
        $this->nom = mb_strtoupper($nom, 'UTF8');

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

    public function getCode(): ?string
    {
        return mb_strtoupper($this->code);
    }

    public function setCode(?string $code): self
    {
        $this->code = mb_strtoupper($code);

        return $this;
    }

    /**
     * @return Collection|Employe[]
     */
    public function getEmployes(): Collection
    {
        return $this->employes;
    }

    public function addEmploye(Employe $employe): self
    {
        if (!$this->employes->contains($employe)) {
            $this->employes[] = $employe;
            $employe->setService($this);
        }

        return $this;
    }

    public function removeEmploye(Employe $employe): self
    {
        if ($this->employes->contains($employe)) {
            $this->employes->removeElement($employe);
            // set the owning side to null (unless already changed)
            if ($employe->getService() === $this) {
                $employe->setService(null);
            }
        }

        return $this;
    }
}
