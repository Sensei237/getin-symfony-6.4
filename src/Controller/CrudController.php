<?php

namespace App\Controller;

use App\Entity\Classe;

use App\Entity\Filiere;
use App\Entity\Service;

// les entites ou models
use App\Form\ClasseType;
use App\Entity\Formation;
use App\Form\FiliereType;
use App\Form\ServiceType;
use App\Entity\Specialite;
use App\Form\FormationType;

// les formulaires
use App\Form\SpecialiteType;
use App\Repository\ClasseRepository;
use App\Repository\ExamenRepository;
use App\Repository\FiliereRepository;
use App\Repository\FormationRepository;
use App\Repository\ServiceRepository;
use App\Repository\SpecialiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Toutes les actions du menu creation sont repertoriées ici
 * @author : MBOZO'O METOU'OU Emmanuel Beranger Alias Sensei emmaberanger2@gmail.com
 */
class CrudController extends AbstractController
{
    private $link = 'crud';
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @Route("/creation", name="creation")
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('crud/index.html.twig', ['li' => $this->link,]);
    }

    /**
     * Cette action permet d'afficher les filieres
     * @Route("/creation/filieres/afficher", name="filieres_home")
     */
    public function showFilieres(FiliereRepository $filiereRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('crud/filiere/index.html.twig', [
            'li' => $this->link,
            'filieres' => $filiereRepository->findAll()
        ]);
    }

    /**
     * Cette function permet de creer ou de modifier une filiere
     * @Route("/creation/filiere/creer", name="creer_filiere")
     * @Route("/creation/filiere/modifier/{slug}", name="edit_filiere")
     */
    public function editFiliere(?Filiere $filiere=null, FiliereRepository $filiereRepository, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $btn_title = "Enregistrer";
        $flashMessage = "Filière modifiée avec succès !";
        
        $editMode = true;
        if (!$filiere) {
            $filiere = new Filiere();
            $flashMessage = "Filière créée avec succès !";
            $editMode = false;
            
        }else {
            $btn_title = "Enregistrer les modifications";
        }
        
        $form = $this->createForm(FiliereType::class, $filiere);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $filiere->getName();
            $filiere->setSlug($slug);
            $this->entityManagerInterface->persist($filiere);
            $this->entityManagerInterface->flush();
            $this->addFlash('success', $flashMessage);
            return $this->redirectToRoute('filieres_home');
        }
        return $this->render('crud/filiere/edit.html.twig', [
            'form' => $form->createView(),
            'btn_title' => $btn_title,
            'editMode' => $editMode,
            'filieres' => $filiereRepository->findBy([], ['name'=>'ASC']),
            'filiere' => $filiere, 
            'li' => $this->link,
        ]);
    }

    /**
     * Cette fonction permet d'afficher les specialites d'une filiere donnee
     * @Route("/creation/filiere/specialites/{slug}", name="show_specialites")
     * @Route("/creation/filiere/specialites", name="show_all_specialites")
     */
    public function showSpecialites(?Filiere $filiere=null, SpecialiteRepository $specialiteRepository, FiliereRepository $filiereRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $specialites = [];
        if (!$filiere) {
            $specialites = $specialiteRepository->findBy([], ['name'=>'ASC']);
        }else {
            $specialites = $specialiteRepository->findBy(['filiere' => $filiere], ['name'=>'ASC']);
        }
        return $this->render('crud/filiere/specialite/index.html.twig', [
            'specialites' => $specialites,
            'filiere' => $filiere,
            'li' => $this->link,
            'filieres' => $filiereRepository->findBy([], ['name'=>'ASC']),

        ]);
    }

    /**
     * Cette fonction permet soit de créer une specialité
     * @Route("/creation/filiere/creer-speciliate/{slug}", name="creer_specialite")
     */
    public function createSpecialite(Filiere $filiere, SpecialiteRepository $specialiteRepository, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $btn_title = "Enregistrer";
        $flashMessage = "Spécialité ajoutée avec succès !";
        $specialite = new Specialite();
        $form = $this->createForm(SpecialiteType::class, $specialite);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $specialite->getName();
            $specialite->setSlug($slug);
            $specialite->setFiliere($filiere);
            $this->entityManagerInterface->persist($specialite);
            $this->entityManagerInterface->flush();
            $this->addFlash('success', $flashMessage);
            return $this->redirectToRoute('show_specialites', ['slug'=>$filiere->getSlug()]);
        }
        return $this->render('crud/filiere/specialite/edit.html.twig', [
            'form' => $form->createView(),
            'btn_title' => $btn_title,
            'filiere' => $filiere,
            'editMode' => false,
            'li' => $this->link,
            'specialites' => $specialiteRepository->findBy(['filiere'=>$filiere], ['name'=>'ASC']),

        ]);
    }

    /**
     * Cette function permet de modifier une specialité
     * @Route("/creation/filiere/modifier-speciliate/{slug}", name="edit_specialite")
     */
    public function editSpecialite(Specialite $specialite, SpecialiteRepository $specialiteRepository, EntityManagerInterface $manager, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $filiere = $specialite->getFiliere();
        $btn_title = "Enregistrer les modifications";
        $flashMessage = "Spécialité modifiée avec succès !";
        $form = $this->createForm(SpecialiteType::class, $specialite);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $specialite->getName();
            $specialite->setSlug($slug);
            $manager->persist($specialite);
            $manager->flush();
            $this->addFlash('success', $flashMessage);
            return $this->redirectToRoute('show_specialites', ['slug'=>$filiere->getSlug()]);
        }
        return $this->render('crud/filiere/specialite/edit.html.twig', [
            'form' => $form->createView(),
            'btn_title' => $btn_title,
            'filiere' => $filiere,
            'editMode' => true,
            'specialite' => $specialite, 
            'li' => $this->link,
            'specialites' => $specialiteRepository->findBy(['filiere'=>$filiere], ['name'=>'ASC']),
        ]);
    }

    /**
     * Cette fonction affiche les formations
     * @Route("/creation/formations", name="formation_home")
     */
    public function showFormations(FormationRepository $formationRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('crud/formation/index.html.twig', [
            'li' => $this->link,
            'formations' => $formationRepository->findBy([],['name'=>'ASC'])
        ]);
    }

    /**
     * Cette function permet soit de modifier soit d'ajouter une formation
     * @Route("/creation/formation/creer", name="creer_formation")
     * @Route("/creation/formation/modifier/{slug}", name="edit_formation")
     */
    public function editFormation(?Formation $formation=null, FormationRepository $formationRepository, EntityManagerInterface $manager, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $btn_title = "Enregistrer";
        $flashMessage = "Formation modifiée avec succès !";
        $editMode = true;
        if (!$formation) {
            $formation = new Formation();
            $flashMessage = "Formation créée avec succès !";
            $editMode = false;
            
        }else {
            $btn_title = "Enregistrer les modifications";
        }
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $formation->getName();
            $formation->setSlug($slug);
            $manager->persist($formation);
            $manager->flush();
            $this->addFlash('success', $flashMessage);
            return $this->redirectToRoute('formation_home');
        }
        return $this->render('crud/formation/edit.html.twig', [
            'form' => $form->createView(),
            'btn_title' => $btn_title,
            'editMode' => $editMode,
            'formations' => $formationRepository->findAll(),
            'formation' => $formation,
            'li' => $this->link,
        ]);
    }

    /**
     * Cette methode permet d'afficher la liste des salles de classe. 
     * On doit etre capable d'afficher les classes d'une filiere donnée ou d'afficher les classes 
     * d'une specialite donnee ou meme afficher toutes les classes. 
     * @Route("/gestion-des-classes/classes", name="all_classes")
     * @Route("/gestion-des-classes/creation/classes/{filiere_slug}", name="classes_filiere")
     * @Route("/gestion-des-classes/creation/classes/{filiere_slug}/{specialite_slug}", name="classes_specialite")
     */
    public function showClasses(?string $filiere_slug=null, ?string $specialite_slug=null,
        FiliereRepository $filiereRepository, ClasseRepository $classeRepository,
        SpecialiteRepository $specialiteRepository, ExamenRepository $examenRepository,
        Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $classes = [];
        if (!$filiere_slug && !$specialite_slug) {
            $classes = $classeRepository->findBy([], ['nom'=>'ASC']);
            $data['filieres'] = $filiereRepository->findAll();
        }
        elseif (!empty($filiere_slug)) {
            $filiere = $filiereRepository->findOneBy(['slug'=>$filiere_slug]);
            if (!$filiere) {
                throw $this->createNotFoundException('Page not found');
            }
            $data['filiere'] = $filiere;
            if (!empty($specialite_slug)) {
                $specialite = $specialiteRepository->findOneBy(['slug'=>$specialite_slug]);
                if (!$specialite) {
                    throw $this->createNotFoundException('Page not found');
                }
                $data['specialite'] = $specialite;
                $classes = $classeRepository->findBy(['specialite'=>$specialite], ['nom'=>'ASC']);
                
            }else {
                foreach ($filiere->getSpecialites() as $specialite) {
                    foreach ($specialite->getClasses() as $classe) {
                        $classes[] = $classe;
                    }
                }
            }
        }
        $data['classes'] = $classes;
        $data['li'] = 'cl';
        $data['annee'] = $request->getSession()->get('annee');
        $data['examens'] = $examenRepository->findBy([], ['code'=>'ASC']);
        return $this->render('crud/filiere/specialite/classe/index.html.twig', $data);
    }

    /**
     * Cette fonction permet de creer des salles de classe
     * @Route("/gestion-des-classes/creation/creer-classe/{filiere_slug}/{specialite_slug}", name="creer_classe")
     * @Route("/gestion-des-classes/creation/modifier-classe/{filiere_slug}/{specialite_slug}/{slug}", name="edit_classe")
     */
    public function editClasse($filiere_slug, $specialite_slug, ?Classe $classe=null,
        FiliereRepository $filiereRepository, SpecialiteRepository $specialiteRepository, EntityManagerInterface $manager, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $filiere = $filiereRepository->findOneBy(['slug'=>$filiere_slug]);
        $specialite = $specialiteRepository->findOneBy(['slug'=>$specialite_slug]);
        if (empty($filiere) || empty($specialite)) {
            throw $this->createNotFoundException('Page not found');
        }
        $btn_title = "Enregistrer";
        $editMode = true;
        $flashMessage = "classe modifiée avec succès !";
        if (!$classe) {
            $classe = new Classe();
            $flashMessage = "classe créée avec succès !";
            $editMode = false;
        }else {
            $btn_title = "Enregistrer les modifications";
        }
        $form = $this->createForm(ClasseType::class, $classe, ['filiere'=>$filiere]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $classe->getNom();
            $classe->setSlug($slug);
            $classe->setSpecialite($specialite);
            $manager->persist($classe);
            $manager->flush();
            $this->addFlash('success', $flashMessage);
            return $this->redirectToRoute('classes_specialite', ['filiere_slug'=>$filiere_slug, 'specialite_slug'=>$specialite_slug]);
        }
        return $this->render('crud/filiere/specialite/classe/edit.html.twig', [
            'form' => $form->createView(),
            'btn_title' => $btn_title,
            'filiere' => $filiere,
            'specialite' => $specialite,
            'editMode' => $editMode,
            'classe' => $classe,
            'li' => $this->link,
        ]);
    }

    /**
     * Cette fonction affiche les services
     * @Route("/creation/services", name="show_services")
     */
    public function showServices(ServiceRepository $serviceRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('crud/service/index.html.twig', [
            'li' => $this->link,
            'services' => $serviceRepository->findBy([], ['nom'=>'ASC']),
        ]);
    }

    /**
     * Cette function permet soit de creer soit de modifier un service 
     * @Route("/creation/service/creer", name="creer_service")
     * @Route("/creation/service/modifier/{slug}", name="edit_service")
     */
    public function editService(?Service $service=null, ServiceRepository $serviceRepository, EntityManagerInterface $manager, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $btn_title = "Créer le service";
        $flashMessage = "Service modifié avec succès !";
        $editMode = true;
        if (!$service) {
            $service = new Service();
            $flashMessage = "Service créé avec succès !";
            $editMode = false;
        }else {
            $btn_title = "Modifier le service";
        }
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $service->getNom();
            $service->setSlug($slug);
            $manager->persist($service);
            $manager->flush();
            $this->addFlash('success', $flashMessage);
            return $this->redirectToRoute('show_services');
        }
        return $this->render('crud/service/edit.html.twig', [
            'form' => $form->createView(),
            'btn_title' => $btn_title,
            'editMode' => $editMode,
            'li' => $this->link,
            'services' => $serviceRepository->findAll(),
            'service' => $service,
        ]);
    }
}
