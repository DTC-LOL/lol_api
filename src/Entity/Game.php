<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\DatedTrait;
use App\Entity\DatedInterface;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game implements DatedInterface
{
    use DatedTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

    #[ORM\Column]
    private array $timeline = [];

    #[ORM\ManyToMany(targetEntity: Player::class, inversedBy: 'games')]
    private Collection $players;

    #[ORM\Column]
    private array $recap = [];

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getTimeline(): array
    {
        return $this->timeline;
    }

    public function setTimeline(array $timeline): self
    {
        $this->timeline = $timeline;

        return $this;
    }

    /**
     * @return Collection<int, Player>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(Player $player): self
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
        }

        return $this;
    }

    public function removePlayer(Player $player): self
    {
        $this->players->removeElement($player);

        return $this;
    }

    public function getRecap(): array
    {
        return $this->recap;
    }

    public function setRecap(array $recap): self
    {
        $this->recap = $recap;

        return $this;
    }
}
