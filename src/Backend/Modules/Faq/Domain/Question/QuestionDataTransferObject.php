<?php

namespace Backend\Modules\Faq\Domain\Question;

use Backend\Core\Language\Language;
use Backend\Core\Language\Locale;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class QuestionDataTransferObject
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var int
     */
    public $revisionId;

    /**
     * @var Status
     */
    public $status;

    /**
     * @var int
     */
    public $sequence;

    /**
     * @var bool
     */
    public $visibleOnPhone;

    /**
     * @var bool
     */
    public $visibleOnTablet;

    /**
     * @var bool
     */
    public $visibleOnDesktop;

    /**
     * @var Collection|QuestionTranslationDataTransferObject[]
     */
    public $translations;

    public function __construct(Question $question = null)
    {
        $this->translations = $this->createTranslations();

        if ($question === null) {
            $this->revisionId = 1;
            $this->status = Status::active();
            $this->visibleOnPhone = true;
            $this->visibleOnTablet = true;
            $this->visibleOnDesktop = true;

            return;
        }

        $this->setId($question->getId());
        $this->revisionId = $question->getRevisionId();
        $this->status = $question->getStatus();
        $this->id = $question->getId();
        $this->sequence = $question->getSequence();
        $this->visibleOnPhone = $question->getVisibleOnPhone();
        $this->visibleOnTablet = $question->getVisibleOnTablet();
        $this->visibleOnDesktop = $question->isVisibleOnDesktop();
        foreach ($question->getTranslations() as $questionTranslation) {
            $this->translations->set(
                $questionTranslation->getLocale(),
                new QuestionTranslationDataTransferObject($questionTranslation)
            );
        }
    }

    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new IdAlreadySetException();
        }

        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * This method makes sure that even when new languages are added we will always show them in the form
     *
     * @return ArrayCollection
     */
    private function createTranslations(): ArrayCollection
    {
        $translations = new ArrayCollection();
        foreach (Language::getActiveLanguages() as $locale) {
            $translations->set(
                $locale,
                new QuestionTranslationDataTransferObject(
                    null,
                    Locale::fromString($locale)
                )
            );
        }

        return $translations;
    }
}
