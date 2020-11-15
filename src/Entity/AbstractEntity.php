<?php

namespace Sgiberne\UnitOfWork\Entity;

abstract class AbstractEntity implements EntityInterface
{
    public ?int $id = null;

    public function getAllowedFields(): array
    {
        return get_object_vars($this);
    }

    protected function checkAllowedFields(string $field): bool
    {
        if (!in_array($field, $this->getAllowedFields, true)) {
            throw new \InvalidArgumentException("The requested operation on the field '$field' is not allowed for this entity.");
        }

        return true;
    }
}
