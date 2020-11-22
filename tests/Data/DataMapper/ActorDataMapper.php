<?php

namespace Sgiberne\UnitOfWork\Tests\Data\DataMapper;

use Sgiberne\UnitOfWork\DataMapper\AbstractDataMapper;
use Sgiberne\UnitOfWork\Tests\Data\Entity\Actor;

final class ActorDataMapper extends AbstractDataMapper
{
    protected function loadEntity(array $row): Actor
    {
        return new Actor(
            $row["id"] ?? null,
            $row["firstname"],
            $row["lastname"],
            $row["url_avatar"],
        );
    }

    public function getEntityTable(): string
    {
        return 'actor';
    }

    public function supports(): bool
    {
        return true;
    }
}
