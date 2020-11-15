<?php

namespace Sgiberne\UnitOfWork\DataMapper;

use Sgiberne\UnitOfWork\Collection\EntityCollection;
use Sgiberne\UnitOfWork\Entity\EntityInterface;

interface DataMapperInterface
{
    public function fetchById(string $id);

    /** @todo I think $where should be an array */
    public function fetchAll(array $bind = [], string $where = "", array $options = [], array $orderBy = []): ?EntityCollection;

    /** @todo I think $where should be an array */
    public function select(array $bind = [], string $where = "", array $options = [], array $orderBy = []): ?EntityCollection;

    public function insert(EntityInterface $entity): int;

    public function update(EntityInterface $entity): int;

    public function delete(EntityInterface $entity);
}
