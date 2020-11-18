<?php

namespace Sgiberne\UnitOfWork;

use Sgiberne\UnitOfWork\Collection\EntityCollection;
use Sgiberne\UnitOfWork\DataMapper\DataMapperInterface;
use Sgiberne\UnitOfWork\Entity\EntityInterface;
use Sgiberne\UnitOfWork\Storage\ObjectStorageInterface;

class UnitOfWork
{
    private const STATE_NEW = "NEW";
    private const STATE_CLEAN = "CLEAN";
    private const STATE_DIRTY = "DIRTY";
    private const STATE_REMOVED = "REMOVED";

    protected DataMapperInterface $dataMapper;
    protected ObjectStorageInterface $objectStorage;

    public function __construct(DataMapperInterface $dataMapper, ObjectStorageInterface $objectStorage)
    {
        $this->dataMapper = $dataMapper;
        $this->objectStorage = $objectStorage;
    }

    public function getDataMapper(): DataMapperInterface
    {
        return $this->dataMapper;
    }

    public function getObjectStorage(): ObjectStorageInterface
    {
        return $this->objectStorage;
    }

    public function fetchById($id): ?EntityInterface
    {
        $entity = $this->dataMapper->fetchById($id);

        if ($entity instanceof EntityInterface) {
            $this->registerClean($entity);

            return $entity;
        }

        return null;
    }

    public function registerNew(EntityInterface $entity): self
    {
        $this->registerEntity($entity, self::STATE_NEW);

        return $this;
    }

    public function registerClean(EntityInterface $entity): self
    {
        $this->registerEntity($entity, self::STATE_CLEAN);

        return $this;
    }

    public function registerDirty(EntityInterface $entity): self
    {
        $this->registerEntity($entity, self::STATE_DIRTY);

        return $this;
    }

    public function registerDeleted(EntityInterface $entity): self
    {
        $this->registerEntity($entity, self::STATE_REMOVED);

        return $this;
    }

    protected function registerEntity(EntityInterface $entity, string $state = self::STATE_CLEAN): void
    {
        $this->objectStorage->attach($entity, $state);
    }

    public function commit(): void
    {
        foreach ($this->objectStorage as $entity) {
            switch ($this->objectStorage[$entity]) {
                case self::STATE_NEW:
                    $this->dataMapper->insert($entity);
                    break;
                case self::STATE_DIRTY:
                    $this->dataMapper->update($entity);
                    break;
                case self::STATE_REMOVED:
                    $this->dataMapper->delete($entity);
            }
        }

        $this->clear();
    }

    public function startTransaction(): void
    {
        // @todo custom transaction implementation goes here
    }

    public function rollback(): void
    {
        // @todo custom rollback implementation goes here
    }

    public function clear(): self
    {
        $this->objectStorage->clear();

        return $this;
    }

    public function fetchAll(array $bind = [], array $where = [], array $options = [], array $orderBy = []): ?EntityCollection
    {
        $entities = $this->dataMapper->fetchAll($bind, $where, $options, $orderBy);

        if ($entities instanceof EntityCollection) {
            foreach ($entities as $entity) {
                $this->registerClean($entity);
            }

            return $entities;
        }

        return null;
    }

    public function select(array $bind = [], array $where = [], array $options = [], array $orderBy = []): ?EntityInterface
    {
        $entity = $this->dataMapper->select($bind, $where, $options, $orderBy);

        if ($entity instanceof EntityInterface) {
            $this->registerClean($entity);

            return $entity;
        }

        return null;
    }
}
