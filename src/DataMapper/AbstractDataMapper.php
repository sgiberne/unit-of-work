<?php

namespace Sgiberne\UnitOfWork\DataMapper;

use Sgiberne\UnitOfWork\Adapter\PDOAdapter;
use Sgiberne\UnitOfWork\Collection\EntityCollection;
use Sgiberne\UnitOfWork\Entity\EntityInterface;

abstract class AbstractDataMapper implements DataMapperInterface
{
    private PDOAdapter $adapter;
    private EntityCollection $collection;

    public function __construct(PDOAdapter $adapter, EntityCollection $collection)
    {
        $this->adapter = $adapter;
        $this->collection = $collection;
    }

    public function fetchById(string $id): ?EntityInterface
    {
        $this->adapter->select($this->getEntityTable(), ['id' => $id], ['id=:id']);

        if (!$row = $this->adapter->fetch()) {
            return null;
        }

        return $this->loadEntity($row);
    }

    public function fetchAll(array $bind = [], array $where = [], array $options = [], array $orderBy = []): ?EntityCollection
    {
        $this->adapter->select($this->getEntityTable(), $bind, $where, $options, $orderBy);
        $rows = $this->adapter->fetchAll();

        if (empty($rows)) {
            return null;
        }

        return $this->loadEntityCollection($rows);
    }

    public function select(array $bind = [], array $where = [], array $options = [], array $orderBy = []) : ?EntityCollection
    {
        $this->adapter->select($this->getEntityTable(), $bind, $where, $options, $orderBy);
        $rows = $this->adapter->fetchAll();

        if (empty($rows)) {
            return null;
        }

        return $this->loadEntityCollection($rows);
    }

    public function insert(EntityInterface $entity): int
    {
        foreach ($entity->getFields() as $field) {
            if ($field instanceof EntityInterface) {
                $this->adapter->insert($field->getEntityTable(), $field->getFields());
            }
        }

        return $this->adapter->insert($this->getEntityTable(), $entity->getFields());
    }

    public function update(EntityInterface $entity): int
    {
        return $this->adapter->update($this->getEntityTable(), $entity->getFields(), "{$this->getPrimaryKey()} = ". $entity->{$this->getPrimaryKey()});
    }

    public function delete(EntityInterface $entity)
    {
        return $this->adapter->delete($this->getEntityTable(), "id = $entity->id");
    }

    public function count(): int
    {
        return $this->adapter->count();
    }

    protected function loadEntityCollection(array $rows): EntityCollection
    {
        $this->collection->clear();
        foreach ($rows as $row) {
            $this->collection->add($this->loadEntity($row));
        }

        return $this->collection;
    }

    public function getPrimaryKey(): string
    {
        return 'id';
    }

    abstract protected function loadEntity(array $row);
    abstract public function getEntityTable(): string;
    abstract public function supports(): bool;
}
