<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait UpdatedAtTrait
{
    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $updatedAt = null;

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
