<?php

namespace App\Entity;

interface UpdatedAtInterface
{
    public function getUpdatedAt(): ?\DateTime;

    public function setUpdatedAt(\DateTime $updatedAt): void;
}
