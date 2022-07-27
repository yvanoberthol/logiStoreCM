<?php

namespace App\Form;

use App\Entity\ProductCategory;
use App\Entity\Product;
use App\Entity\ProductPackaging;
use App\Entity\Setting;
use App\Extension\AppExtension;
use App\Repository\SettingRepository;
use App\Util\ModuleConstant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ProductType extends ApplicationType
{
    /**
     * @var AppExtension
     */
    private $appExtension;
    /**
     * @var Setting
     */
    private $setting;

    /**
     * ProductionController constructor.
     * @param AppExtension $appExtension
     * @param RequestStack $requestStack
     */
    public function __construct(AppExtension $appExtension, RequestStack $requestStack)
    {
        $this->appExtension = $appExtension;
        $this->setting = $requestStack->getSession()->get('setting');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('name',TextType::class,
                $this->options("form.product.name.title", 'form.product.name.placeholder'));
        if($this->setting->getProductWithImage()){
            $builder->add('imageFile',
                VichImageType::class,
                $this->options('form.product.imageFile.title', 'form.product.imageFile.placeholder',
                    [
                        'required' => false,
                        'allow_delete' => false,
                        'download_uri' => false,
                        'image_uri' => false
                    ]));
        }

          $builder->add('category', EntityType::class,[
                'class' => ProductCategory::class,
                'label' => 'form.product.category.title',
                'choice_label' => 'name'
            ]);

        if($this->setting->getWithPackaging()){
            $builder->add('packaging', EntityType::class,[
                'class' => ProductPackaging::class,
                'label' => 'form.product.packaging.title',
                'choice_label' => 'name'
            ])->add('packagingQty', IntegerType::class,
                $this->options("form.product.packagingQty.title", '0',[
                    'required' => false
                ]));
        }

        if($this->setting->getWithPurchasePrice()){
            $builder->add('buyPrice', NumberType::class,
                $this->options("form.product.buyPrice.title", '0'));
        }

        $builder
            ->add('sellPrice', NumberType::class,
            $this->options("form.product.sellPrice.title", '0'));
        if ($this->setting->getWithWholeSale()){
            $builder->add('wholePrice', NumberType::class,
                    $this->options("form.product.wholePrice.title", '0'));
        }
        $builder->add('stockAlert', IntegerType::class,
            $this->options("form.product.stockAlert.title", '0',[
            'required' => false
            ]));

        if ($this->setting->getWithProductReference()){
            $builder->add('reference', TextType::class,
                $this->options("form.product.reference.title", '',[
                    'required' => false
                ]));
        }

        if ($this->setting->getWithBarcode()){
            $builder->add('qrCode', TextType::class,
                $this->options("form.product.qrCode.title", '',[
                    'required' => false
                ]));
        }

        if($this->setting->getWithClubPoint() &&
            $this->appExtension->moduleExists(ModuleConstant::MODULES['club_point'])){
            $builder->add('point', NumberType::class,
                $this->options("form.product.point.title",'0',[
                    'required' => false
                ]));
            if ($this->setting->getWithWholeSale()){
                $builder->add('wholePoint', NumberType::class,
                    $this->options("form.product.wholePoint.title",'0',[
                        'required' => false
                    ]));
            }
        }

        if($this->setting->getProductWithDiscount()){
            $builder->add('discount', NumberType::class,
                $this->options("form.product.discount.title",'0',[
                    'required' => false
                ]));
            if ($this->setting->getWithWholeSale()) {
                $builder->add('wholeDiscount', NumberType::class,
                    $this->options("form.product.wholeDiscount.title", '0', [
                        'required' => false
                    ]));
            }
        }

        if($this->setting->getWithProduction() &&
            $this->appExtension->moduleExists(ModuleConstant::MODULES['prod_man'])){
            $builder->add('byProduct', CheckboxType::class,
                $this->options("form.product.byProduct.title",'',[
                    'required' => false
                ]));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
