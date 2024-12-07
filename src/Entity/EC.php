<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un EC represente une matiere enseignée dans l'ecole. Ses proprietes sont : 
 * - id : cette propriete est en auto increment et permet d'identifier de facon unique un EC. Seule la methode getId() est disponible pour cette propriete. 
 * - intitule : represente le nom de l'EC. Methodes : getIntitule() et setIntitule(). 
 * - code : represente le nom court de l'EC. Methodes : getCode() et setCode(). 
 * - slug : une chaine de caractere qui permet de formater les urls de notre application. Cette valeur doit etre unique par EC. Methodes : getSlug() et setSlug(). 
 * - ------------------------------------------------------------------------------
 * - Une methode getEcModules() est aussi disponible vous permettant de recuperer tous les modules qui ont cet EC comme matiere. 
 * 
 */
#[ORM\Entity(repositoryClass: "App\Repository\ECRepository")]
#[UniqueEntity(fields: ["intitule"], errorPath: "intitule", message: "Un EC portant ce nom existe déjà")]
#[UniqueEntity(fields: ["code"], errorPath: "code", message: "Un EC portant ce code existe déjà")]
class EC
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $intitule;

    #[ORM\Column(type: "string", length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 15,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $code;

    #[ORM\Column(type: "string", length: 150, unique: true)]
    private $slug;

    #[ORM\OneToMany(targetEntity: "App\Entity\ECModule", mappedBy: "ec")]
    private $eCModules;

    public function __construct()
    {
        $this->eCModules = new ArrayCollection();
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
        return mb_strtoupper($this->code, 'utf8');
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
        $this->slug = mb_strtolower(trim(\str_replace([' ', '(', ')', '/', "\\", '.', '+', '*', '@', '!', '?', '>', '<', '\'', '"', '&', '%', '$', '#', ',', ';', ':'], '-', str_replace('é', 'e', str_replace('è', 'e', str_replace('à', 'a', str_replace('ê', 'e', mb_strtolower($slug, 'UTF8'))))))));

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
            $eCModule->setEc($this);
        }

        return $this;
    }

    public function removeECModule(ECModule $eCModule): self
    {
        if ($this->eCModules->contains($eCModule)) {
            $this->eCModules->removeElement($eCModule);
            // set the owning side to null (unless already changed)
            if ($eCModule->getEc() === $this) {
                $eCModule->setEc(null);
            }
        }

        return $this;
    }

    public function getAsArray()
    {

        return [
            'code' => $this->getCode(),
            'intitule' => $this->getIntitule(),
        ];
    }
}
