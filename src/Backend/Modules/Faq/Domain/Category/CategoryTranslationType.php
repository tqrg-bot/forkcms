<?php

namespace Backend\Modules\Faq\Domain\Category;

use Backend\Form\EventListener\AddMetaSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Backend\Form\Type\EditorType;

final class CategoryTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => 'lbl.Category',
            ]
        );
        $builder->addEventSubscriber(
            new AddMetaSubscriber(
                'Faq',
                'Category',
                CategoryRepository::class,
                'getUrl',
                [
                    'getData.getLocale',
                    'getData.getId',
                ],
                'name'
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CategoryTranslationDataTransferObject::class]);
    }
}
