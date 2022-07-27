<?php

namespace App\Controller;

use App\Dto\ProductDto;
use App\Dto\ProductStockDto;
use App\Dto\ProductStockSaleDto;
use App\Entity\Attendance;
use App\Entity\Customer;
use App\Entity\Expense;
use App\Entity\ExpenseType;
use App\Entity\Loss;
use App\Entity\Permission;
use App\Entity\Product;
use App\Entity\ProductPackaging;
use App\Entity\ProductPrice;
use App\Entity\ProductSale;
use App\Entity\ProductSaleReturn;
use App\Entity\ProductStock;
use App\Entity\ProductStockSale;
use App\Entity\SalaryPayment;
use App\Entity\Sale;
use App\Entity\SalePayment;
use App\Entity\Stock;
use App\Entity\Subscription;
use App\Entity\Supplier;
use App\Entity\Theme;
use App\Entity\User;
use App\Repository\AttendanceRepository;
use App\Repository\CustomerRepository;
use App\Repository\ExpenseTypeRepository;
use App\Repository\LossTypeRepository;
use App\Repository\PaymentMethodRepository;
use App\Repository\PermissionRepository;
use App\Repository\ProductPackagingRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductSaleRepository;
use App\Repository\ProductSaleReturnRepository;
use App\Repository\ProductStockRepository;
use App\Repository\ProductStockSaleRepository;
use App\Repository\RoleRepository;
use App\Repository\SalaryPaymentRepository;
use App\Repository\SalePaymentRepository;
use App\Repository\SaleRepository;
use App\Repository\StockRepository;
use App\Repository\SupplierRepository;
use App\Repository\UserRepository;
use App\Service\CartService;
use App\Service\ProductService;
use App\Util\AttendanceStatusConstant;
use App\Util\CustomerTypeConstant;
use App\Util\RoleConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class TestController extends AbstractController
{

    /**
     * @Route("/test/stockAlert", name="test_stock_alert", methods={"POST","GET"})
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function testStockAlert(ProductRepository $productRepository,
                         EntityManagerInterface $entityManager): Response
    {
        $stockAlerts = [5,10,15,20];

        $products = $productRepository->findAll();

        foreach ($products as $product){
            if ($product->getStockAlert() === 0){
                $product
                    ->setStockAlert($stockAlerts[random_int(0,count($stockAlerts)-1)]);
                $entityManager->persist($product);
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/packaging", name="test_packaging", methods={"POST","GET"})
     * @param ProductRepository $productRepository
     * @param ProductPackagingRepository $packagingRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function testPackaging(ProductRepository $productRepository,
                                   ProductPackagingRepository $packagingRepository,
                                   EntityManagerInterface $entityManager): Response
    {
        $packs = [5,6,12,20];

        $products = $productRepository->findAll();
        $packagings = $packagingRepository->findAll();

        foreach ($products as $product){

            $packaging = (random_int(1,100) >20)?$packagings[0]:$packagings[1];

            if ($product->getPackagingQty() === 0){
                $product
                    ->setPackagingQty($packs[random_int(0,count($packs)-1)]);
                $product->setPackaging($packaging);
                $entityManager->persist($product);
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/product/point", name="test_product_point", methods={"POST","GET"})
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function testPoint(ProductRepository $productRepository,
                         EntityManagerInterface $entityManager): Response
    {

        $products = $productRepository->findAll();

        foreach ($products as $product){
            if ($product->getProfit() >= 100){
                $point = round(($product->getProfit() / 100));
                $product->setPoint($point);
                if ($point >= 5){
                    $wholePoint = $point - random_int(1,3);
                    $product->setWholePoint($wholePoint);
                }else{
                    $product->setWholePoint($point);
                }

                $entityManager->persist($product);
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/product/price", name="test_product_price", methods={"POST","GET"})
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function testProductPrice(ProductRepository $productRepository,
                              EntityManagerInterface $entityManager): Response
    {

        $products = $productRepository->findAll();

        foreach ($products as $product){
            $difference = $product->getSellPrice() - $product->getBuyPrice();

            if ($difference >= 5){
                $nbProductPrice = random_int(1,3);

                $discount = (int) round($difference / 5);

                $qtys = [];
                for ($i=0;$nbProductPrice>$i;$i++){

                    do{
                        $qty = random_int(3,10);
                    }while(in_array($qty,$qtys,true));

                    $productPrice = new ProductPrice();
                    $productPrice->setProduct($product);
                    $productPrice->setQty($qty);
                    $productPrice->setUnitPrice($product->getBuyPrice() + random_int($discount,$difference-$discount));
                    $entityManager->persist($productPrice);

                    $qtys[] = $qty;
                }

            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }


    /**
     * @Route("/test/product/substitute", name="test_product_substitute", methods={"POST","GET"})
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function testSubstitute(ProductRepository $productRepository,
                         EntityManagerInterface $entityManager): Response
    {

        $products = $productRepository->findAll();

        foreach ($products as $product){
            $nbSubstitutes = random_int(0,5);

            for ($i=0;$i<$nbSubstitutes;$i++){
                $substitute = $products[random_int(0,count($products)-1)];
                $product->addSubstitute($substitute);
                $substitute->addSubstitute($product);
                $entityManager->persist($substitute);
            }
            $entityManager->persist($product);
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/productStock/adjust", name="test_productStock_adjust", methods={"POST","GET"})
     * @param ProductService $productService
     * @param ProductRepository $productRepository
     * @param ProductStockRepository $productStockRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function testProductStockAdjustt(ProductService $productService,
                                            ProductRepository $productRepository,
                                            ProductStockRepository $productStockRepository,
                              EntityManagerInterface $entityManager): Response
    {
        $productStockDispos = [];

        $productStocks = array_filter(array_map(static function (ProductStock $productStock) use($productService){
            return $productService->countQtyRemaining($productStock);
        },$productStockRepository->findAll()),
            static function (ProductStock $productStock){
                return 0 > $productStock->getQtyRemaining();
            });

        $products = array_map(static function(ProductStock $productStock){
            return $productStock->getProduct();
        },$productStocks);

        $result = [];
        foreach ($productStocks as $productStock) {
            $productStockSales = array_map(static function(ProductStockSale $productStockSale){
                return ProductStockSaleDto::createFromEntity($productStockSale);
            },$productStock->getProductStockSales()->toArray());
            $product = [
                'product' => ProductDto::createFromEntity($productStock->getProduct()),
                'qtyRemaining' => $productStock->getQtyRemaining(),
                'last'  => $productStockSales[count($productStockSales)-1],
                'productStockSales' => $productStockSales,
            ];

            $result[] = $product;
        }

        foreach ($products as $product){
            $productStockDispos[] = $productService
                ->getProductStockDispoByProduct($product);
        }

        dd($result);

        return $this->redirectToRoute('home');

    }


    /**
     * @Route("/test/delete/product", name="test_delete_product", methods={"POST","GET"})
     * @param ProductRepository $productRepository
     * @param ProductService $productService
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function testDeleteProduct(ProductRepository $productRepository,
                         ProductService $productService,
                         EntityManagerInterface $entityManager): Response
    {

        $products = $productService
            ->countStocks($productRepository->findAll());

        foreach ($products as $product){
            if (!$product->getDeletable() &&
                count($product->getProductSales()) === 0)
                $entityManager->remove($product);
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/delete/sale", name="test_delete_sale", methods={"POST","GET"})
     * @param ProductSaleRepository $productSaleRepository
     * @param SaleRepository $saleRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function testDeleteSale(ProductSaleRepository $productSaleRepository,
                                      SaleRepository $saleRepository,
                                      EntityManagerInterface $entityManager): Response
    {

        for($i=121580;$i <= 128148;$i++){
            $sale = $saleRepository->find($i);
            $productSales = $productSaleRepository->findBy(['sale' => $sale]);
            foreach ($productSales as $productSale){
                foreach ($productSale->getProductStockSales() as $productSaleStock){
                    $entityManager->remove($productSaleStock);
                }
                $entityManager->remove($productSale);
            }

            $entityManager->remove($sale);
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/delete/stock", name="test_delete_stock", methods={"POST","GET"})
     * @param ProductStockRepository $productStockRepository
     * @param StockRepository $stockRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function testDeleteStock(ProductStockRepository $productStockRepository,
                                   StockRepository $stockRepository,
                                   EntityManagerInterface $entityManager): Response
    {

        for($i=121;$i <= 133;$i++){
            $stock= $stockRepository->find($i);
            $productStocks = $productStockRepository->findBy(['stock' => $stock]);
            foreach ($productStocks as $productStock){
                foreach ($productStock->getProductStockSales() as $productSaleStock){
                    $entityManager->remove($productSaleStock);
                }
                $entityManager->remove($productStock);
            }

            $entityManager->remove($stock);
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }


    /**
     * @Route("/test/sale/fix", name="test_sale_fix", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param SaleRepository $saleRepository
     * @return Response
     * @throws Exception
     */
    public function testSaleFix(EntityManagerInterface $entityManager,
                             SaleRepository $saleRepository): Response
    {

        $sales = $saleRepository->findAll();

        foreach ($sales as $sale){
            $sale->setAmount($sale->getAmountProductSales());
            $sale->setProfit($sale->getProfitProductSales());

            $entityManager->persist($sale);
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/sale/up", name="test_sale_up", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param SaleRepository $saleRepository
     * @return Response
     * @throws Exception
     */
    public function testSaleUp(EntityManagerInterface $entityManager,
                         SaleRepository $saleRepository): Response
    {

        $sales = $saleRepository->findAll();

        foreach ($sales as $sale){
            $sale->setAmount($sale->getAmountProductSales());
            $sale->setProfit($sale->getProfitProductSales());

            if (random_int(0,1)){
                $sale->setAmountReceived($sale->getAmount());
            }else{
                $rest = $sale->getAmount() % 500;
                $amountBefore = $sale->getAmount() - $rest;

                if (($amountBefore / 500) <= 1){
                    $amountReceived = 500;
                }else{
                    $amountReceived = $amountBefore + (random_int(1,5)*500);
                }
                $sale->setAmountReceived($amountReceived);
            }

            $entityManager->persist($sale);
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/stock/fix", name="test_stock_fix", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param StockRepository $stockRepository
     * @return Response
     */
    public function testStockFix(EntityManagerInterface $entityManager,
                          StockRepository $stockRepository): Response
     {

         $stocks = $stockRepository->findAll();

         foreach ($stocks as $stock){
             $stock->setAmount($stock->getAmountProductStocks());
             $entityManager->persist($stock);
         }

         $entityManager->flush();

         return $this->redirectToRoute('home');

     }

    /**
     * @Route("/test/user", name="test_user", methods={"POST","GET"})
     * @param UserPasswordHasherInterface $passwordEncoder
     * @param EntityManagerInterface $entityManager
     * @param RoleRepository $roleRepository
     * @return Response
     * @throws Exception
     */
    public function testUser(UserPasswordHasherInterface $passwordEncoder,
                             EntityManagerInterface $entityManager,
                             RoleRepository $roleRepository): Response
    {


        $gender = ['male','female'];
        $roles = $roleRepository->findAll();


        for ($i=0;$i < 5;$i++){
            $faker = Factory::create();
            $user = new User();
            $user->setName($faker->name($gender[random_int(0,1)]));
            $user->setEnabled(random_int(0,1));
            $user->setEmail($faker->email);

            $newpassword = $passwordEncoder
                ->hashPassword($user, '123456');
            $user->setPlainPassword('123456');
            $user->setPassword($newpassword);
            do{
                $role = $roles[random_int(0,count($roles)-1)];
            }while($role->getTitle() === RoleConstant::ADMIN);
            $user->setRole($role);
            if ($role->getTitle() !== RoleConstant::ADMIN){
                $salaries = [50000,60000,80000,100000,120000,150000,160000,180000,200000];
                $user->setSalary($salaries[random_int(0,8)]);
            }

            $entityManager->persist($user);
        }
        $entityManager->flush();

        return $this->redirectToRoute('home');


    }


    /**
     * @Route("/test/attendance", name="test_attendance", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param AttendanceRepository $attendanceRepository
     * @return Response
     * @throws Exception
     */
    public function testAttendance(EntityManagerInterface $entityManager,
                             UserRepository $userRepository,
                             AttendanceRepository $attendanceRepository): Response
    {

        $status = ['A','LA','H','LE'];

        $faker = Factory::create();
        $employees = $userRepository->findEmployees();

        foreach ($employees as $employee) {
            for ($y=2018;$y <= 2022;$y++){
                for ($j=1;$j<=12;$j++){
                    for ($i=0;$i < random_int(0,5);$i++){
                        $j = str_pad($j,2,'0',STR_PAD_LEFT);
                        $date = $faker->dateTimeBetween($y.'-'.$j.'-01',$y.'-'.$j.'-31');
                        //$date = $faker->dateTimeBetween('2022-01-01','2022-01-31');
                        $attendance = $attendanceRepository
                            ->findByDateAndEmployee($date->format('Y-m-d'),$employee);

                        if ($attendance === null){
                            $attendance = new Attendance();
                            $attendance->setDate($date);
                            $attendance->setStatus($status[random_int(0,count($status)-1)]);
                            $attendance->setUser($employee);
                            $entityManager->persist($attendance);

                        }else{
                            $attendance->setStatus($status[random_int(0,count($status)-1)]);
                        }

                        $entityManager->flush();
                    }
                }
            }
        }

        return $this->redirectToRoute('home');


    }

    /**
     * @Route("/test/customer", name="test_customer", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function testCustomer(EntityManagerInterface $entityManager): Response
    {


        $gender = ['male','female'];
        $type = ['Simple Customer','Reseller'];

        for ($i=0;$i < random_int(10,20);$i++){
            $faker = Factory::create();
            $customer = new Customer();
            $customer->setName($faker->name($gender[random_int(0,1)]));
            $customer->setGender($gender[random_int(0,1)]);
            $customer->setPhoneNumber($faker->phoneNumber);
            $customer->setEmail($faker->email);
            $customer->setAddress($faker->address);
            $customer->setType(CustomerTypeConstant::TYPEKEYS[$type[random_int(0,1)]]);

            $entityManager->persist($customer);
        }
        $entityManager->flush();

        return $this->redirectToRoute('home');


    }


    /**
     * @Route("/test/supplier", name="test_supplier", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function testSupplier(EntityManagerInterface $entityManager): Response
    {

        $type = ['Corporation','Physical person'];

        for ($i=0;$i < random_int(4,10);$i++){
            $faker = Factory::create();
            $supplier = new Supplier();
            $supplier->setName($faker->company);
            $supplier->setFirstPhoneNumber($faker->phoneNumber);
            $supplier->setSecondPhoneNumber($faker->phoneNumber);
            $supplier->setEmail($faker->email);
            $supplier->setType($type[random_int(0,1)]);

            $entityManager->persist($supplier);
        }
        $entityManager->flush();

        return $this->redirectToRoute('home');


    }

    /**
     * @Route("/test/salary", name="test_salary", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param SalaryPaymentRepository $salaryPaymentRepository
     * @return Response
     * @throws Exception
     */
    public function testSalary(EntityManagerInterface $entityManager,
                             UserRepository $userRepository,
                             SalaryPaymentRepository $salaryPaymentRepository): Response
    {

        $faker = Factory::create();
        $employees = $userRepository->findEmployees();

        foreach ($employees as $employee) {
            for ($y=2018;$y <= 2022;$y++) {
                for ($j = 1; $j <= 12; $j++) {
                    $random = random_int(1,3);
                    for ($i = 0; $i < $random; $i++) {
                        $j = str_pad($j, 2, '0', STR_PAD_LEFT);
                        $date = $faker->dateTimeBetween($y.'-' . $j . '-01', $y.'-' . $j . '-31');
                        //$date = $faker->dateTimeBetween('2022-01-01','2022-01-31');

                        $salaryPayments = $salaryPaymentRepository
                            ->findByMonthYear($date->format('m'),
                                $date->format('Y'), $employee);

                        $salaryPaid = array_sum(array_map(static function (SalaryPayment $salaryPayment) {
                            return $salaryPayment->getAmount();
                        }, $salaryPayments));

                        $salaryLied = (empty($salaryPayments)) ? $employee->getSalary() : $salaryPayments[0]->getSalary();

                        if ($random === 1) {
                            $amount = $salaryLied;
                        } else {
                            if ($i === 0) {
                                $rest = $salaryLied % 5000;
                                $amountBefore = $salaryLied - $rest;
                                $amount = $amountBefore;

                                if (($amountBefore / 5000) >= 2) {
                                    $amount -= ((($amountBefore / 5000) - random_int(1, ($amountBefore / 5000) - 1)) * 5000);
                                }

                            } else {

                                $amount = $salaryLied - $salaryPaid;
                                $rest = $amount % 5000;
                                $amountBefore = $amount - $rest;
                                if (($amountBefore / 5000) >= 2 && random_int(0, 1)) {
                                    $amount -= ((random_int(1, ($amountBefore / 5000) - 1)) * 5000);
                                }

                            }
                        }

                        if (($salaryPaid + $amount) <= $salaryLied) {
                            $salaryPayment = new SalaryPayment();
                            $salaryPayment->setMonth((int)$date->format('m'));
                            $salaryPayment->setYear((int)$date->format('Y'));
                            $salaryPayment->setAddDate($date);
                            $salaryPayment->setAmount((float)$amount);
                            $salaryPayment->setSalary($salaryLied);
                            $salaryPayment->setEmployee($employee);
                            $entityManager->persist($salaryPayment);
                            $entityManager->flush();
                        }

                        $entityManager->flush();
                    }
                }
            }
        }

        return $this->redirectToRoute('home');


    }

    /**
     * @Route("/test/stock", name="test_stock", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param SupplierRepository $supplierRepository
     * @param ProductRepository $productRepository
     * @param ProductService $productService
     * @return Response
     * @throws Exception
     */
    public function testStock(EntityManagerInterface $entityManager,
                             UserRepository $userRepository,
                              SupplierRepository $supplierRepository,
                             ProductRepository $productRepository,
                             ProductService $productService): Response
    {
        $faker = Factory::create();

        $suppliers= $supplierRepository->findAll();
        $admins= $userRepository->findUserByRole('ROLE_ADMIN');
        $managers= $userRepository->findUserByRole('ROLE_MANAGER');
        $users = array_merge($admins,$managers);
        $stock = new Stock();
        $stock->setRecorder($users[random_int(0,count($users)-1)]);
        $stock->setStatus(true);
        $stock->setAddDate(new \DateTime('2022-10-31'));
        $stock->setDeliveryDate(new \DateTime('2022-11-01'));
        $stock->setSupplier($suppliers[random_int(0,count($suppliers)-1)]);
        $stock->setNumInvoice(str_pad(random_int(10,4587321536),10,'0'));
        $entityManager->persist($stock);

        //$faker = Factory::create();
        $productsRecovers = [];
        $products = $productService
            ->getProductByStockAlert($productRepository->findAll());

        $maxProducts = count($products);
        $nbProductStocks = random_int(15,30);

        if ($maxProducts <= $nbProductStocks){
            $nbProductStocks = $maxProducts-1;
        }

        $amount = 0;
        for ($i=1;$i < $nbProductStocks;$i++){
            $product = $products[random_int(0,$maxProducts-1)];

            while(!in_array($product->getId(),$productsRecovers,true)){
                $productStock = new ProductStock();
                $productStock->setQty(random_int(100,200));
                $purchasePrice = (random_int(0,1))? $product->getBuyPrice()
                    :$product->getBuyPrice()+random_int(-100,0);

                $buyPrice = ($purchasePrice <= 0)?$product->getBuyPrice():$purchasePrice;

                $productStock->setUnitPrice($buyPrice);
                $productStock->setStock($stock);
                $productStock->setProduct($product);
                if ((random_int(0,1))){
                    $productStock->setExpirationDate($faker->dateTimeBetween('+180 days','+360 days'));
                }

                $entityManager->persist($productStock);
                $productsRecovers[] = $product->getId();
                $amount += $productStock->getSubtotal();
            }

        }

        $stock->setAmount($amount);
        $entityManager->persist($stock);

        $entityManager->flush();

        return $this->redirectToRoute('home');


    }

    /**
     * @Route("/test/sale", name="test_sale", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param ProductService $productService
     * @param CartService $cartService
     * @param UserRepository $userRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param ProductRepository $productRepository
     * @return Response
     * @throws Exception
     **/
    public function testSaleAdd(EntityManagerInterface $entityManager,
                             ProductService $productService,
                             CartService $cartService,
                             UserRepository $userRepository,
                             PaymentMethodRepository $paymentMethodRepository,
                             ProductRepository $productRepository): Response
    {
        $faker = Factory::create();
        $products = $productRepository->findAll();
        $paymentMethods = $paymentMethodRepository->findAll();
        $cashiers= $userRepository->findUserByRole('ROLE_CASHIER');
        $nbSales = random_int(2000,2500);
        for ($i=0;$i < $nbSales;$i++){
            // clear a cart
            $cartService->removeAll();
            $productDispos = $productService->getProductByStockNotFinish($products);

            $maxProducts = count($productDispos);
            $nbProductSales = random_int(1,3);

            if ($maxProducts <= $nbProductSales){
                $nbProductSales = $maxProducts-1;
            }
            do{
                $productsRecovers = [];
                for ($j=0;$j < $nbProductSales;$j++){
                    $product = $productDispos[random_int(0,$maxProducts-1)];

                    if(!in_array($product->getId(),$productsRecovers,true)){
                        $qtySelected = random_int(1,2);
                        if ($qtySelected >= $product->getStock()){
                            $qtySelected = $product->getStock();
                        }
                        $cartService->changeQty($product->getId(),$qtySelected);
                        $productsRecovers[] = $product->getId();
                    }
                }
            }while(empty($productsRecovers));

            $itemProfit = $cartService->getItemProfit();

            $sale = new Sale();
            $sale->setAmount($cartService->getTotalWithTax());
            $sale->setTaxAmount($cartService->getTotalTax());
            if (random_int(0,1)){
                $sale->setAmountReceived($cartService->getTotalWithTax());
            }else{
                $rest = $cartService->getTotalWithTax() % 500;
                $amountBefore = $cartService->getTotalWithTax() - $rest;

                if (($amountBefore / 500) <= 1){
                    $amountReceived = 500;
                }else{
                    $amountReceived = $amountBefore + (random_int(1,5)*500);
                }
                $sale->setAmountReceived($amountReceived);
            }

            $sale->setRecorder($cashiers[random_int(0,count($cashiers)-1)]);
            $sale->setPaymentMethod($paymentMethods[random_int(0,count($paymentMethods)-1)]);
            $sale->setAddDate($faker->dateTimeBetween('2022-12-01','2022-12-31'));


            $cartProducts = $cartService->getFullCart();
            $totalProfit = 0;
            foreach ($cartProducts as $item){
                $productStockSales = $cartService->getProductStocks($item);

                $productSale = new ProductSale();
                $productSale->setProduct($item['product']);
                $productSale->setQty($item['qty']);
                $productSale->setSale($sale);
                foreach ($productStockSales as $productStockSale){
                    $entityManager->persist($productStockSale);
                    $productSale->addProductStockSale($productStockSale);
                }
                $productSale->setProfit($itemProfit[$item['product']->getId()]);
                $productSale->setUnitPrice($item['product']->getSellPrice());
                $entityManager->persist($productSale);

                $totalProfit += $productSale->getProfit();
            }

            $sale->setProfit($totalProfit);
            $entityManager->persist($sale);
            $entityManager->flush();
        }

        return $this->redirectToRoute('home');


    }

    /**
     * @Route("/test/sale/amount", name="test_sale_amount", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param SaleRepository $saleRepository
     * @return Response
     * @throws Exception
     **/
    public function testSaleUpdate(EntityManagerInterface $entityManager,
                             SaleRepository $saleRepository): Response
    {
        $sales = $saleRepository->getAmountToUpdate();

        foreach ($sales as $sale){
            if (random_int(0,1)){
                $sale->setAmountReceived($sale->getAmount());
            }else{
                $rest = $sale->getAmount() % 500;
                $amountBefore = $sale->getAmount() - $rest;

                if (($amountBefore / 500) <= 1){
                    $amountReceived = 500;
                }else{
                    $amountReceived = $amountBefore + (random_int(1,5)*500);
                }
                $sale->setAmountReceived($amountReceived);
            }

            $entityManager->persist($sale);
        }

        $entityManager->flush();


        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/sale/adjust/unsettled", name="test_sale_adjust_unsettled", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param CustomerRepository $customerRepository
     * @param SaleRepository $saleRepository
     * @return Response
     * @throws Exception
     */
    public function testSaleAdjustUnsettled(EntityManagerInterface $entityManager,
                                   CustomerRepository $customerRepository,
                                   SaleRepository $saleRepository): Response
    {
        $sales = $saleRepository
            ->findSaleByPeriod('2018-01-01 00:00','2022-12-31 23:59');

        $saleUnsettled = array_filter($sales,static function(Sale $sale){
            return $sale->getAmountDebt() > 0;
        });
        $customers = $customerRepository->findAll();

        foreach ($saleUnsettled as $sale){
            $sale->setCustomer($customers[random_int(0,count($customers)-1)]);
            $entityManager->persist($sale);
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/sale/settled", name="test_sale_settled", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param UserRepository $userRepository
     * @param SaleRepository $saleRepository
     * @return Response
     * @throws Exception
     */
    public function testSaleSettled(EntityManagerInterface $entityManager,
                                            PaymentMethodRepository $paymentMethodRepository,
                                            UserRepository $userRepository,
                                            SaleRepository $saleRepository): Response
    {

        $sales = $saleRepository
            ->findSaleByPeriod('2018-01-01 00:00','2022-12-31 23:59');

        $saleUnsettled = array_filter($sales,static function(Sale $sale){
            return $sale->getAmountDebt() > 0;
        });

        $paymentMethods = $paymentMethodRepository->findAll();
        $recorders= $userRepository->findAll();

        $faker = Factory::create();
        foreach ($saleUnsettled as $sale){

           $amountDebt = $sale->getAmountDebt();
           if ($amountDebt > 1000){
               $nb_salePayments = random_int(1,3);

               $rate = 0;
               for ($i=1;$i <= $nb_salePayments;$i++){
                   $rateSelected = random_int(1,100);

                   if ($i === $nb_salePayments)
                       $rateSelected = 100 - $rate;

                   $amount = $amountDebt * $rateSelected / 100;

                   $addDate = $faker->dateTimeBetween($sale->getAddDate(),
                       $faker->dateTimeInInterval($sale->getAddDate(),'+5 days'));
                   $paymentMethod = $paymentMethods[random_int(0,count($paymentMethods)-1)];
                   $recorder = $recorders[random_int(0,count($recorders)-1)];
                   $salePayment = new SalePayment();
                   $salePayment->setAddDate($addDate);
                   $salePayment->setAmount((int) $amount);
                   $salePayment->setPaymentMethod($paymentMethod);
                   $salePayment->setRecorder($recorder);
                   $salePayment->setSale($sale);
                   $entityManager->persist($salePayment);

                   $rate += $rateSelected;
                   if ($rate >= 100)
                       break;

               }

           }else{
               $addDate = $faker->dateTimeBetween($sale->getAddDate(),
                   $faker->dateTimeInInterval($sale->getAddDate(),'+5 days'));
               $paymentMethod = $paymentMethods[random_int(0,count($paymentMethods)-1)];
               $recorder = $recorders[random_int(0,count($recorders)-1)];
               $salePayment = new SalePayment();
               $salePayment->setAddDate($addDate);
               $salePayment->setAmount($amountDebt);
               $salePayment->setPaymentMethod($paymentMethod);
               $salePayment->setRecorder($recorder);
               $salePayment->setSale($sale);
               $entityManager->persist($salePayment);
           }
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }


    /**
     * @Route("/test/salePayment/delete", name="test_salePayment_delete", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param CustomerRepository $customerRepository
     * @param SalePaymentRepository $salePaymentRepository
     * @return Response
     * @throws Exception
     */
    public function testSalePaymentDelete(EntityManagerInterface $entityManager,
                                    CustomerRepository $customerRepository,
                                    SalePaymentRepository $salePaymentRepository): Response
    {
        $faker = Factory::create();

        $customers = $customerRepository->findAll();
        foreach ($customers as $customer){
            $number = random_int(1,100);
            if ($number >80){
                $salePayments = $salePaymentRepository->findByCustomer($customer);
                foreach ($salePayments as $salePayment){
                    if ($faker->boolean){
                        $entityManager->remove($salePayment);
                    }
                }
            }
        }


        $entityManager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/expense", name="test_expense", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param ExpenseTypeRepository $expenseTypeRepository
     * @param PaymentMethodRepository $paymentMethodRepository
     * @return Response
     * @throws Exception
     */
    public function testExpense(EntityManagerInterface $entityManager,
                                ExpenseTypeRepository $expenseTypeRepository,
                                PaymentMethodRepository $paymentMethodRepository): Response
    {
        $faker = Factory::create();
        $amounts = [5000,10000,12000,15000,20000,25000];
        $titles = ["achat de tenues","Reparation d'ordinateurs",
            'Nettoyage des meubles','reaménagement de la entreprise',
            'Réparation de la porte de sortie', "paie de l'électricité",
            "réparation du compteur"];
        $paymentMethods = $paymentMethodRepository->findAll();
        $expenseTypes = $expenseTypeRepository->findAll();
        for ($i=0;$i < random_int(2,5);$i++){
            $expense = new Expense();
            $expense->setDescription($faker->sentence(random_int(6,10)));
            $expense->setAmount($amounts[random_int(0,count($amounts)-1)]);
            $expense->setName($titles[random_int(0,count($titles)-1)]);
            $expense->setType($expenseTypes[random_int(0,count($expenseTypes)-1)]);
            $expense->setPaymentMethod($paymentMethods[random_int(0,count($paymentMethods)-1)]);
            $expense->setDate($faker->dateTimeBetween('2022-01-01','2022-01-31'));

            $entityManager->persist($expense);
        }

        $entityManager->flush();


        return $this->redirectToRoute('home');


    }

    /**
     * @Route("/test/sale/date", name="test_sale_date", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param SaleRepository $saleRepository
     * @return Response
     * @throws Exception
     */
    public function testSaleDate(EntityManagerInterface $entityManager,
                                   SaleRepository $saleRepository): Response
    {
        $sales = $saleRepository->findSaleByMethodPeriod('2017-01-01 00:00','2021-12-31 23:59');

        foreach ($sales as $sale){
            $year= $sale->getAddDate()->format('Y');
            $month= $sale->getAddDate()->format('m');
            $day= $sale->getAddDate()->format('d');
            $hour= $sale->getAddDate()->format('H');
            $min= $sale->getAddDate()->format('i');
            $second= $sale->getAddDate()->format('s');

            $date = new \DateTime(((int) $year + 1).'-'.$month.'-'.$day.' '.$hour.':'.$min.':'.$second);

            $sale->setAddDate($date);

            $entityManager->persist($sale);
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/stock/date", name="test_stock_date", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @param StockRepository $stockRepository
     * @return Response
     * @throws Exception
     */
    public function testStockDate(EntityManagerInterface $entityManager,
                                 StockRepository $stockRepository): Response
    {
        $stocks = $stockRepository->findStockByPeriod('2017-01-01 00:00','2021-12-31 23:59');

        foreach ($stocks as $stock){
            $year= $stock->getAddDate()->format('Y');
            $month= $stock->getAddDate()->format('m');
            $day= $stock->getAddDate()->format('d');
            $hour= $stock->getAddDate()->format('H');
            $min= $stock->getAddDate()->format('i');
            $second= $stock->getAddDate()->format('s');


            $dyear= $stock->getDeliveryDate()->format('Y');
            $dmonth= $stock->getDeliveryDate()->format('m');
            $dday= $stock->getDeliveryDate()->format('d');
            $dhour= $stock->getDeliveryDate()->format('H');
            $dmin= $stock->getDeliveryDate()->format('i');
            $dsecond= $stock->getDeliveryDate()->format('s');

            $date = new \DateTime(((int) $year + 1).'-'.$month.'-'.$day.' '.$hour.':'.$min.':'.$second);
            $ddate = new \DateTime(((int) $dyear + 1).'-'.$dmonth.'-'.$dday.' '.$dhour.':'.$dmin.':'.$dsecond);

            $stock->setAddDate($date);
            $stock->setDeliveryDate($ddate);

            $entityManager->persist($stock);
        }

        $entityManager->flush();


        return $this->redirectToRoute('home');


    }

    /**
     * @Route("/test/permission/update", name="test_permission_update", methods={"POST","GET"})
     * @param PermissionRepository $permissionRepository
     * @param RouterInterface $router
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function testPermissionUpdate(PermissionRepository $permissionRepository,
                                         RouterInterface $router,
                                         EntityManagerInterface $entityManager): Response
    {

        $permisions = array_map(static function(Permission $permission){
            return strtoupper($permission->getCode());
        },$permissionRepository->findAll());

        foreach ($this->getPermissions($router) as $permissionName){
            if (!in_array(strtoupper($permissionName), $permisions, true)){
                $permission = new Permission();
                $permission->setCode(strtoupper($permissionName));
                $permission->setAddDate(new DateTime());
                $entityManager->persist($permission);
            }
        }

        $entityManager->flush();
        return $this->redirectToRoute('home');

    }

    /**
     * @Route("/test/permission/adjust", name="test_permission_adjust", methods={"POST","GET"})
     * @param PermissionRepository $permissionRepository
     * @param RouterInterface $router
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function testPermissionAdjust(PermissionRepository $permissionRepository,
                                         RouterInterface $router,
                                         EntityManagerInterface $entityManager): Response
    {

        $permisions = array_map(static function(Permission $permission){
            return strtoupper($permission->getCode());
        },$permissionRepository->findAll());

        $routeNames = $this->getPermissions($router);
        foreach ($permisions as $permissionName){
            if (!in_array(strtolower($permissionName), $routeNames, true)){
                $permission = $permissionRepository->findOneBy(['code' => strtoupper($permissionName)]);
                $entityManager->remove($permission);
            }
        }

        $entityManager->flush();
        return $this->redirectToRoute('home');

    }


    /**
     * @Route("/test/theme/install", name="test_theme_install", methods={"POST","GET"})
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function testThemeInstal(EntityManagerInterface $entityManager): Response
    {

         $themes = array(
            array(
                "backcolor_side_menu" => "#000000",
                "color_side_menu_link" => "#000000",
                "general_color_light" => "#000000",
                "general_color_dark" => "#000000",
                "deletable" => true,
            ),
            array(
                "backcolor_side_menu" => "#004C41",
                "color_side_menu_link" => "#004C41",
                "general_color_light" => "#009577",
                "general_color_dark" => "#006552",
                "deletable" => false,
            ),
            array(
                "backcolor_side_menu" => "#000000",
                "color_side_menu_link" => "#000000",
                "general_color_light" => "#059181",
                "general_color_dark" => "#004C41",
                "deletable" => false,
            ),
            array(
                "id" => 8,
                "backcolor_side_menu" => "#0E2751",
                "color_side_menu_link" => "#0E2751",
                "general_color_light" => "#0855DA",
                "general_color_dark" => "#0B5C8A",
                "deletable" => false,
            ),
            array(
                "backcolor_side_menu" => "#000000",
                "color_side_menu_link" => "#000000",
                "general_color_light" => "#147F9A",
                "general_color_dark" => "#033949",
                "deletable" => false,
            ),
            array(
                "backcolor_side_menu" => "#014236",
                "color_side_menu_link" => "#014236",
                "general_color_light" => "#19AC5C",
                "general_color_dark" => "#215832",
                "deletable" => false,
            ),
            array(
                "backcolor_side_menu" => "#1c1f22",
                "color_side_menu_link" => "#1c1f22",
                "general_color_light" => "#339798",
                "general_color_dark" => "#204F6F",
                "deletable" => false,
            ),
            array(
                "backcolor_side_menu" => "#1c1f22",
                "color_side_menu_link" => "#1c1f22",
                "general_color_light" => "#41bdb5",
                "general_color_dark" => "#007c74",
                "deletable" => false,
            ),
            array(
                "backcolor_side_menu" => "#715CC6",
                "color_side_menu_link" => "#715CC6",
                "general_color_light" => "#7BA8F5",
                "general_color_dark" => "#6149C4",
                "deletable" => true,
            ),
        );


        foreach ($themes as $theme){
            $themeAdd = new Theme();
            $themeAdd->setBackcolorSideMenu($theme['backcolor_side_menu']);
            $themeAdd->setColorSideMenuLink($theme['color_side_menu_link']);
            $themeAdd->setGeneralColorDark($theme['general_color_dark']);
            $themeAdd->setGeneralColorLight($theme['general_color_light']);
            $themeAdd->setDeletable($theme['deletable']);

            $entityManager->persist($themeAdd);
        }

        $entityManager->flush();
        return $this->redirectToRoute('home');

    }

    private function getPermissions(RouterInterface $router): array
    {
        $subPaths = [
            'imageLogo',
            'titleStore',
            'linkStore',
            'notice',
            'nbProduct',
            'account_login',
            'documentation',
            'installation',
        ];

        $allRoutes = $router->getRouteCollection()->all();

        $routeNames = [];
        foreach ($allRoutes as $key=>$value){
            if (!str_contains($key,'profiler') && !str_starts_with($key,'rest')
                && !str_starts_with($key,'test') && !str_starts_with($key,'_')
                && !in_array($key,$subPaths,true))
                $routeNames[] = $key;
        }

        return $routeNames;
    }

    /**
     * @param DateTime $date
     * @param int $nb
     * @param string $period
     * @return DateTime
     * @throws Exception
     */
    private function dateConvert(DateTime $date, $nb = 1, $period='day'){

        $year= (int) $date->format('Y');
        $month= (int) $date->format('m');
        $day= (int) $date->format('d');
        $hour= (int) $date->format('H');
        $min= (int) $date->format('i');
        $second= (int) $date->format('s');

        switch ($period){
            case 'year':
                $year= ((int)$date->format('Y')) + $nb;
                break;
            case 'month':
                $month= ((int)$date->format('m')) + $nb;
                $month= str_pad($month,2,'0',STR_PAD_LEFT);
                break;
            case 'day':
                $day= ((int)$date->format('d')) + $nb;
                $day= str_pad($day,2,'0',STR_PAD_LEFT);
                break;
            case 'hour':
                $hour= ((int)$date->format('H')) + $nb;
                $hour= str_pad($hour,2,'0',STR_PAD_LEFT);
                break;
            case 'min':
                $min= ((int)$date->format('i')) + $nb;
                $min= str_pad($min,2,'0',STR_PAD_LEFT);
                break;
            case 'sec':
                $second= ((int)$date->format('s')) + $nb;
                $second= str_pad($second,2,'0',STR_PAD_LEFT);
                break;
            default:
                $day= ((int)$date->format('d')) + $nb;
        }

        return new \DateTime($year.'-'.$month.'-'.$day.' '.$hour.':'.$min.':'.$second);

    }


    /**
     * @Route("/test/loss/add", name="test_loss_add", methods={"POST","GET"})
     * @param UserRepository $userRepository
     * @param LossTypeRepository $lossTypeRepository
     * @param ProductStockSaleRepository $productStockSaleRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function testLossAdd(UserRepository $userRepository,
                                LossTypeRepository $lossTypeRepository,
                                ProductStockSaleRepository $productStockSaleRepository,
                                EntityManagerInterface $entityManager): Response
    {
        $faker = Factory::create();
        $productStockSales = $productStockSaleRepository->findAll();
        $lossTypes = $lossTypeRepository->findAll();
        $users = $userRepository->findEmployees();
        $managers = $userRepository->findUserByRole('ROLE_MANAGER');

        foreach ($productStockSales as $productStockSale){
            $percent = random_int(1,100000);

            if ($percent >= 99900){
                $sale = $productStockSale->getProductSale()->getSale();
                $productSaleReturn = new ProductSaleReturn();
                $productSaleReturn->setQty($productStockSale->getQty());
                $productSaleReturn->setStockable($faker->boolean);
                $productSaleReturn->setRepay($faker->boolean);
                $productSaleReturn->setProductStockSale($productStockSale);
                $productSaleReturn->setDate($faker->dateTimeInInterval($sale->getAddDate(),'+10 days'));
                $productSaleReturn->setRecorder($users[random_int(0,count($users)-1)]);

                $entityManager->persist($productSaleReturn);

                if ($productSaleReturn->getStockable()){
                    $loss = new Loss();
                    $loss->setRecorder($managers[0]);
                    $loss->setAddDate($productSaleReturn->getDate());
                    $loss->setQty($productSaleReturn->getQty());
                    $loss->setType($lossTypes[random_int(0,count($lossTypes)-1)]);
                    $loss->setProductStock($productStockSale->getProductStock());

                    $entityManager->persist($loss);
                }
            }
        }

        $entityManager->flush();

        return $this->redirectToRoute('home');

    }


}
