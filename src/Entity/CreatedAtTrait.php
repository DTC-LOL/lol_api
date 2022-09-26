<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait CreatedAtTrait
{
    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    #[ORM\Column(type: 'datetime')]
    protected ?\DateTime $createdAt = null;

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
