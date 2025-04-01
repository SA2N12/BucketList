<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Wish::class)]
    private Collection $wishes;
    public function __construct()
    {
        $this->wishes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getWishes(): Collection
    {
        return $this->wishes;
    }

    public function addWish(Wish $wish): static
    {
        if (!$this->wishes->contains($wish)) {
            $this->wishes->add($wish);
            $wish->setCategory($this);
        }
        return $this;
    }

    public function removeWish(Wish $wish): static
    {
        if ($this->wishes->removeElement($wish)) {
            // Si le Wish pointait toujours vers cette Catégorie, on le détache
            if ($wish->getCategory() === $this) {
                $wish->setCategory(null);
            }
        }
        return $this;
    }
}
