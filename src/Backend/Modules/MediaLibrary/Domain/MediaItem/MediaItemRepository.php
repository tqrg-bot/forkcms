<?php

namespace Backend\Modules\MediaLibrary\Domain\MediaItem;

use Doctrine\ORM\EntityRepository;
use Backend\Modules\MediaLibrary\Domain\MediaItem\Exception\MediaItemNotFound;

final class MediaItemRepository extends EntityRepository
{
    public function add(MediaItem $mediaItem): void
    {
        // We don't flush here, see http://disq.us/p/okjc6b
        $this->getEntityManager()->persist($mediaItem);
    }

    public function existsOneByUrl(string $url): bool
    {
        /** @var MediaItem|null $mediaItem */
        $mediaItem = $this->findOneByUrl($url);

        return $mediaItem !== null;
    }

    public function findOneById(string $id = null): MediaItem
    {
        if ($id === null) {
            throw MediaItemNotFound::forEmptyId();
        }

        $mediaItem = parent::findOneById($id);

        if ($mediaItem === null) {
            throw MediaItemNotFound::forId($id);
        }

        return $mediaItem;
    }

    public function remove(MediaItem $mediaItem): void
    {
        // We don't flush here, see http://disq.us/p/okjc6b
        $this->getEntityManager()->remove($mediaItem);
    }
}
