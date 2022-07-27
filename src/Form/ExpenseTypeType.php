<?php

namespace App\Form;

use App\Entity\ExpenseType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExpenseTypeType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class,
                $this->options("form.expenseType.name.title",
                    'form.expenseType.name.placeholder'))
            ->add('description',TextareaType::class,
                $this->options("form.expenseType.description.title",
                    'form.expenseType.description.placeholder',
                    ['required'=>false])
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExpenseType::class,
        ]);
    }
}
