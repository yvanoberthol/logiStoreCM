<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Serializable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable()
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *     fields={"email"},
 *     message="entity.user.email.unique"
 * )
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface,PasswordAuthenticatedUserInterface,EquatableInterface,Serializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank(message="entity.user.name")
     * @ORM\Column(type="string", length=255,unique=true)
     */
    private $name;

    /**
     * @Assert\Email(message="entity.user.email.valide")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @Assert\Positive(message="entity.user.phone.positive")
     * @ORM\Column(type="string", nullable=true)
     */
    private $firstPhoneNumber;

    /**
     * @Assert\Positive(message="entity.user.phone.positive")
     * @ORM\Column(type="string", nullable=true)
     */
    private $secondPhoneNumber;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $gender;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $district;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $imageName;

    /**
     * @Assert\Image()
     * @Vich\UploadableField(fileNameProperty="imageName",mapping="image_user")
     * @var File|null
     */
    protected $imageFile;

    /**
     * @ORM\Column(type="datetime")
     */
    private $addDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime
     */
    private $lastConnection;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime
     */
    private $lastDeconnection;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;
    

    /**
     * @ORM\ManyToOne(targetEntity=Role::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    private $role;

    /**
     * @ORM\OneToMany(targetEntity=Sale::class, mappedBy="recorder", orphanRemoval=true)
     */
    private $sales;

    /**
     * @ORM\OneToMany(targetEntity=Stock::class, mappedBy="recorder", orphanRemoval=true)
     */
    private $stocks;

    /**
     * @ORM\OneToMany(targetEntity=Loss::class, mappedBy="recorder", orphanRemoval=true)
     */
    private $losses;

    /**
     * @ORM\OneToMany(targetEntity=Connection::class, mappedBy="user", orphanRemoval=true)
     */
    private $connections;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $language='en';

    /**
     * @ORM\OneToMany(targetEntity=Transaction::class, mappedBy="recorder")
     */
    private $transactions;

    /**
     * @ORM\OneToMany(targetEntity=StockPayment::class, mappedBy="recorder")
     */
    private $stockPayments;

    /**
     * @ORM\OneToMany(targetEntity=Attendance::class, mappedBy="user", orphanRemoval=true)
     */
    private $attendances;

    /**
     * @ORM\Column(type="float")
     */
    private $salary=0.0;

    /**
     * @ORM\OneToMany(targetEntity=SalaryPayment::class, mappedBy="employee")
     */
    private $salaryPayments;

    /**
     * @ORM\OneToMany(targetEntity=NoticeBoard::class, mappedBy="recorder")
     */
    private $noticeBoards;

    /**
     * @ORM\OneToMany(targetEntity=NoticeBoardEmployee::class, mappedBy="employee")
     */
    private $noticeBoardEmployees;

    /**
     * @ORM\OneToMany(targetEntity=ProductSaleReturn::class, mappedBy="recorder")
     */
    private $productSaleReturns;

    /**
     * @ORM\OneToMany(targetEntity=ProductStockReturn::class, mappedBy="recorder")
     */
    private $productStockReturns;

    /**
     * @ORM\OneToOne(targetEntity=Customer::class, cascade={"persist", "remove"})
     */
    private $customer;

    /**
     * @ORM\Column(type="boolean")
     */
    private $canCustomer = false;

    /**
     * @ORM\OneToMany(targetEntity=EmployeeFee::class, mappedBy="employee")
     */
    private $employeeFees;

    /**
     * @ORM\ManyToMany(targetEntity=ProductCategory::class, inversedBy="users")
     */
    private $categories;

    /**
     * @ORM\OneToMany(targetEntity=Encashment::class, mappedBy="employee", orphanRemoval=true)
     */
    private $encashments;

    /**
     * @ORM\OneToMany(targetEntity=Adjustment::class, mappedBy="recorder", orphanRemoval=true)
     */
    private $adjustments;

    public function __construct()
    {
        $this->sales = new ArrayCollection();
        $this->stocks = new ArrayCollection();
        $this->losses = new ArrayCollection();
        $this->connections = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->stockPayments = new ArrayCollection();
        $this->attendances = new ArrayCollection();
        $this->salaryPayments = new ArrayCollection();
        $this->noticeBoards = new ArrayCollection();
        $this->noticeBoardEmployees = new ArrayCollection();
        $this->productSaleReturns = new ArrayCollection();
        $this->productStockReturns = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->encashments = new ArrayCollection();
        $this->adjustments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAllName(): ?string
    {
        return $this->name;
    }

    public function getPhone(): ?string
    {
        return $this->getFirstPhoneNumber().' / '.$this->getSecondPhoneNumber();
    }

    public function getInitials(): ?string
    {
        return substr($this->name,0,1);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstPhoneNumber(): ?string
    {
        return $this->firstPhoneNumber;
    }

    public function setFirstPhoneNumber(?string $firstPhoneNumber): self
    {
        $this->firstPhoneNumber = $firstPhoneNumber;

        return $this;
    }

    public function getSecondPhoneNumber(): ?string
    {
        return $this->secondPhoneNumber;
    }

    public function setSecondPhoneNumber(?string $secondPhoneNumber): self
    {
        $this->secondPhoneNumber = $secondPhoneNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     * @return User
     */
    public function setGender($gender): self
    {
        $this->gender = $gender;
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
     * @param DateTimeInterface $updatedAt
     * @return User
     */
    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLastConnection(): ?DateTimeInterface
    {
        return $this->lastConnection;
    }

    /**
     * @param DateTimeInterface $lastConnection
     * @return User
     */
    public function setLastConnection(?DateTimeInterface $lastConnection): self
    {
        $this->lastConnection = $lastConnection;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLastDeconnection(): ?DateTimeInterface
    {
        return $this->lastDeconnection;
    }

    /**
     * @param DateTimeInterface|null $lastDeconnection
     * @return User
     */
    public function setLastDeconnection(?DateTimeInterface $lastDeconnection): self
    {
        $this->lastDeconnection = $lastDeconnection;
        return $this;
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
     * @return User
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

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): self
    {
        $this->district = $district;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     * @return User
     */
    public function setPlainPassword($plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }


    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function getRule(): ?string
    {
        return $this->getRole()->getName();
    }

    public function getTitle(): ?string
    {
        return substr($this->getRole()->getName(),5);
    }


    public function setRole(?Role $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return Collection|Sale[]
     */
    public function getSales(): Collection
    {
        return $this->sales;
    }

    /**
     * @return int
     */
    public function getSaleAmounts(): ?int
    {
        return array_sum($this->getSales()->map(static function(Sale $sale){
            return $sale->getAmount();
        })->toArray());
    }

    /**
     * @return Collection|Stock[]
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    /**
     * @return Collection|Loss[]
     */
    public function getLosses(): Collection
    {
        return $this->losses;
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
     * Returns the roles granted to the user.
     *
     *     public function getRoles()
     *     {
     *         return ['ROLE_USER'];
     *     }
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return string[] The user roles
     */
    public function getRoles(): ?array
    {
        return [$this->getRole()->getName()];
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt(): ?string
    {
        return 'sdfss5464d5sf79s8df';
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername(): string
    {
        return $this->getEmail()??$this->getName();
    }

    public function getUserIdentifier(): string
    {
        return $this->getEmail()??$this->getName();
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
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
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return User
     */
    public function setLanguage($language): self
    {
        $this->language = $language;
        return $this;
    }



    /**
     * @ORM\PrePersist
     */
    public function setDate(){
        $this->updatedAt = new DateTime();
        $this->addDate = new DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function upDateDate(){
        $this->updatedAt = new DateTime();
    }

    public function serialize()
    {
        return serialize(
            array(
                $this->id,
                $this->name,
                $this->email,
                $this->firstPhoneNumber,
                $this->secondPhoneNumber,
                $this->imageName,
                $this->district,
                $this->gender,
                $this->plainPassword,
                $this->password,
                $this->enabled,
                $this->lastConnection,
                $this->lastDeconnection,
                $this->updatedAt,
                $this->addDate,
                $this->language
            )
        );
    }

    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->name,
            $this->email,
            $this->firstPhoneNumber,
            $this->secondPhoneNumber,
            $this->imageName,
            $this->district,
            $this->gender,
            $this->plainPassword,
            $this->password,
            $this->enabled,
            $this->lastConnection,
            $this->lastDeconnection,
            $this->updatedAt,
            $this->addDate,
            $this->language
            ) = unserialize($serialized, array('allowed_classes' => false));
    }

    /**
     * @return Collection|Connection[]
     */
    public function getConnections(): Collection
    {
        return $this->connections;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setRecorder($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getRecorder() === $this) {
                $transaction->setRecorder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|StockPayment[]
     */
    public function getStockPayments(): Collection
    {
        return $this->stockPayments;
    }

    public function addStockPayment(StockPayment $stockPayment): self
    {
        if (!$this->stockPayments->contains($stockPayment)) {
            $this->stockPayments[] = $stockPayment;
            $stockPayment->setRecorder($this);
        }

        return $this;
    }

    public function removeStockPayment(StockPayment $stockPayment): self
    {
        if ($this->stockPayments->removeElement($stockPayment)) {
            // set the owning side to null (unless already changed)
            if ($stockPayment->getRecorder() === $this) {
                $stockPayment->setRecorder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Attendance[]
     */
    public function getAttendances(): Collection
    {
        return $this->attendances;
    }

    public function addAttendance(Attendance $attendance): self
    {
        if (!$this->attendances->contains($attendance)) {
            $this->attendances[] = $attendance;
            $attendance->setUser($this);
        }

        return $this;
    }

    public function removeAttendance(Attendance $attendance): self
    {
        if ($this->attendances->removeElement($attendance)) {
            // set the owning side to null (unless already changed)
            if ($attendance->getUser() === $this) {
                $attendance->setUser(null);
            }
        }

        return $this;
    }

    public function getSalary(): ?float
    {
        return $this->salary;
    }

    public function setSalary(float $salary): self
    {
        $this->salary = $salary;

        return $this;
    }

    /**
     * @return Collection|SalaryPayment[]
     */
    public function getSalaryPayments(): Collection
    {
        return $this->salaryPayments;
    }

    public function addSalaryPayment(SalaryPayment $salaryPayment): self
    {
        if (!$this->salaryPayments->contains($salaryPayment)) {
            $this->salaryPayments[] = $salaryPayment;
            $salaryPayment->setEmployee($this);
        }

        return $this;
    }

    public function removeSalaryPayment(SalaryPayment $salaryPayment): self
    {
        if ($this->salaryPayments->removeElement($salaryPayment)) {
            // set the owning side to null (unless already changed)
            if ($salaryPayment->getEmployee() === $this) {
                $salaryPayment->setEmployee(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|NoticeBoard[]
     */
    public function getNoticeBoards(): Collection
    {
        return $this->noticeBoards;
    }

    public function addNoticeBoard(NoticeBoard $noticeBoard): self
    {
        if (!$this->noticeBoards->contains($noticeBoard)) {
            $this->noticeBoards[] = $noticeBoard;
            $noticeBoard->setRecorder($this);
        }

        return $this;
    }

    public function removeNoticeBoard(NoticeBoard $noticeBoard): self
    {
        if ($this->noticeBoards->removeElement($noticeBoard)) {
            // set the owning side to null (unless already changed)
            if ($noticeBoard->getRecorder() === $this) {
                $noticeBoard->setRecorder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|NoticeBoardEmployee[]
     */
    public function getNoticeBoardEmployees(): Collection
    {
        return $this->noticeBoardEmployees;
    }

    public function addNoticeBoardEmployee(NoticeBoardEmployee $noticeBoardEmployee): self
    {
        if (!$this->noticeBoardEmployees->contains($noticeBoardEmployee)) {
            $this->noticeBoardEmployees[] = $noticeBoardEmployee;
            $noticeBoardEmployee->setEmployee($this);
        }

        return $this;
    }

    public function removeNoticeBoardEmployee(NoticeBoardEmployee $noticeBoardEmployee): self
    {
        if ($this->noticeBoardEmployees->removeElement($noticeBoardEmployee)) {
            // set the owning side to null (unless already changed)
            if ($noticeBoardEmployee->getEmployee() === $this) {
                $noticeBoardEmployee->setEmployee(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ProductSaleReturn[]
     */
    public function getProductSaleReturns(): Collection
    {
        return $this->productSaleReturns;
    }

    public function addProductSaleReturn(ProductSaleReturn $productSaleReturn): self
    {
        if (!$this->productSaleReturns->contains($productSaleReturn)) {
            $this->productSaleReturns[] = $productSaleReturn;
            $productSaleReturn->setRecorder($this);
        }

        return $this;
    }

    public function removeProductSaleReturn(ProductSaleReturn $productSaleReturn): self
    {
        if ($this->productSaleReturns->removeElement($productSaleReturn)) {
            // set the owning side to null (unless already changed)
            if ($productSaleReturn->getRecorder() === $this) {
                $productSaleReturn->setRecorder(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection|ProductStockReturn[]
     */
    public function getProductStockReturns(): Collection
    {
        return $this->productStockReturns;
    }

    public function addProductStockReturn(ProductStockReturn $productStockReturn): self
    {
        if (!$this->productStockReturns->contains($productStockReturn)) {
            $this->productStockReturns[] = $productStockReturn;
            $productStockReturn->setRecorder($this);
        }

        return $this;
    }

    public function removeProductStockReturn(ProductStockReturn $productStockReturn): self
    {
        if ($this->productStockReturns->removeElement($productStockReturn)) {
            // set the owning side to null (unless already changed)
            if ($productStockReturn->getRecorder() === $this) {
                $productStockReturn->setRecorder(null);
            }
        }

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCanCustomer(): ?bool
    {
        return $this->canCustomer;
    }

    public function setCanCustomer(bool $canCustomer): self
    {
        $this->canCustomer = $canCustomer;

        return $this;
    }

    /**
     * @return Collection|EmployeeFee[]
     */
    public function getEmployeeFees(): Collection
    {
        return $this->employeeFees;
    }

    public function addEmployeeFee(EmployeeFee $employeeFee): self
    {
        if (!$this->employeeFees->contains($employeeFee)) {
            $this->employeeFees[] = $employeeFee;
            $employeeFee->setEmployee($this);
        }

        return $this;
    }

    public function removeEmployeeFee(EmployeeFee $employeeFee): self
    {
        if ($this->employeeFees->removeElement($employeeFee)) {
            // set the owning side to null (unless already changed)
            if ($employeeFee->getEmployee() === $this) {
                $employeeFee->setEmployee(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ProductCategory[]
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(ProductCategory $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }

        return $this;
    }

    public function removeCategory(ProductCategory $category): self
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection|Encashment[]
     */
    public function getEncashments(): Collection
    {
        return $this->encashments;
    }

    public function addEncashment(Encashment $encashment): self
    {
        if (!$this->encashments->contains($encashment)) {
            $this->encashments[] = $encashment;
            $encashment->setEmployee($this);
        }

        return $this;
    }

    public function removeEncashment(Encashment $encashment): self
    {
        if ($this->encashments->removeElement($encashment)) {
            // set the owning side to null (unless already changed)
            if ($encashment->getEmployee() === $this) {
                $encashment->setEmployee(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Adjustment[]
     */
    public function getAdjustments(): Collection
    {
        return $this->adjustments;
    }

    public function addAdjustment(Adjustment $adjustment): self
    {
        if (!$this->adjustments->contains($adjustment)) {
            $this->adjustments[] = $adjustment;
            $adjustment->setRecorder($this);
        }

        return $this;
    }

    public function removeAdjustment(Adjustment $adjustment): self
    {
        if ($this->adjustments->removeElement($adjustment)) {
            // set the owning side to null (unless already changed)
            if ($adjustment->getRecorder() === $this) {
                $adjustment->setRecorder(null);
            }
        }

        return $this;
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement @method string getUserIdentifier()
    }

    /**
     * The equality comparison should neither be done by referential equality
     * nor by comparing identities (i.e. getId() === getId()).
     *
     * However, you do not need to compare every attribute, but only those that
     * are relevant for assessing whether re-authentication is required.
     *
     * @param UserInterface $user
     * @return bool
     */
    public function isEqualTo(UserInterface $user): bool
    {

        return ($this->getEmail() === $user->getEmail() || $this->getName() === $user->getName());
    }
}
