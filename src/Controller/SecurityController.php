<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Entity\ECModule;
use App\Entity\Configuration;
use App\Entity\MatiereASaisir;
use App\Entity\AnneeAcademique;
use App\Form\OperateurSaisieType;
use App\Repository\ECModuleRepository;
use App\Repository\MatiereASaisirRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @Route("se-connecter", name="security_login")
     * @Route("login", name="app_security_login")
     */
    public function seConnecter(AuthenticationUtils $authenticationUtils)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }
    
    /**
     * @Route("se-deconnecter", name="security_logout")
     * @Route("logout", name="app_security_logout")
     */
    public function logout() {}

    /**
     * On appelle cette fonction lorsque l'on souhaite creer un compte client 
     * @Route("/gestion-utilisateur/registration", name="security_user_registration")
     * @Route("/gestion-utilisateur/registration/edit-{id}", name="security_user_edit")
     */
    public function UserRegistration(?User $user=null, EntityManagerInterface $objectManager, 
    Request $request, UserPasswordHasherInterface $encoder, UserRepository $userRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (!$user) {
            $user = new User();
        }
        // die(var_dump($user->getRoles()));
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$userRepository->findOneBy(['username'=>$user->getUsername()])) {
                $roles = implode('-', $user->droits);
                $paswordHashed = $encoder->hashPassword($user, $user->getPassword());
                $user->setPassword($paswordHashed)
                     ->setRoles($roles);
                $objectManager->persist($user);
                $objectManager->flush();
                
                $this->addFlash('info', "Votre compte a été créé ! Pour vous connecter utiliser votre no, d'utilisateur ainsi que votre mot de passe.");
                return $this->redirectToRoute('security_home');
            }
            $this->addFlash('info', "Ce nom d'utilisateur est déjà reservé !");
        }

        return $this->render('security/create_user.html.twig', [
            'form' => $form->createView(),
            'btn_title' => 'Créer le compte !',
            'li' => 'user',
            'user' => $user,
        ]);
    }
    /**
     * @Route("/gestion-utilisateur", name="security_home")
     */
    public function showUsers(UserRepository $userRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        return $this->render('security/index.html.twig', [
            'users' => $userRepository->findAll(),
            'li' => 'user'
        ]);
    }
    
    /**
     * @Route("/gestion-utilisateur/delete-user-{id}", name="security_delete_user")
     */
    public function deleteUser(User $user, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        foreach ($user->getMatiereASaisirs() as $ms) {
            $this->entityManagerInterface->remove($ms);
        }
        $this->entityManagerInterface->remove($user);
        $this->entityManagerInterface->flush();
        
        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['isDeleted'=>true, 'msg'=>"Utilisateur supprimé ! La page va être rechargée.", 'reloadWindow'=>true]), '200', ['Content-type'=>'appplication/json']);
        }
        $this->addFlash('success', "Utilisateur Supprimé !");
        return $this->redirectToRoute('security_home');
    }

    /**
     * @Route("/gestion-utilisateur/operateur-saisie/ajouter-{slug}", name="security_add_operateur")
     * 
     * On se charge juste d'indiquer les matieres a saisir a un utilisateur
     */
    public function addOperateurSaisie(AnneeAcademique $annee, Request $request, MatiereASaisirRepository $matiereASaisirRepository, ECModuleRepository $eCModuleRepository, EntityManagerInterface $objectManager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived()) {
            $this->createNotFoundException("Annee en lecture seule !");
        }

        $matiereAsaisir = new MatiereASaisir();
        $form = $this->createForm(OperateurSaisieType::class, $matiereAsaisir);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ecms = [];
            if (!empty($matiereAsaisir->ecsModule)) {
                $ecms = $matiereAsaisir->ecsModule;
            }elseif (!empty($matiereAsaisir->classe)) {
                $ecms = $eCModuleRepository->findActualECModulesByClasse($annee, $matiereAsaisir->classe);
            }elseif (!empty($matiereAsaisir->specialite)) {
                foreach ($matiereAsaisir->specialite->getClasses() as $classe) {
                    if ($classe->getFormation()->getId() == $matiereAsaisir->formation->getId()) {
                        foreach ($eCModuleRepository->findActualECModulesByClasse($annee, $classe) as $ecm) {
                            $ecms[] = $ecm;
                        }
                    }
                }
            }elseif (!empty($matiereAsaisir->filiere)) {
                foreach ($matiereAsaisir->filiere->getSpecialites() as $s) {
                    foreach ($s->getClasses() as $classe) {
                        if ($classe->getFormation()->getId() == $matiereAsaisir->formation->getId()) {
                            foreach ($eCModuleRepository->findActualECModulesByClasse($annee, $classe) as $ecm) {
                                $ecms[] = $ecm;
                            }
                        }
                    }
                }
            }
            
            foreach ($ecms as $ecm) {
                $ms = $matiereASaisirRepository->findOneBy(['ecModule'=>$ecm, 'examen'=>$matiereAsaisir->getExamen()]);
                if (!$ms) {
                    $ms = new MatiereASaisir();
                    $ms->setUser($matiereAsaisir->getUser())
                        ->setExamen($matiereAsaisir->getExamen())
                        ->setDateExpiration($matiereAsaisir->getDateExpiration())
                        ->setEcModule($ecm)
                        ->setAnneeAcademique($annee)
                        ->setIsSaisie(false);
                }else {
                    $ms->setUser($matiereAsaisir->getUser())
                        ->setDateExpiration($matiereAsaisir->getDateExpiration());
                }
                $ms->setIsSaisiAnonym($matiereAsaisir->getIsSaisiAnonym());

                $objectManager->persist($ms);
            }

            $matiereAsaisir->getUser()->setRole('ROLE_SAISIE_NOTES');
            $objectManager->flush();

            $this->addFlash("success", "Opérateur de saisie créer ou modifier");
            return $this->redirectToRoute('security_add_operateur', ['slug'=>$annee->getSlug()]);
        }
        
        return $this->render('security/operateur.html.twig', [
            'form' => $form->createView(),
            'li' => 'user',
            'addMode' => true,
        ]);
    }

    /**
     * @Route("/gestions-notes/operateur-saisie/list-matieres/a-saisir/{id}/{slugAnnee}", name="security_user_list_matiere")
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * 
     * Cette fonction permet d'afficher la liste des matiere qu'un utilisateur doit saisir
     */
    public function showUserMatieresASaisir(User $user, AnneeAcademique $annee)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('security/operateur.html.twig', [
            'user' => $user,
            'annee' => $annee,
            'li' => 'user',
            'showMatiereMode' => true,
        ]);
    }

    /**
     * @Route("/gestion-utilisateurs/delete-matiere-saisir/{idMS}/{idUser}/{slugAnnee}", name="security_delete_ms")
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("ms", options={"mapping": {"idMS": "id"}})
     * @ParamConverter("user", options={"mapping": {"idUser": "id"}})
     * Permet de supprimer une matiere a saisir
     */
    public function deleteMatiereASaisir(MatiereASaisir $ms, User $user, AnneeAcademique $annee, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->entityManagerInterface->remove($ms);
        $this->entityManagerInterface->flush();
        
        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['isDeleted'=>true, 'msg'=>"Matière Supprimée ! Lla page va être rechargée.", 'reloadWindow'=>true]), '200', ['Content-type'=>'appplication/json']);
        }
        $this->addFlash('success', "Matière Supprimée !");
        return $this->redirectToRoute('security_user_list_matiere', ['id'=>$user->getId(), 'slugAnnee'=>$annee->getId()]);
    }

    /**
     * @Route("/creer-compte-test", name="security_user_test")
     */
    public function testRegistration(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $encoder)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$userRepository->findOneBy(['username'=>$user->getUsername()])) {
                $roles = implode('-', $user->droits);
                $paswordHashed = $encoder->hashPassword($user, $user->getPassword());
                $user->setPassword($paswordHashed)
                     ->setRoles($roles);
                $this->entityManagerInterface->persist($user);
                $this->entityManagerInterface->flush();
                
                $this->addFlash('info', "Votre compte a été créé ! Pour vous connecter utiliser votre no, d'utilisateur ainsi que votre mot de passe.");
                return $this->redirectToRoute('security_login');
            }
            $this->addFlash('info', "Ce nom d'utilisateur est déjà reservé !");
        }

        return $this->render('security/create_user.html.twig', [
            'form' => $form->createView(),
            'btn_title' => 'Créer le compte !'
        ]);
    }

    /**
     * On appelle cette fonction lorsque l'année academique qui est encours est arrivée à 
     * terme. Seul le superadmin est abilité à effectuer cette opération
     * 
     * @Route("/cloturer/annee/{slug}", name="security_cloturer_annee")
     */
    public function cloturerLAnneeAcademique(AnneeAcademique $annee, EntityManagerInterface $objectManager, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_SUPER_USER');

        $config = new Configuration();
        
        $config->setSlogan($annee->getConfiguration()->getSlogan())
               ->setTelephone($annee->getConfiguration()->getTelephone())
               ->setNomEcole($annee->getConfiguration()->getNomEcole())
               ->setNomEcoleEn($annee->getConfiguration()->getNomEcoleEn())
               ->setNoteEliminatoire($annee->getConfiguration()->getNoteEliminatoire())
               ->setNotePourValiderUneMatiere($annee->getConfiguration()->getNotePourValiderUneMatiere())
               ->setPourcentageECForADC($annee->getConfiguration()->getPourcentageECForADC())
               ->setLogo($annee->getConfiguration()->getLogo())
               ->setLogoUniversity($annee->getConfiguration()->getLogoUniversity())
               ->setInitiale($annee->getConfiguration()->getInitiale())
               ->setEmail($annee->getConfiguration()->getEmail())
               ->setAdresse($annee->getConfiguration()->getAdresse())
               ->setIsValidationModulaire($annee->getConfiguration()->getIsValidationModulaire())
               ->setIsSREcraseSN($annee->getConfiguration()->getIsSREcraseSN())
               ->setBoitePostale($annee->getConfiguration()->getBoitePostale())
               ->setNotePourValiderUnModule($annee->getConfiguration()->getNotePourValiderUnModule())
               ->setVille($annee->getConfiguration()->getVille())
               ->setIsRattrapageSurToutesLesMatieres($annee->getConfiguration()->getIsRattrapageSurToutesLesMatieres())
               ;

        $year = trim(explode('-', $annee->getDenomination())[1]);
        $newDenomination = $year.' - '.($year+1);
        $newSlug = $year.'-'.($year+1);

        $newAnnee = new AnneeAcademique();
        $newAnnee->setConfiguration($config)
                 ->setDenomination($newDenomination)
                 ->setSlug($newSlug)
                 ->setIsArchived(false);

        $objectManager->persist($newAnnee);
        $annee->setIsArchived(true);
        $objectManager->flush();

        $request->getSession()->set('annee', $newAnnee);
        $this->addFlash('success', 'Année académique cloturée');

        return $this->redirectToRoute('security_logout');
    }
}
