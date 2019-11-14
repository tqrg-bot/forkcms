<?php

namespace Backend\Modules\Faq\Domain\Category\Command;

final class ReSequenceCategories
{
    /**
     * @var int[]
     */
    private $ids;

    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    /**
     * @return int[]
     */
    public function getIds(): array
    {
        return $this->ids;
    }
}
