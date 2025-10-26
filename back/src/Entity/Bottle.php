<?php

namespace App\Entity;

use App\Repository\BottleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BottleRepository::class)]
class Bottle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $year = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $domaine = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $vignoble = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(nullable: true)]
    private ?int $note = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type2 = null;

    #[ORM\Column(nullable: true)]
    private ?array $cepage_mandatory = null;

    #[ORM\Column(nullable: true)]
    private ?array $cepage_optional = null;

    #[ORM\Column]
    private ?\DateTime $creation_date = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'bottle')]
    private Collection $notes;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $accords = null;

    #[ORM\Column(length: 255)]
    private ?string $type3 = null;

    #[ORM\Column(length: 255)]
    private ?string $country = null;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(?string $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getDomaine(): ?string
    {
        return $this->domaine;
    }

    public function setDomaine(?string $domaine): static
    {
        $this->domaine = $domaine;

        return $this;
    }


    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getVignoble(): ?string
    {
        return $this->vignoble;
    }

    public function setVignoble(?string $vignoble): static
    {
        $this->vignoble = $vignoble;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(?int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType2(): ?string
    {
        return $this->type2;
    }

    public function setType2(?string $type2): static
    {
        $this->type2 = $type2;

        return $this;
    }

    public function getCepageMandatory(): ?array
    {
        return $this->cepage_mandatory;
    }

    public function setCepageMandatory(?array $cepage_mandatory): static
    {
        $this->cepage_mandatory = $cepage_mandatory;

        return $this;
    }

    public function getCepageOptional(): ?array
    {
        return $this->cepage_optional;
    }

    public function setCepageOptional(?array $cepage_optional): static
    {
        $this->cepage_optional = $cepage_optional;

        return $this;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->creation_date;
    }

    public function setCreationDate(\DateTime $creation_date): static
    {
        $this->creation_date = $creation_date;

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setBottle($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getBottle() === $this) {
                $note->setBottle(null);
            }
        }

        return $this;
    }

    public function getAccords(): ?string
    {
        return $this->accords;
    }

    public function setAccords(?string $accords): static
    {
        $this->accords = $accords;

        return $this;
    }

    public function getType3(): ?string
    {
        return $this->type3;
    }

    public function setType3(string $type3): static
    {
        $this->type3 = $type3;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }
}
