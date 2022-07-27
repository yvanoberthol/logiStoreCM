<?php


namespace App\Controller;


use App\Entity\Loss;
use App\Entity\Setting;
use App\Repository\ProductRepository;
use App\Repository\LossRepository;
use App\Repository\ProductStockRepository;
use App\Repository\LossTypeRepository;
use App\Service\ProductService;
use App\Util\GlobalConstant;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class LossController extends AbstractController
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

    /**
     * @Route("/loss", name="loss_index", methods={"GET","POST"})
     * @param LossRepository $lossRepository
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(LossRepository $lossRepository, Request $request): Response
    {
        //$model = GlobalConstant::getMonthsAndYear($request);

        $intervalDays = $this->setting->getMaxIntervalPeriod();

        $model['start'] = $request->get('start') ?? new DateTime();
        $model['end'] = $request->get('end') ?? new DateTime();

        if (!GlobalConstant::compareDate($model['start'],$model['end'])){
            $model['end'] = $model['start'];
        }

        if (GlobalConstant::getInterval($model['start'],$model['end']) > $intervalDays){
            $model['start'] = new DateTime();
            $model['end'] = new DateTime();
            $this->addFlash('danger',"controller.sale.index.flash.danger");
        }

        if (!$model['start'] instanceof DateTime && !$model['end'] instanceof DateTime){
            $model['start'] = new DateTime($model['start']);
            $model['end'] = new DateTime($model['end']);
        }

        $model['losses'] = $lossRepository
            ->findLossByPeriod($model['start'],$model['end']);
        //breadcumb
        $model['entity'] = 'controller.loss.index.entity';
        $model['page'] = 'controller.loss.index.page';
        return $this->render('loss/index.html.twig', $model);
    }

    /**
     * @Route("/loss/new", name="loss_new")
     * @param Request $request
     * @param ProductStockRepository $productStockRepository
     * @param ProductRepository $productRepository
     * @param ProductService $productService
     * @param LossTypeRepository $lossTypeRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws Exception
     */
    public function new(Request $request,
                        ProductStockRepository $productStockRepository,
                        ProductRepository $productRepository,
                        ProductService $productService,
                        LossTypeRepository $lossTypeRepository,
                        EntityManagerInterface $entityManager): Response
    {
        $loss = new Loss();

        if ($request->getMethod() === 'POST'){
            if (isset($_POST['product']) && $_POST['product'] !== null){
                $requestTab = explode('_',$request->get('productStock'));
                $productStock = $productStockRepository->find($requestTab[0]);

                $qtyLoss = abs((int)$request->get('qty'));
                if ($requestTab[1] >= $qtyLoss){
                    if (isset($_POST['type']) && $_POST['type'] !== null){
                        $type = $lossTypeRepository->find($request->get('type'));
                        $addDate = new DateTime($request->get('date'))??new DateTime();
                        $loss->setAddDate($addDate);
                        $loss->setProductStock($productStock);
                        $loss->setQty($qtyLoss);
                        $loss->setType($type);
                        $loss->setRecorder($this->getUser());
                        $entityManager->persist($loss);
                        $entityManager->flush();

                        $this->addFlash('success',"controller.loss.new.flash.success");
                    }else{
                        $this->addFlash('danger',"controller.loss.new.flash.danger.lossType");
                    }
                }else{
                    $this->addFlash('danger',"controller.loss.new.flash.danger.qtyEleve");
                }
            }else{
                $this->addFlash('danger',"controller.loss.new.flash.danger.product");
            }
        }

        $products = $productRepository->findAll();
        $model['products'] = $productService->getProductByStockNotFinish($products);
        $model['types'] = $lossTypeRepository->findAll();

        //breadcumb
        $model['entity'] = 'controller.loss.new.entity';
        $model['page'] = 'controller.loss.new.page';
        return $this->render('loss/new.html.twig',$model);
    }

    /**
     * @Route("/loss/delete/{id}", name="loss_delete")
     * @param Loss $loss
     * @param EntityManagerInterface $entityManager
     * @return RedirectResponse
     */
    public function delete(Loss $loss, EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->denyAccessUnlessGranted('LOSS_DELETE',$loss);
        $entityManager->remove($loss);
        $entityManager->flush();
        $this->addFlash('success',"controller.loss.delete.flash.success");
        return $this->redirectToRoute('loss_index');
    }
}
