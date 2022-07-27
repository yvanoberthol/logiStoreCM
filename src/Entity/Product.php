<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 * @UniqueEntity(
 *     fields={"name"},
 *     message="entity.product.name"
 * )
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups(groups="product:read")
     */
    private $id;

    /**
     * @Groups(groups="product:read")
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @Groups({"product:read"})
     * @ORM\Column(type="float")
     */
    private $buyPrice=0.0;

    /**
     * @Groups(groups="product:read")
     * @ORM\Column(type="float")
     * @Assert\GreaterThan(value="buyPrice",message="entity.product.sellPrice")
     */
    private $sellPrice=0.0;

    /**
     * @Groups(groups="product:read")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $qrCode;

    /**
     * @Groups(groups="product:read")
     * @ORM\Column(type="integer")
     */
    private $stockAlert = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    private $addDate;

    /**
     * @ORM\OneToMany(targetEntity=ProductSale::class, mappedBy="product")
     */
    private $productSales;

    /**
     * @ORM\OneToMany(targetEntity=ProductStock::class, mappedBy="product")
     */
    private $productStocks;

    /**
     * @Groups(groups="product:read")
     * @ORM\ManyToOne(targetEntity=ProductCategory::class,
     *     inversedBy="products", fetch="EAGER")
     */
    private $category;

    private $stock = 0;

    private $deletable = true;
    
    /**
     * @ORM\ManyToMany(targetEntity=Product::class, inversedBy="products")
     */
    private $substitutes;

    /**
     * @ORM\ManyToMany(targetEntity=Product::class, mappedBy="substitutes")
     */
    private $products;

    private $new;

    /**
     * @ORM\Column(type="boolean")
     */
    private $by_product = false;

    /**
     * @ORM\OneToMany(targetEntity=ProductPrice::class, mappedBy="product", orphanRemoval=true)
     */
    private $productPrices;

    /**
     * @Groups({"product:read"})
     * @ORM\Column(type="float")
     */
    private $wholePrice = 0.0;

    /**
     * @ORM\Column(type="integer")
     */
    private $point = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $wholePoint = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $discount = 0.0;

    /**
     * @ORM\Column(type="float")
     */
    private $wholeDiscount = 0.0;

    /**
     * @ORM\OneToMany(targetEntity=CustomerProductPrice::class, mappedBy="product", orphanRemoval=true)
     */
    private $customerProductPrices;

    /**
     * @ORM\ManyToOne(targetEntity=ProductPackaging::class, inversedBy="products")
     */
    private $packaging;

    /**
     * @ORM\Column(type="integer")
     */
    private $packagingQty=0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $imageName;

    /**
     * @Assert\Image()
     * @Vich\UploadableField(fileNameProperty="imageName",mapping="image_product")
     * @var File|null
     */
    protected $imageFile;

    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255, unique=true, nullable=true)
     */
    private $reference;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * @ORM\OneToMany(targetEntity=ProductAdjust::class, mappedBy="product")
     */
    private $productAdjusts;

    public function __construct()
    {
        $this->productSales = new ArrayCollection();
        $this->productStocks = new ArrayCollection();
        $this->substitutes = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->productPrices = new ArrayCollection();
        $this->customerProductPrices = new ArrayCollection();
        $this->productAdjusts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @Groups(groups="product:read")
     * @return string
     */
    public function getNameWithCategory(): ?string
    {
        $categoryName = ($this->getCategory() !== null)?' / '.$this->getCategory()->getName():'';
        return $this->name.$categoryName;
    }

    /**
     * @return integer
     */
    public function getEstimatedPackagingBatch(): ?int
    {
        if ($this->getPackaging() !== null){
            if ($this->getPackagingQty() > 0){
                $qty = $this->getStock() / $this->getPackagingQty();
                return round($qty,0,PHP_ROUND_HALF_DOWN);
            }

            return 0;
        }

        return null;
    }

    /**
     * @return integer
     */
    public function getRestQtyBatch(): ?int
    {
        if ($this->getPackaging() !== null){
            return ($this->getPackagingQty() > 0)
                ?$this->getStock() % $this->getPackagingQty() :0;
        }

        return null;
    }

    /**
     * @return integer
     */
    public function getPackagingBatchString(): ?int
    {
        if ($this->getPackaging() !== null){
            if ($this->getEstimatedPackagingBatch() > 0){
                return $this->getEstimatedPackagingBatch()
                    .' Pack(s) ('.$this->getPackagingQty().')';
            }

            return '('.$this->getPackagingQty().')';
        }

        return null;
    }

    /**
     * @return string
     */
    public function getPackagingName(): ?string
    {
        if ($this->getPackaging() !== null) {
            $packagingName = ($this->getPackaging() !== null) ? $this->getPackaging()->getName() : '//';
            return $packagingName . ' (' . $this->packagingQty . ')';
        }

        return null;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getBuyPrice(): ?float
    {
        return $this->buyPrice;
    }

    public function setBuyPrice(float $buyPrice): self
    {
        $this->buyPrice = $buyPrice;

        return $this;
    }

    public function getSellPrice(): ?float
    {
        return $this->sellPrice;
    }

    public function getSellPriceWithDiscount(): ?float
    {
        return $this->sellPrice - $this->getDiscount();
    }

    public function setSellPrice(float $sellPrice): self
    {
        $this->sellPrice = $sellPrice;

        return $this;
    }

    public function getProfit(): ?float
    {
        return $this->sellPrice - $this->buyPrice;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): self
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * @param File|null $imageFile
     * @return Product
     * @throws Exception
     */
    public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile){
            $this->updatedAt = new DateTimeImmutable();
        }
        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function setDate(): void {
        $this->updatedAt = new DateTime();
        $this->addDate = new DateTime();
    }

    /**
     * @param DateTimeInterface $updatedAt
     * @return Product
     */
    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function getBarCode(): ?string
    {
        if ($this->getQrCode() === null){
            return null;
        }
        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($this->getQrCode(), $generator::TYPE_CODE_128,
            1, 20, [0, 0, 0]);
        return base64_encode($barcode);
    }

    public function setQrCode(?string $qrCode): self
    {
        $this->qrCode = $qrCode;

        return $this;
    }

    public function getAddDate(): ?DateTimeInterface
    {
        return $this->addDate;
    }

    public function setAddDate(DateTimeInterface $addDate): self
    {
        $this->addDate = $addDate;

        return $this;
    }


    /**
     * @return Collection|ProductSale[]
     */
    public function getProductSales(): Collection
    {
        return $this->productSales;
    }

    /**
     * @return Collection|ProductStock[]
     */
    public function getProductStocks(): Collection
    {
        return $this->productStocks;
    }

    /**
     * @Groups(groups="product:read")
     * @return int
     */
    public function getStock(): ?int
    {
        return $this->stock;
    }

    /**
     * @param int $stock
     * @return Product
     */
    public function setStock($stock): self
    {
        $this->stock = $stock;
        return $this;
    }


    public function getStockAlert(): ?int
    {
        return $this->stockAlert;
    }

    public function setStockAlert(int $stockAlert): self
    {
        $this->stockAlert = $stockAlert;

        return $this;
    }

    public function getCategory(): ?ProductCategory
    {
        return $this->category;
    }

    public function setCategory(?ProductCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDeletable(): ?bool
    {
        return $this->deletable;
    }

    /**
     * @param bool $deletable
     * @return Product
     */
    public function setDeletable($deletable): self
    {
        $this->deletable = $deletable;
        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getSubstitutes(): Collection
    {
        return $this->substitutes;
    }

    public function addSubstitute(self $substitute): self
    {
        if (!$this->substitutes->contains($substitute)) {
            $this->substitutes[] = $substitute;
        }

        return $this;
    }

    public function removeSubstitute(self $substitute): self
    {
        $this->substitutes->removeElement($substitute);

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(self $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->addSubstitute($this);
        }

        return $this;
    }

    public function removeProduct(self $product): self
    {
        if ($this->products->removeElement($product)) {
            $product->removeSubstitute($this);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->new;
    }

    /**
     * @param bool $new
     * @return Product
     */
    public function setNew(bool $new): ?self
    {
        $this->new = $new;
        return $this;
    }

    public function getByProduct(): ?bool
    {
        return $this->by_product;
    }

    public function setByProduct(bool $by_product): self
    {
        $this->by_product = $by_product;

        return $this;
    }

    /**
     * @return Collection|ProductPrice[]
     */
    public function getProductPrices(): Collection
    {
        return $this->productPrices;
    }

    public function addProductPrice(ProductPrice $productPrice): self
    {
        if (!$this->productPrices->contains($productPrice)) {
            $this->productPrices[] = $productPrice;
            $productPrice->setProduct($this);
        }

        return $this;
    }

    public function removeProductPrice(ProductPrice $productPrice): self
    {
        if ($this->productPrices->removeElement($productPrice)) {
            // set the owning side to null (unless already changed)
            if ($productPrice->getProduct() === $this) {
                $productPrice->setProduct(null);
            }
        }

        return $this;
    }

    public function getWholePrice(): ?float
    {
        return ($this->wholePrice <= 0 || $this->wholePrice === null)
            ? $this->getSellPrice(): ($this->wholePrice - $this->getWholeDiscount());
    }

    public function setWholePrice(float $wholePrice): self
    {
        $this->wholePrice = $wholePrice;

        return $this;
    }

    public function getPoint(): ?int
    {
        return $this->point;
    }

    public function setPoint(int $point): self
    {
        $this->point = $point;

        return $this;
    }

    public function getWholePoint(): ?int
    {
        return $this->wholePoint;
    }

    public function setWholePoint(int $wholePoint): self
    {
        $this->wholePoint = $wholePoint;

        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(float $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    public function getWholeDiscount(): ?float
    {
        return $this->wholeDiscount;
    }

    public function setWholeDiscount(float $wholeDiscount): self
    {
        $this->wholeDiscount = $wholeDiscount;

        return $this;
    }

    /**
     * @return Collection|CustomerProductPrice[]
     */
    public function getCustomerProductPrices(): Collection
    {
        return $this->customerProductPrices;
    }

    public function addCustomerProductPrice(CustomerProductPrice $customerProductPrice): self
    {
        if (!$this->customerProductPrices->contains($customerProductPrice)) {
            $this->customerProductPrices[] = $customerProductPrice;
            $customerProductPrice->setProduct($this);
        }

        return $this;
    }

    public function removeCustomerProductPrice(CustomerProductPrice $customerProductPrice): self
    {
        if ($this->customerProductPrices->removeElement($customerProductPrice)) {
            // set the owning side to null (unless already changed)
            if ($customerProductPrice->getProduct() === $this) {
                $customerProductPrice->setProduct(null);
            }
        }

        return $this;
    }

    public function getPackaging(): ?ProductPackaging
    {
        return $this->packaging;
    }

    public function setPackaging(?ProductPackaging $packaging): self
    {
        $this->packaging = $packaging;

        return $this;
    }

    public function getPackagingQty(): ?int
    {
        return $this->packagingQty;
    }

    public function setPackagingQty(int $packagingQty): self
    {
        $this->packagingQty = $packagingQty;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return Collection|ProductAdjust[]
     */
    public function getProductAdjusts(): Collection
    {
        return $this->productAdjusts;
    }

    public function addProductAdjust(ProductAdjust $productAdjust): self
    {
        if (!$this->productAdjusts->contains($productAdjust)) {
            $this->productAdjusts[] = $productAdjust;
            $productAdjust->setProduct($this);
        }

        return $this;
    }

    public function removeProductAdjust(ProductAdjust $productAdjust): self
    {
        if ($this->productAdjusts->removeElement($productAdjust)) {
            // set the owning side to null (unless already changed)
            if ($productAdjust->getProduct() === $this) {
                $productAdjust->setProduct(null);
            }
        }

        return $this;
    }
}
