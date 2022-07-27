<?php

namespace App\Form;

use App\Entity\Expense;
use App\Entity\PaymentMethod;
use App\Form\DataTransformer\LocaleToDateTimeTransformer;
use App\Repository\ExpenseTypeRepository;
use App\Repository\PaymentMethodRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExpenseType extends ApplicationType
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * ExpenseType constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->session = $this->requestStack->getSession();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        $builder
            ->add('name',TextType::class,
                $this->options("form.expense.name.title",
                    'form.expense.name.placeholder'))
            ->add('date',TextType::class,
                $this->options("form.expense.addDate.title",
                    $this->session->get('setting')->getDateMediumPicker(),[
                        'attr' => ['class' => 'datepicker']
                    ]))
            ->add('type', EntityType::class,[
                'class' => 'App\\Entity\\ExpenseType',
                'query_builder' => function(ExpenseTypeRepository $expenseTypeRepository){
                    return $expenseTypeRepository->qbFindActive(true);
                },
                'label' => 'form.expense.expenseType.title',
                'choice_label' => 'name',
                'required' => true
            ])
            ->add('paymentMethod', EntityType::class,[
                'class' => PaymentMethod::class,
                'query_builder' => function(PaymentMethodRepository $paymentRepository){
                    return $paymentRepository->qbFindActive(true);
                },
                'label' => 'form.expense.paymentMethod.title',
                'choice_label' => 'name',
                'required' => true
            ])
            ->add('amount',
                NumberType::class,
                $this->options("form.expense.amount.title",
                    'form.expense.amount.placeholder'))
            ->add('description',
                TextareaType::class,
                $this->options("form.expense.description.title",
                    'form.expense.description.placeholder',[
                        'required' => false
                    ]));

        $builder->get('date')->addModelTransformer(new LocaleToDateTimeTransformer($this->requestStack));
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Expense::class,
        ]);
    }
}
