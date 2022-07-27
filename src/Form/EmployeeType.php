<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Form\Type\VichImageType;

class EmployeeType extends ApplicationType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',
                TextType::class,
                $this->options('form.user.name.title', 'form.user.name.placeholder'))
            ->add('email',EmailType::class,
                $this->options('form.user.email.title',
                    'form.user.email.placeholder',[
                        'required' => false
                ]))
            ->add('firstPhoneNumber', TextType::class,
                $this->options('form.user.firstPhoneNumber.title', 'form.user.firstPhoneNumber.placeholder',[
                    'required' => false
                ]))
            ->add('secondPhoneNumber', TextType::class,
                $this->options('form.user.secondPhoneNumber.title', 'form.user.secondPhoneNumber.placeholder',[
                    'required' => false
                ]))
            ->add('gender',
                ChoiceType::class,
                $this->options('form.user.gender.title', 'form.user.gender.placeholder',
                    ['choices' => [
                        'form.user.gender.choices.man' => 'Man',
                        'form.user.gender.choices.woman' => 'Woman'
                    ]]))
            ->add('district',
                TextType::class,
                $this->options('form.user.district.title', 'form.user.district.placeholder',[
                    'required' => false
                ]))
            ->add('imageFile',
                VichImageType::class,
                $this->options('form.user.imageFile.title', 'form.user.imageFile.placeholder',
                    [
                        'required' => false,
                        'allow_delete' => false,
                        'download_uri' => false,
                        'image_uri' => false
                    ]))
            ->add('salary', NumberType::class,
                $this->options('form.user.salary.title', '0',[
                    'required' => false
                ]))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
