<?php

namespace Backend\Modules\Faq\Domain\Question;

use Backend\Form\EventListener\AddMetaSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

final class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'visibleOnPhone',
            CheckboxType::class,
            [
                'label' => 'lbl.VisibleOnPhone',
            ]
        );
        $builder->add(
            'visibleOnTablet',
            CheckboxType::class,
            [
                'label' => 'lbl.VisibleOnTablet',
            ]
        );
        $builder->add(
            'visibleOnDesktop',
            CheckboxType::class,
            [
                'label' => 'lbl.VisibleOnDesktop',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => QuestionDataTransferObject::class]);
    }
}
