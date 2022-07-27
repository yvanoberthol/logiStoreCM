<?php

namespace App\Form;

use App\Entity\PasswordUpdate;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PasswordUpdateType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('oldpassword',
                PasswordType::class,
                $this->options('form.passwordUpdate.oldpassword.title', 'form.passwordUpdate.oldpassword.placeholder'))
            ->add('newpassword',
                PasswordType::class,
                $this->options('form.passwordUpdate.newpassword.title', 'form.passwordUpdate.newpassword.placeholder'))
            ->add('confirmpassword',
                PasswordType::class,
                $this->options('form.passwordUpdate.confirmpassword.title', 'form.passwordUpdate.confirmpassword.placeholder'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'data_class' => PasswordUpdate::class
        ]);
    }
}
