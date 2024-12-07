<?php

namespace App\Controller;

use App\Entity\ECModule;
use App\Entity\PourcentageECModule;
use App\Form\PourcentageECModuleType;
use App\Repository\PourcentageECModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/pourcentage/ec/module")
 */
class PourcentageECModuleController extends AbstractController
{

    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @Route("/", name="pourcentage_e_c_module_index", methods={"GET"})
     */
    public function index(PourcentageECModuleRepository $pourcentageECModuleRepository): Response
    {
        return $this->render('pourcentage_ec_module/index.html.twig', [
            'pourcentage_e_c_modules' => $pourcentageECModuleRepository->findAll(),
        ]);
    }

    /**
     * @Route("/{id}", name="pourcentage_e_c_module_config", methods={"GET"})
     */
    public function configPercent(ECModule $eCModule): Response
    {
        if ($eCModule->getPourcentageECModule() === null) {
            return $this->redirectToRoute('pourcentage_e_c_module_new', ['id' => $eCModule->getId()]);
        }

        return $this->redirectToRoute('pourcentage_e_c_module_edit', ['id' => $eCModule->getPourcentageECModule()->getId()]);
    }

    /**
     * @Route("/new/{id}", name="pourcentage_e_c_module_new", methods={"GET","POST"})
     */
    public function new(ECModule $eCModule, Request $request): Response
    {
        $pourcentageECModule = new PourcentageECModule();
        $form = $this->createForm(PourcentageECModuleType::class, $pourcentageECModule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pourcentageECModule->setEcModule($eCModule);
            $this->entityManagerInterface->persist($pourcentageECModule);
            $this->entityManagerInterface->flush();

            return $this->redirectToRoute('PA_classe', ['annee_slug' => $eCModule->getModule()->getAnneeAcademique()->getSlug(), 'slug' => $eCModule->getModule()->getClasse()->getSlug()]);
        }

        return $this->render('pourcentage_ec_module/new.html.twig', [
            'pourcentage_e_c_module' => $pourcentageECModule,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="pourcentage_e_c_module_show", methods={"GET"})
     */
    public function show(PourcentageECModule $pourcentageECModule): Response
    {
        return $this->render('pourcentage_ec_module/show.html.twig', [
            'pourcentage_e_c_module' => $pourcentageECModule,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="pourcentage_e_c_module_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, PourcentageECModule $pourcentageECModule): Response
    {
        $form = $this->createForm(PourcentageECModuleType::class, $pourcentageECModule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManagerInterface->flush();

            $eCModule = $pourcentageECModule->getEcModule();
            return $this->redirectToRoute('PA_classe', ['annee_slug' => $eCModule->getModule()->getAnneeAcademique()->getSlug(), 'slug' => $eCModule->getModule()->getClasse()->getSlug()]);
        }

        return $this->render('pourcentage_ec_module/edit.html.twig', [
            'pourcentage_e_c_module' => $pourcentageECModule,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="pourcentage_e_c_module_delete", methods={"DELETE"})
     */
    public function delete(Request $request, PourcentageECModule $pourcentageECModule): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pourcentageECModule->getId(), $request->request->get('_token'))) {
            
            $this->entityManagerInterface->remove($pourcentageECModule);
            $this->entityManagerInterface->flush();
        }

        return $this->redirectToRoute('pourcentage_e_c_module_index');
    }
}
