<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Cette classe est une entite c'est-a-dire une table de la base de donnee et chaque entree de cette table
 * represente une salle de classe.
 * Liste des proprietes : 
 * - id : une propriete autoincrement donc seule la methode getId() est presente
 * - nom : represente le nom de salle de classe. Vous avez donc les methodes getNom() et setNom() pour cette propriete
 * - code : represente le code de la salle de classe c'est-a-dire le nom court. Vous avez les methodes getCode() et setCode() pour cette propriete
 * - slug : c'est une chaine de caractere qui permet de formater les urls de notre application. Ainsi dans l'url au lieu d'utilier les id quand on souhaite recuperer un objet donnee, on utilisera plutot le slug cela revient assi a dire que le slug doit etre unique. Les methodes getSlug() et setSlug() sont disponibles pour cette propriete. 
 * - specialite : chaque classe est liee a une specialite. c'est propriete est donc une instance de classe Specialite. Les methodes getSpecialite() et setSpecialiete() sont disponibles.
 * - formation : chaque classe est liee a une formation. Cette proprite est donc une instance de classe Formation. getFormation() et setFormation() sont disponibles. 
 * - niveau : chaque classe a une propriete niveau qui represente le niveau d'etude des eleves inscrits dans cette classe. getNiveau() et setNiveau() sont disponibles. 
 * --------------------------------------------------------------------------------
 * D'autres methodes sont disponibles dans cette classe tellesque : 
 * - getEtudiantInscris() : une methode qui permet de recuperer la liste des etudiants inscrits dans cette classe. Il est necessaire de faire un filtre en fonction des années.
 * - getModules() : une methode qui permet de recuperer la liste des modules pour cette classe. Un filtre par année est necessaire. 
 * ---------------------------------------------------------------------------------
 * Pour plus d'informations, consulter la classe en question dans src/entity/Classe.php 
 * 
 */
#[ORM\Entity(repositoryClass: "App\Repository\ClasseRepository")]
#[UniqueEntity(fields: ["nom"], errorPath: "nom", message: "Une classe ayant ce nom existe déjà")]
#[UniqueEntity(fields: ["code"], errorPath: "code", message: "Ce code est déjà utilisé par une autre classe")]
#[UniqueEntity(fields: ["nom", "code"], errorPath: "code", message: "Une classe ayant ce code existe déjà")]
class Classe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    #[Groups("public")]
    private $id;

    #[ORM\Column(type: "string", length: 150, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 120,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    #[Groups("public")]
    private $nom;

    #[ORM\Column(type: "string", length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 15,
        minMessage: "Il faut au moins {{ limit }} caractères",
        maxMessage: "Il faut au max {{ limit }} caractères"
    )]
    private $code;

    #[ORM\Column(type: "string", length: 150, unique: true)]
    #[Groups("public")]
    private $slug;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Specialite", inversedBy: "classes")]
    #[ORM\JoinColumn(nullable: false)]
    private $specialite;

    #[ORM\ManyToOne(targetEntity: "App\Entity\Formation", inversedBy: "classes")]
    private $formation;

    #[ORM\Column(type: "integer")]
    #[Assert\Type(
        type: "integer",
        message: "Doit être un entier positif superieur à 1"
    )]
    #[Assert\GreaterThanOrEqual(1)]
    private $niveau;

    #[ORM\OneToMany(targetEntity: "App\Entity\PaiementClasse", mappedBy: "classe")]
    private $paiementClasses;

    #[ORM\OneToMany(targetEntity: "App\Entity\EtudiantInscris", mappedBy: "classe")]
    private $etudiantInscris;

    #[ORM\OneToMany(targetEntity: "App\Entity\Module", mappedBy: "classe")]
    private $modules;

    private $niveauRomain;

    public function __construct()
    {
        $this->paiementClasses = new ArrayCollection();
        $this->etudiantInscris = new ArrayCollection();
        $this->modules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return mb_strtoupper($this->nom, 'utf8');
    }

    public function setNom(string $nom): self
    {
        $this->nom = mb_strtoupper($nom, 'utf8');

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
        $this->slug = mb_strtolower(trim(\str_replace(' ', '-', str_replace('é', 'e', str_replace('è', 'e', str_replace('à', 'a', str_replace('ê', 'e', mb_strtolower($slug, 'UTF8'))))))));

        return $this;
    }

    public function getSpecialite(): ?Specialite
    {
        return $this->specialite;
    }

    public function setSpecialite(?Specialite $specialite): self
    {
        $this->specialite = $specialite;

        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        $this->formation = $formation;

        return $this;
    }

    public function getNiveau(): ?int
    {
        return $this->niveau;
    }

    public function setNiveau(int $niveau): self
    {
        $this->niveau = $niveau;

        return $this;
    }

    /**
     * @return Collection|PaiementClasse[]
     */
    public function getPaiementClasses(): Collection
    {
        return $this->paiementClasses;
    }

    public function addPaiementClass(PaiementClasse $paiementClass): self
    {
        if (!$this->paiementClasses->contains($paiementClass)) {
            $this->paiementClasses[] = $paiementClass;
            $paiementClass->setClasse($this);
        }

        return $this;
    }

    public function removePaiementClass(PaiementClasse $paiementClass): self
    {
        if ($this->paiementClasses->contains($paiementClass)) {
            $this->paiementClasses->removeElement($paiementClass);
            // set the owning side to null (unless already changed)
            if ($paiementClass->getClasse() === $this) {
                $paiementClass->setClasse(null);
            }
        }

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
            $etudiantInscri->setClasse($this);
        }

        return $this;
    }

    public function removeEtudiantInscri(EtudiantInscris $etudiantInscri): self
    {
        if ($this->etudiantInscris->contains($etudiantInscri)) {
            $this->etudiantInscris->removeElement($etudiantInscri);
            // set the owning side to null (unless already changed)
            if ($etudiantInscri->getClasse() === $this) {
                $etudiantInscri->setClasse(null);
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
            $module->setClasse($this);
        }

        return $this;
    }

    public function removeModule(Module $module): self
    {
        if ($this->modules->contains($module)) {
            $this->modules->removeElement($module);
            // set the owning side to null (unless already changed)
            if ($module->getClasse() === $this) {
                $module->setClasse(null);
            }
        }

        return $this;
    }

    public function getNiveauRomain()
    {
        switch ($this->niveau) {
            case 1:
                $this->niveauRomain = 'I';
                break;
            case 2:
                $this->niveauRomain = 'II';
                break;
            case 3:
                $this->niveauRomain = 'III';
                break;
            case 4:
                $this->niveauRomain = 'IV';
                break;
            case 5:
                $this->niveauRomain = 'V';
                break;
            
            default:
                $this->niveauRomain = $this->niveau;
                break;
        }
        
        return $this->niveauRomain;
    }

    public function getLMDLevel(): ?string
    {
        switch ($this->niveau) {
            case 1:
                return 'L1';
                
            case 2:
                return 'L2';
                
            case 3:
                return 'L3';
                
            case 4:
                return 'M1';
                
            case 5:
                return 'M2';
                
            default:
                return null;
        }
    }

    public function getAsArray()
    {

        return [
            'filiere' => $this->getSpecialite()->getFiliere()->getName(),
            'niveau' => $this->getNiveauRomain(),
            'specialite' => $this->getSpecialite()->getName(),
            
        ];
    }
}
