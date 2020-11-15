<?php

namespace Sgiberne\UnitOfWork\Collection;

use Sgiberne\UnitOfWork\Entity\EntityInterface;

final class EntityCollection implements \Countable
{
    protected array $entities = [];

    public function __construct(array $entities = [])
    {
        if (!empty($entities)) {
            $this->entities = $entities;
        }
    }

    public function add(EntityInterface $entity): void
    {
        $this->offsetSet($entity);
    }

    public function remove(EntityInterface $entity): void
    {
        $this->offsetUnset($entity);
    }

    public function get(int $key): ?EntityInterface
    {
        $this->offsetGet($key);
    }

    public function exists(int $key): bool
    {
        return $this->offsetExists($key);
    }

    public function clear(): void
    {
        $this->entities = [];
    }

    public function toArray(): array
    {
        return $this->entities;
    }

    public function count(): int
    {
        return count($this->entities);
    }

    public function offsetSet(EntityInterface $entity, string $key = null): void
    {
        if (!isset($key)) {
            $this->entities[] = $entity;
        } else {
            $this->entities[$key] = $entity;
        }
    }

    public function offsetUnset(EntityInterface $key): void
    {
        if ($key instanceof EntityInterface) {
            $this->entities = array_filter(
                $this->entities,
                static function ($entity) use ($key) {
                return $entity !== $key;
            });
        }

        if (isset($this->entities[$key])) {
            unset($this->entities[$key]);
        }
    }

    public function offsetGet(int $key) : ?EntityInterface
    {
        return $this->entities[$key] ?? null;
    }

    /**
     * @param int|EntityInterface $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $key instanceof EntityInterface ? array_search($key, $this->entities, true) : isset($this->entities[$key]);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->entities);
    }
}
