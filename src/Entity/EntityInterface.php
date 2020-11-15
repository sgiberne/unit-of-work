<?php

namespace Sgiberne\UnitOfWork\Entity;

interface EntityInterface
{
    public function getAllowedFields(): array;
    public function getFields(): array;
}
