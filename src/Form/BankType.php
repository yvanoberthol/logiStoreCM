<?php

namespace App\Form;

use App\Entity\Bank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BankType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('accountName',
                TextType::class,
                $this->options("form.bank.accountName.title",
                    'form.bank.accountName.placeholder'))
            ->add('initialBalance',
                NumberType::class,
                $this->options("form.bank.initialBalance.title",
                    'form.bank.initialBalance.placeholder'))
            ->add('phoneNumber',
                TextType::class,
                $this->options("form.bank.phoneNumber.title",
                    'form.bank.phoneNumber.placeholder',[
                        'required' => false
                    ]))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Bank::class,
        ]);
    }
}
