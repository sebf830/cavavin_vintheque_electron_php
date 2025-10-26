<?php

namespace App\Entity;

use App\Repository\ParametersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParametersRepository::class)]
class Parameters
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasStockNotification = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasInspectorNotification = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function hasStockNotification(): ?bool
    {
        return $this->hasStockNotification;
    }

    public function setHasStockNotification(?bool $hasStockNotification): static
    {
        $this->hasStockNotification = $hasStockNotification;

        return $this;
    }

    public function hasInspectorNotification(): ?bool
    {
        return $this->hasInspectorNotification;
    }

    public function setHasInspectorNotification(?bool $hasInspectorNotification): static
    {
        $this->hasInspectorNotification = $hasInspectorNotification;

        return $this;
    }
}
