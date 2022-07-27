<?php

namespace App\Form;

use App\Entity\Store;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class StoreType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name',
                TextType::class,
                $this->options('form.store.name.title', 'form.store.name.placeholder'))
            ->add('imageFile',
                VichImageType::class,
                $this->options('form.store.imageFile.title', 'form.store.imageFile.placeholder',
                    [
                        'required' => false,
                        'allow_delete' => false,
                        'download_uri' => false,
                        'image_uri' => false
                    ]))
            ->add('address', TextType::class,
                $this->options('form.store.address.title', '',[
                    'required' => false
                ]))
            ->add('slogan',
                TextType::class,
                $this->options('form.store.slogan.title','',[
                    'required' => false
                ]))
            ->add('year', IntegerType::class,
                $this->options('form.store.year.title', '',[
                    'required' => false
                ]))
            ->add('phoneNumber', TextType::class,
                $this->options('form.store.phoneNumber.title', 'form.store.phoneNumber.placeholder',[
                    'required' => false
                ]))
            ->add('email',EmailType::class,
                $this->options('form.store.email.title', 'form.store.email.placeholder',[
                    'required' => false
                ]))
            ->add('webSite',UrlType::class,$this->options('form.store.webSite.title', 'form.store.webSite.placeholder',[
                'required' => false
            ]))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Store::class,
        ]);
    }
}
