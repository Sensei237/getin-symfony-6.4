<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Cette classe est une entite c'est-a-dire une table de la base de donnee et chaque entree de cette table
 * represente une annee academique. Les proprietes de cette entite sont 
 * - id : C'est une valeur auto increment donc il n'y a pas de methode setId()
 * - denomination : c'est une valeur de type chaine de caractere qui represente le nom de l'annee academique. Exemple : 2019 - 2020. Vous avez donc pour cette propriete une methode getDenomination() et une methode setDenomination()
 * - slug : c'est un champ de type chaine de caractere. Sa valeur est semblable a la denomination de l'annee academique. On l'utilisera pour formater nos urls. Vous avez donc pour cette propriete une methode getSlug() et une methode setSlug()
 * - isArchived : C'est un booleen qui nous permet de savoir si cette annee academique est deja passee. Vous avez donc pour cette propriete une methode getIsArchived() et une methode setIsArchived()
 * --------------------------------------------------------------------------------------------------------
 * Vous pouvez aussi recuperer les parametres (configuration) de l'ecole au courant de cette annee grace a la methode getConfiguration(), ainsi que la 
 * liste des etudiants inscris au courant de cette annee academique grace a la methode getConfiguration(). Vous pouvez aussi grace a la methode getModules() 
 */
#[ORM\Entity(repositoryClass: "App\Repository\AnneeAcademiqueRepository")]
#[UniqueEntity("denomination")]
class AnneeAcademique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 20, unique: true)]
    private $denomination;

    #[ORM\Column(type: "string", length: 100, unique: true)]
    private $slug;

    #[ORM\Column(type: "boolean")]
    private $isArchived;

    #[ORM\OneToMany(targetEntity: "App\Entity\EtudiantInscris", mappedBy: "anneeAcademique")]
    private $etudiantInscris;

    #[ORM\OneToMany(targetEntity: "App\Entity\Module", mappedBy: "anneeAcademique")]
    private $modules;

    #[ORM\OneToOne(targetEntity: "App\Entity\Configuration", mappedBy: "anneeAcademique", cascade: ["persist", "remove"])]
    private $configuration;

    private $denominationSlash;

    public function __construct()
    {
        $this->etudiantInscris = new ArrayCollection();
        $this->modules = new ArrayCollection();
        $this->isArchived = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDenomination(): ?string
    {
        return $this->denomination;
    }

    public function setDenomination(string $denomination): self
    {
        $this->denomination = $denomination;

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

    public function getIsArchived(): ?bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): self
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    /**
     * @return Collection|EtudiantInscris[]
     */
    public function getEtudiantInscris(): Collection
    {
        return $this->etudiantInscris;
    }

    public function addEtudiantInscri(EtudiantInscris $etudiantInscri): self
    {
        if (!$this->etudiantInscris->contains($etudiantInscri)) {
            $this->etudiantInscris[] = $etudiantInscri;
            $etudiantInscri->setAnneeAcademique($this);
        }

        return $this;
    }

    public function removeEtudiantInscri(EtudiantInscris $etudiantInscri): self
    {
        if ($this->etudiantInscris->contains($etudiantInscri)) {
            $this->etudiantInscris->removeElement($etudiantInscri);
            // set the owning side to null (unless already changed)
            if ($etudiantInscri->getAnneeAcademique() === $this) {
                $etudiantInscri->setAnneeAcademique(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Module[]
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(Module $module): self
    {
        if (!$this->modules->contains($module)) {
            $this->modules[] = $module;
            $module->setAnneeAcademique($this);
        }

        return $this;
    }

    public function removeModule(Module $module): self
    {
        if ($this->modules->contains($module)) {
            $this->modules->removeElement($module);
            // set the owning side to null (unless already changed)
            if ($module->getAnneeAcademique() === $this) {
                $module->setAnneeAcademique(null);
            }
        }

        return $this;
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(Configuration $configuration): self
    {
        $this->configuration = $configuration;

        // set the owning side of the relation if necessary
        if ($this !== $configuration->getAnneeAcademique()) {
            $configuration->setAnneeAcademique($this);
        }

        return $this;
    }

    public function getDenominationSlash()
    {
        return trim(str_replace(" ", "", str_replace("-", "/", $this->denomination)));
    }

    public function getAsArray()
    {
        return [
            'denomination' => $this->getDenominationSlash(),
            'isArchived' => $this->getIsArchived(),
            'configuration' => [
                'nomEcole' => $this->getConfiguration()->getNomEcole(),
                'nomEcoleEn' => $this->getConfiguration()->getNomEcoleEn(),
                'boitePostale' => $this->getConfiguration()->getBoitePostale(),
                'telephone' => $this->getConfiguration()->getTelephone(),
                'email' => $this->getConfiguration()->getEmail(),
                'logo' => $this->getConfiguration()->getLogo(),
                'ville' => $this->getConfiguration()->getVille(),
                'logoUniversity' => $this->getConfiguration()->getLogoUniversity(),
                'logoEcoleBase64' => null, //$this->getConfiguration()->getLogoEcoleBase64(),
                'logoUniversityBase64' => null, // $this->getConfiguration()->getLogoUniversityBase64(),
                'filigrane' => null //$this->getConfiguration()->getFiligrane(),
            ]
        ];
    }
}
