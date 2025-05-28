<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    private ?Product $product = null;

    #[ORM\Column]
    private ?int $stars = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Profile $author = null;

    /**
     * @var Collection<int, Relevance>
     */
    #[ORM\OneToMany(targetEntity: Relevance::class, mappedBy: 'review')]
    private Collection $relevance;

    public function __construct()
    {
        $this->relevance = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return Collection<int, Relevance>
     */
    public function getRelevance(): Collection
    {
        return $this->relevance;
    }


    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getAuthor(): ?Profile
    {
        return $this->author;
    }

    public function setAuthor(?Profile $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getStars(): ?int
    {
        return $this->stars;
    }

    public function setStars(int $stars): static
    {
        $this->stars = $stars;

        return $this;
    }

    public function addRelevance(Relevance $relevance): static
    {
        if (!$this->relevance->contains($relevance)) {
            $this->relevance->add($relevance);
            $relevance->setReview($this);
        }

        return $this;
    }

    public function removeRelevance(Relevance $relevance): static
    {
        if ($this->relevance->removeElement($relevance)) {
            // set the owning side to null (unless already changed)
            if ($relevance->getReview() === $this) {
                $relevance->setReview(null);
            }
        }

        return $this;
    }

    public function getAverageRelevance(): float
    {
        $relevances = $this->getRelevance();
        if ($relevances->isEmpty()) {
            return false;
        }

        $total = 0;
        foreach ($relevances as $relevance) {
            $total += $relevance->getRate();
        }

        return $total / count($relevances);
    }

}
