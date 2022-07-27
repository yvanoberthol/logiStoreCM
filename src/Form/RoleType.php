<?php

namespace App\Form;

use App\Entity\Role;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class,
                $this->options("form.role.name.title",
                    'form.role.name.placeholder'))
            ->add('rank',
                ChoiceType::class,
                $this->options('form.role.rank.title', '',
                    ['choices' => [
                        '1' => 1,
                        '2' => 2,
                    ]]))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
        ]);
    }
}
