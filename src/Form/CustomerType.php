<?php

namespace App\Form;

use App\Entity\Customer;

use App\Entity\Setting;
use App\Util\CustomerTypeConstant;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomerType extends ApplicationType
{

    /**
     * @var Setting
     */
    private $setting;

    /**
     * ExpenseController constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->setting = $requestStack->getSession()->get('setting');
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',
                TextType::class,
                $this->options('form.customer.name.title',
                    'form.customer.name.placeholder'))
            ->add('email',
                EmailType::class,
                $this->options('form.customer.email.title',
                    'form.customer.email.placeholder',[
                        'required' => false
                    ]))
            ->add('phoneNumber',
                TextType::class,
                $this->options('form.customer.phoneNumber.title',
                    'form.customer.phoneNumber.placeholder',[
                    'required' => false
                ]))
            ->add('gender',
                ChoiceType::class,
                $this->options('form.customer.gender.title',
                    'form.customer.gender.placeholder',
                    ['choices' => [
                        'man' => 'Man',
                        'woman' => 'Woman'
                    ]]))
            ->add('address',
                TextType::class,
                $this->options('form.customer.address.title',
                    'form.customer.address.placeholder',[
                    'required' => false
                ]));
            if ($this->setting->getWithWholeSale()){
            $builder->add('type',
                ChoiceType::class,
                $this->options('form.customer.type.title', 'form.customer.type.placeholder',
                    ['choices' => CustomerTypeConstant::TYPEKEYS]));
            }
            $builder->add('other',
                TextareaType::class,
                $this->options("form.customer.other.title",
                    'form.customer.other.placeholder',[
                        'required' => false
                    ]))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}
