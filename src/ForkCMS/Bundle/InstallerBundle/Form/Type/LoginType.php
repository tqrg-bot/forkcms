<?php

namespace ForkCMS\Bundle\InstallerBundle\Form\Type;

use ForkCMS\Bundle\InstallerBundle\Entity\InstallationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Builds the form to set up login information
 */
class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'email',
                'email'
            )
            ->add(
                'password',
                'repeated',
                [
                    'type' => 'password',
                    'invalid_message' => 'The passwords do not match.',
                    'required' => true,
                    'first_options' => ['label' => 'Password'],
                    'second_options' => ['label' => 'Confirm'],
                ]
            )
        ;

        // make sure the default data is set
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();

                $email = $data->getEmail();
                if (empty($email) && isset($_SERVER['HTTP_HOST'])) {
                    $data->setEmail('info@' . $_SERVER['HTTP_HOST']);
                    $event->setData($data);
                }
            }
        );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InstallationData::class,
            'validation_groups' => 'login',
        ]);
    }

    public function getName(): string
    {
        return 'install_login';
    }
}
