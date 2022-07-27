<?php

namespace App\Form;

use App\Entity\Supplier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SupplierType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class,
                $this->options("form.supplier.name.title", 'form.supplier.name.placeholder'))
            ->add('firstPhoneNumber',IntegerType::class,
                $this->options("form.supplier.firstPhoneNumber.title",
                    'form.supplier.firstPhoneNumber.placeholder',[
                        'required' => false
                    ]))
            ->add('secondPhoneNumber', IntegerType::class,
                $this->options("form.supplier.secondPhoneNumber.title",
                    'form.supplier.secondPhoneNumber.placeholder',[
                        'required' => false
                    ]))
            ->add('email', EmailType::class,
                $this->options("form.supplier.email.title",
                    "form.supplier.email.placeholder",[
                'required' => false
            ]))
            ->add('type',
                ChoiceType::class,
                $this->options('form.supplier.type.title', 'form.supplier.type.placeholder',
                    ['choices' => [
                        'form.supplier.type.choices.corporation' => 'Corporation',
                        'form.supplier.type.choices.physical_person' => 'Physical person'
                    ]]))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Supplier::class,
        ]);
    }
}
