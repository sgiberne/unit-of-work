<?php

namespace Sgiberne\UnitOfWork\DataMapper;

use Sgiberne\UnitOfWork\Collection\EntityCollection;
use Sgiberne\UnitOfWork\Entity\EntityInterface;

interface DataMapperInterface
{
    public function fetchById(string $id);

    public function fetchAll(array $bind = [], array $where = [], array $options = [], array $orderBy = []): ?EntityCollection;

    public function select(array $bind = [], array $where = [], array $options = [], array $orderBy = []): ?EntityCollection;

    public function insert(EntityInterface $entity): int;

    public function update(EntityInterface $entity): int;

    public function delete(EntityInterface $entity);
}
