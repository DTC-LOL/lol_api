<?php

namespace App\Entity;

interface CreatedAtInterface
{
    public function getCreatedAt(): ?\DateTime;

    public function setCreatedAt(?\DateTime $createdAt): void;
}
