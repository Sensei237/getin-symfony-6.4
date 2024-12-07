<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: "App\Repository\UserRepository")]
#[ORM\Table(name: "getin_user")]
#[UniqueEntity("username")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\OneToOne(targetEntity: "App\Entity\Employe", inversedBy: "user", cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(nullable: false)]
    private $employe;

    #[ORM\Column(type: "string", length: 20, unique: true)]
    private $username;

    #[ORM\Column(type: "string", length: 255)]
    private $password;

    #[ORM\Column(type: "json")]
    private $roles = [];

    public array $droits = [];

    #[ORM\Column(type: "boolean")]
    private $isValid;

    #[ORM\OneToMany(targetEntity: "App\Entity\MatiereASaisir", mappedBy: "user")]
    private $matiereASaisirs;

    #[ORM\Column(type: "boolean")]
    private $isOnline;

    #[ORM\Column(type: "integer", nullable: true)]
    private $lastActivity;

    public function __construct()
    {
        $this->matiereASaisirs = new ArrayCollection();
        $this->isValid = true;
        $this->isOnline = false;
        $this->lastActivity = time();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): self
    {
        $this->employe = $employe;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        $role = explode('-', $this->roles);
        $role[] = 'ROLE_USER';
        $roles = array_unique($role);

        $this->droits = $roles;

        return $roles;

        // return $this->roles;
    }

    public function setRoles(string $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function setRole(string $role): self
    {
        $roles = $this->getRoles();
        $roles[] = $role;
        $this->setRoles(implode('-', array_unique($roles)));
        
        return $this;
    }

    public function hasRole(string $role)
    {
        return in_array($role, $this->getRoles());
    }

    public function getIsValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): self
    {
        $this->isValid = $isValid;

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
            $matiereASaisir->setUser($this);
        }

        return $this;
    }

    public function removeMatiereASaisir(MatiereASaisir $matiereASaisir): self
    {
        if ($this->matiereASaisirs->contains($matiereASaisir)) {
            $this->matiereASaisirs->removeElement($matiereASaisir);
            // set the owning side to null (unless already changed)
            if ($matiereASaisir->getUser() === $this) {
                $matiereASaisir->setUser(null);
            }
        }

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function eraseCredentials(): void
    {}

    public function getNom(): ?string
    {
        if (!$this->employe) {
            return null;
        }
        return $this->employe->getNomComplet();
    }

    public function getIsOnline(): ?bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): self
    {
        $this->isOnline = $isOnline;

        return $this;
    }

    public function getLastActivity(): ?int
    {
        return $this->lastActivity;
    }

    public function setLastActivity(?int $lastActivity): self
    {
        $this->lastActivity = $lastActivity;

        return $this;
    }

}
