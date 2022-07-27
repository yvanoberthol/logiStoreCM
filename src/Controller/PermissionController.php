<?php


namespace App\Controller;


use App\Entity\LossType;
use App\Entity\Setting;
use App\Form\LossTypeType;
use App\Repository\LossTypeRepository;
use App\Repository\PermissionRepository;
use App\Repository\RoleRepository;
use App\Repository\StoreRepository;
use App\Util\RandomUtil;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class PermissionController extends AbstractController
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
     * @Route("/permission", name="permission_index")
     * @param PermissionRepository $permissionRepository
     * @param RoleRepository $roleRepository
     * @return Response
     */
    public function index(PermissionRepository $permissionRepository,
                          RoleRepository $roleRepository): Response
    {
        $model['permissions'] = $permissionRepository->findBy([],['code' => 'DESC']);
        $model['roles'] = $roleRepository->findBy([],['name' => 'DESC']);
        //breadcumb
        $model['entity'] = 'controller.permission.index.entity';
        $model['page'] = 'controller.permission.index.page';
        return $this->render('permission/index.html.twig', $model);
    }

    /**
     * @Route("/permission/pdf", name="permission_pdf")
     * @param PermissionRepository $permissionRepository
     * @param RoleRepository $roleRepository
     * @param Pdf $pdf
     * @param StoreRepository $storeRepository
     * @return Response
     * @throws \Exception
     */
    public function print(PermissionRepository $permissionRepository,
                          RoleRepository $roleRepository,
                          Pdf $pdf,
                          StoreRepository $storeRepository): Response
    {
        $model['permissions'] = $permissionRepository->findBy([],['code' => 'DESC']);
        $model['roles'] = $roleRepository->findBy([],['name' => 'DESC']);

        $model['store'] = null;
        if (!empty($storeRepository->get())){
            $model['store'] = $storeRepository->get();
        }

        $html = $this->renderView('pdf/permission.html.twig',$model);

        $pdf->setOption('enable-local-file-access', true);
        $pdf->setOption('page-height', $this->setting->getReportHeight());
        $pdf->setOption('page-width', $this->setting->getReportWidth());
        $file = $pdf->getOutputFromHtml($html);
        $filename = RandomUtil::randomString($this->setting->getRandomCharacter()).".pdf";
        return new PdfResponse(
            $file,
            $filename,
            'application/pdf',
            'inline'
        );
    }

}
