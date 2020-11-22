<?php

namespace Sgiberne\UnitOfWork\Tests\Data\Entity;

use Sgiberne\UnitOfWork\Entity\AbstractEntity;

final class Actor extends AbstractEntity
{
    public string $firstname;
    public string $lastname;
    public string $urlAvatar;

    public function __construct(?string $id, string $firstname, string $lastname, string $urlAvatar)
    {
        $this->id = $id;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->urlAvatar = $urlAvatar;
    }

    public function getFields(): array
    {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'url_avatar' => $this->urlAvatar,
        ];
    }
}
