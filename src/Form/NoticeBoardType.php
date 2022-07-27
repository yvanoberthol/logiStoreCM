<?php

namespace App\Form;

use App\Entity\NoticeBoard;
use App\Form\DataTransformer\LocaleToDateTimeTransformer;
use App\Util\NoticeBoardStatusConstant;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoticeBoardType extends ApplicationType
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * NoticeBoardType constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        $builder
            ->add('title',TextType::class,
                $this->options("form.noticeBoard.title.title",
                    'form.noticeBoard.title.placeholder'))
            ->add('message',
                TextareaType::class,
                $this->options("form.noticeBoard.message.title",
                    'form.noticeBoard.message.placeholder',[
                        'required' => false
                    ]))
            ->add('start',TextType::class,
                $this->options("form.noticeBoard.start.title",
                    $this->session->get('setting')->getDateMediumPicker(),[
                        'attr' => ['class' => 'datepicker']
                    ]))
            ->add('end',TextType::class,
                $this->options("form.noticeBoard.end.title",
                    $this->session->get('setting')->getDateMediumPicker(),[
                        'attr' => ['class' => 'datepicker']
                    ]))
            ->add('statut',
                ChoiceType::class,
                $this->options('form.noticeBoard.status.title', 'form.user.status.placeholder',
                    ['choices' => NoticeBoardStatusConstant::STATUS]))
        ;

        $builder->get('start')->addModelTransformer(new LocaleToDateTimeTransformer($this->session));
        $builder->get('end')->addModelTransformer(new LocaleToDateTimeTransformer($this->session));
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NoticeBoard::class,
        ]);
    }
}
