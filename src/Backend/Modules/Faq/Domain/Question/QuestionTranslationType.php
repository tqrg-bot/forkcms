<?php

namespace Backend\Modules\Faq\Domain\Question;

use Backend\Form\EventListener\AddMetaSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Backend\Form\Type\EditorType;

final class QuestionTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'question',
            TextType::class,
            [
                'label' => 'lbl.Question',
            ]
        );
        $builder->add(
            'answer',
            EditorType::class,
            [
                'label' => 'lbl.Answer',
            ]
        );
        $builder->addEventSubscriber(
            new AddMetaSubscriber(
                'Faq',
                'Question',
                QuestionRepository::class,
                'getUrl',
                [
                    'getData.getLocale',
                    'getData.getId',
                ],
                'question'
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => QuestionTranslationDataTransferObject::class]);
    }
}
