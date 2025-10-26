<?php

namespace App\Entity;

use App\Repository\ReferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReferenceRepository::class)]
class Reference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $region = null;

    #[ORM\Column(length: 255)]
    private ?string $vignoble = null;

    #[ORM\Column]
    private array $type = [];

    #[ORM\Column]
    private array $type2 = [];

    #[ORM\Column]
    private array $cepages = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $accords = null;

    #[ORM\Column(length: 255)]
    private ?string $type3 = null;

    #[ORM\Column(length: 255)]
    private ?string $country = null;

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

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getVignoble(): ?string
    {
        return $this->vignoble;
    }

    public function setVignoble(string $vignoble): static
    {
        $this->vignoble = $vignoble;

        return $this;
    }

    public function getType(): array
    {
        return $this->type;
    }

    public function setType(array $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType2(): array
    {
        return $this->type2;
    }

    public function setType2(array $type2): static
    {
        $this->type2 = $type2;

        return $this;
    }

    public function getCepages(): array
    {
        return $this->cepages;
    }

    public function setCepages(array $cepages): static
    {
        $this->cepages = $cepages;

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
