<?php

namespace App\Form;

use App\Entity\Bank;
use App\Entity\Transaction;
use App\Form\DataTransformer\LocaleToDateTimeTransformer;
use App\Repository\BankRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionType extends ApplicationType
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
            ->add('bank', EntityType::class,[
                'class' => Bank::class,
                'query_builder' => function(BankRepository $bankRepository){
                    return $bankRepository->qbFindActive(true);
                },
                'label' => 'form.transaction.bank.title',
                'choice_label' => 'accountName',
                'required' => true
            ])
            ->add('type',
                ChoiceType::class,
                $this->options('form.transaction.type.title', 'form.transaction.type.placeholder',
                    ['choices' => [
                        'debit' => '0',
                        'credit' => '1'
                    ]]))
            ->add('date',TextType::class,
                $this->options("form.transaction.date.title",
                    $this->session->get('setting')->getDateMediumPicker(),[
                    'attr' => ['class' => 'datepicker','value' => date($this->session->get('setting')->getDateMedium())]
                ]))
            ->add('numCustomer',
                NumberType::class,
                $this->options("form.transaction.numCustomer.title",
                    'form.transaction.numCustomer.placeholder',[
                        'required' => true
                    ]))
            ->add('transactionCode',
                TextType::class,
                $this->options("form.transaction.transactionCode.title",
                    'form.transaction.transactionCode.placeholder',[
                        'required' => false
                    ]))
            ->add('amount',
                NumberType::class,
                $this->options("form.transaction.amount.title",
                    'form.transaction.amount.placeholder'))
            ->add('description',
                TextareaType::class,
                $this->options("form.transaction.description.title",
                    'form.transaction.description.placeholder',[
                        'required' => false
                    ]))
        ;

        $builder->get('date')->addModelTransformer(new LocaleToDateTimeTransformer($this->requestStack));
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
    }
}
