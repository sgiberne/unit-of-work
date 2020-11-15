<?php

namespace Sgiberne\UnitOfWork\Storage;

final class ObjectStorage extends \SplObjectStorage implements ObjectStorageInterface
{
    public function clear(): void
    {
        $tempStorage = clone $this;
        $this->addAll($tempStorage);
        $this->removeAll($tempStorage);
        $tempStorage = null;
    }

}
