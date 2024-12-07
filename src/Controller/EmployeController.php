<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Form\EmployeType;
use App\Repository\EmployeRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Contient toutes les actions permettant la gestion des employés
 * @author : MBOZO'O METOU'OU Emmanuel Beranger Alias Sensei emmaberanger2@gmail.com
 */
class EmployeController extends AbstractController
{
    private $li = 'pers';
    

    /**
     * @Route("/gestion-personnel", name="employe")
     */
    public function index(EmployeRepository $employeRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('employe/index.html.twig', [
            'employes' => $employeRepository->findBy([], ['nom'=>'ASC', 'prenom'=>'ASC']),
            'li' => $this->li,

        ]);
    }

    /**
     * @Route("/gestion-personnel/ajouter", name="employe_edit")
     * @Route("/gestions-personnel/modifier/{reference}", name="employe_modifer")
     */
    public function edit(?Employe $employe=null, Request $request, EntityManagerInterface $objectManager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $isNew = false;
        $btn_title = "Enregistrer les modifications";
        if (!$employe) {
            $employe = new Employe();
            $isNew = true;
            $btn_title = "Ajouter l'employe";
        }

        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($employe->image) {
                $filename = md5(uniqid()).'_'.time();
                try {
                    $employe->image->move($this->getParameter('server_upload_image_path'), $filename);
                } catch (FileException $e) {
                    die($e->getMessage());
                }
                $employe->setPhoto($filename);
            }

            if ($isNew) {
                $objectManager->persist($employe);
            }
            $objectManager->flush();

            return $this->redirectToRoute('employe');
        }

        return $this->render('employe/edit.html.twig', [
            'form' => $form->createView(),
            'li' => $this->li,
            'editMode' => $isNew,
            'btn_title' => $btn_title,
        ]);
    }

    /**
     * @Route("/gestion-personnel/delete/{reference}", name="employe_delete")
     */
    public function deleteEmploye(Employe $employe, EntityManagerInterface $objectManager, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($employe->getUser()) {
            if ($employe->getUser()->getMatiereASaisirs()) {
                foreach ($employe->getUser()->getMatiereASaisirs() as $ms) {
                    $objectManager->remove($ms);
                }
            }
            $objectManager->remove($employe->getUser());
        }

        $objectManager->remove($employe);
        $objectManager->flush();

        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>false, 'msg'=>"Suppression terminée ! La page va être rechargée.", 'reloadWindow'=>true]), '200', ['Content-type'=>'appplication/json']);
        }

        $this->addFlash("success", "Suppression terminée !");
        return $this->redirectToRoute('employe');
    }

    /**
     * @Route("gestions-personnel/profil/{reference}", name="employe_profile")
     */
    public function showEmployeProfile(Employe $employe, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('employe/profile.html.twig', [
            'employe' => $employe,
            'annee' => $request->getSession()->get('annee'),
            'li' => $this->li,

        ]);
    }
}
