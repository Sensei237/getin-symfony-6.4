<?php

namespace App\Controller;

use App\Entity\AnneeAcademique;
use App\Form\ConfigurationType;
use App\Repository\ECRepository;
use App\Repository\ModuleRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Controller permet de configurer le logiciel
 * @author : MBOZO'O METOU'OU Emmanuel Beranger Alias Sensei emmaberanger2@gmail.com
 */
class ConfigurationController extends AbstractController
{
    private $link = 'config';

    public function __construct()
    {
        
    }
    /**
     * @Route("/0a2e757f01680d2cd2abce171a663a72/{slugAnnee}", name="configuration")
     * 
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     */
    public function index(AnneeAcademique $annee, Request $request, EntityManagerInterface $manager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $config = $annee->getConfiguration();
        $form = $this->createForm(ConfigurationType::class, $config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($config->logo1) {
                $filename = md5(uniqid()).'_'.time().'.'.$config->logo1->guessExtension();
                try {
                    $config->logo1->move($this->getParameter('images_directory'), $filename);
                } catch (FileException $e) {
                    die($e->getMessage());
                }
                $config->setLogo($filename);
            }
            if ($config->logo2) {
                $filename = md5(uniqid()).'_'.time().'.'.$config->logo2->guessExtension();
                try {
                    $config->logo2->move($this->getParameter('images_directory'), $filename);
                } catch (FileException $e) {
                    die($e->getMessage());
                }
                $config->setLogoUniversity($filename);
            }
            $manager->flush();
            $this->addFlash("success", "Les modifications apportées ont été prises en compte !");
            return $this->redirectToRoute('configuration', ['slugAnnee'=>$annee->getSlug()]);
        }
        return $this->render('configuration/index.html.twig', [
            'form' => $form->createView(),
            'btn_title' => "Enregistrer les modifications",
            'li' => $this->link,
        ]);
    }

    /**
     * @Route("/rewrite-slug")
     */
    public function rewriteSlug(EntityManagerInterface $objectManager, ECRepository $eCRepository, ModuleRepository $moduleRepository)
    {
        foreach ($eCRepository->findAll() as $ec) {
            $ec->setSlug($ec->getSlug());
        }
        foreach ($moduleRepository->findAll() as $m) {
            $m->setSlug($m->getSlug());
        }
        $objectManager->flush();
        return $this->redirectToRoute('home');
    }
}
