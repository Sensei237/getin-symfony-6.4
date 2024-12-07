<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Examen;
use App\Entity\Module;
use App\Entity\Contrat;
use App\Entity\ECModule;
use App\Entity\Etudiant;
use App\Service\ContratUtils;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use App\Repository\AnneeAcademiqueRepository;
use App\Repository\ContratRepository;
use App\Repository\ECModuleRepository;
use App\Repository\EtudiantInscrisRepository;
use App\Repository\EtudiantRepository;
use App\Repository\ExamenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Point d'entrer de tous les liens qui permettent d'afficher les vues liees aux contrats academiques
 * @author : MBOZO'O METOU'OU Emmanuel Beranger Alias Sensei emmaberanger2@gmail.com
 */
class ContratController extends AbstractController
{
    private $link = 'et';
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }
    
    /*
        ==============================================================================
        || DANS CE CONTROLLER, NOUS ALLONS METTRE UNIQUEMENT DES FONCTIONS RESERVEES ||
        ||           A LA GESTION DES CONTRATS ACADEMIQUES DES ETUDIANTS.            ||
        ==============================================================================

        REMARQUE : SI LE SYSTEME NE S'OCCUPE PAS DU CHANGEMENT DE PROGRAMME D'UNE ANNEE A 
        L'AUTRE. POUR CELA SI UN ETUDIANT EST ADMIS EN CLASSE SUPERIEUR AVEC DES DETTES 
        DANS LA CLASSE ANTERIEURE, AU MOMENT DE LA GENERATION DES CONTRATS ACADEMIQUES DE 
        LA NOUVELLE ANNEE, LE SYSTEME VA AJOUTER CES MATIERES QUI N'ONT PAS ETE VALIDEES
        DANS SON NOUVEAU CONTRAT. AINSI SI LE PROGRAMME A SUBI DES CHANGEMENTS ET QU'IL 
        Y A DES MATIERES QUI ONT SAUTE, IL FAUDRA GERER LES CONTRATS DE CES ETUDIANTS AU 
        CAS PAR CAS EN SUPPRIMANT LES MATIERES QUI N'EXISTE PLUS ET EN AJOUTANT LES
        NOUVELLES MATIERES.
    */

    /**
     * Cette fonction va permettre de generer les contrats academiques de tous les
     * etudiants qui n'en ont pas encore.
     * Si un etudiant a deja un contrat academique, alors on le met a jour. 
     * l'annee academique doit etre l'annee encours si non on ne genere aucun contrat.
     * 
     * @Route("/cbbeb11e87767ee5c468a789a46e67ab/contrats-academiques/generer/{slug}", name="contrat_generer_tous")
     */
    public function genrerTousLesContratsAcademiques(AnneeAcademique $annee, ContratUtils $contratUtils, EntityManagerInterface $entityManagerInterface, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($annee->getIsArchived()) {
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['reloadWindow'=>false, 'hasError'=>true, 'msg'=>"IMPOSSIBLE DE REALISER CETTE OPERATION ! LES DONNEES DE CETTE ANNEE ACADEMIQUE SONT EN LECTURE SEULE"]), '200', ['Content-type'=>'appplication/json']);
            }else {
                throw new Exception("IMPOSSIBLE DE REALISER CETTE OPERATION ! LES DONNEES DE CETTE ANNEE ACADEMIQUE SONT EN LECTURE SEULE", 1);
            }
            
        }

        // ON SE RASSURE QU'AU MOINS UNE SALLE DE CLASSE A DEJA UN PROGRAMME ACADEMIQUE. 
        if (!$annee->getModules()) {
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['reloadWindow'=>false, 'hasError'=>true, 'msg'=>"AUCUN PROGRAMME ACADEMIQUE TROUVE ! VEUILLEZ D'ABORD RENSEIGNER LES PROGRAMMES ACADEMIQUES DES DIFFERENTES SALLES DE CLASSE SVP !"]), '200', ['Content-type'=>'appplication/json']);
            }else {
                $this->addFlash('warning', "AUCUN PROGRAMME ACADEMIQUE TROUVE ! VEUILLEZ D'ABORD RENSEIGNER LES PROGRAMMES ACADEMIQUES DES DIFFERENTES SALLES DE CLASSE SVP !");
                return $this->redirectToRoute('programme_academique', ['slug'=>$annee->getSlug()]);
            }
            
        }

        // ON SE RASSURE QU'IL EXISTE AU MOINS UN ETUDIANT DANS LE SYSTEME
        if (!$annee->getEtudiantInscris()) {
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>"AUCUN ETUDIANT TROUVE !", 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            else {
                $this->addFlash('warning', "AUCUN ETUDIANT TROUVE !");
                return $this->redirectToRoute('etudiants', ['slug'=>$annee->getSlug()]);
            }
            
        }

        $messages = $contratUtils->genererLesContrats($annee, $this->entityManagerInterface);
        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>false, 'msg'=>$messages, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
        }
        $this->addFlash('info', $messages);
        return $this->redirectToRoute('etudiants', ['slug' => $annee->getSlug()]);
    }

    /**
     * @Route("/cbbeb11e87767ee5c468a789a46e67ab/contrats/academiques/classe/{slug}/{slug_classe}/generer/contrats/academiques/etudiants", name="contrat_generer_contrats_classe")
     */
    public function genererContratsAcademiquesClasse(AnneeAcademique $annee, $slug_classe, ContratUtils $contratUtils, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived()) {
            $msg = "L'année ".$annee->getDenomination()." est en lecture seule !";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            throw new Exception($msg, 1);
        }

        $classe = $this->entityManagerInterface->getRepository(Classe::class)->findOneBy(['slug'=>$slug_classe]);
        if (!$classe) {
            $msg = "La classe spécifiée n'existe pas !";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            throw new Exception($msg, 1);
        }

        $inscriptions = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findBy(['anneeAcademique'=>$annee, 'classe'=>$classe]);
        if (!$inscriptions) {
            $msg = "Aucun étudiant n'a été trouvé dans cette classe pour cette année";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            throw new Exception($msg, 1);
        }
        $modules = $this->entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$classe, 'anneeAcademique'=>$annee]);
        if (!$modules) {
            $msg = "La classe ".$classe->getCode()." n'a pas encore de programme academique pour l'année académique ".$annee->getDenomination();
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            throw new Exception($msg, 1);
        }
        foreach ($inscriptions as $inscris) {
            $contratUtils->genererLeContrat($inscris, $modules, $this->entityManagerInterface);
        }
        $this->entityManagerInterface->flush();
        $msg = "Contrats académiques générés !";
        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>false, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
        }
        $this->addFlash('info', "Contrats académiques générés !");
        return $this->redirectToRoute('etudiants', ['slug' => $annee->getSlug()]);
    }

    /**
     * @Route("/cbbeb11e87767ee5c468a789a46e67ab/etudiants/contrats/academiques/{slug}/{matricule}/generer/les/contrats/academiques", name="contrat_generer")
     */
    public function genererContratsAcademiquesEtudiant(AnneeAcademique $annee, Etudiant $etudiant, ContratUtils $contratUtils, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived()) {
            $msg = "IMPOSSIBLE DE REALISER CETTE OPERATION ! LES DONNEES DE CETTE ANNEE ACADEMIQUE SONT EN LECTURE SEULE";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            throw new Exception($msg, 1);
        }

        $inscription = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findOneBy(['anneeAcademique'=>$annee, 'etudiant'=>$etudiant]);
        if (!$inscription) {
            $msg = "L'étudiant dont vous souhaitez générer les contrats académiques n'existe pas !";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            throw new Exception($msg, 1);
        }
        $modules = $this->entityManagerInterface->getRepository(Module::class)->findBy(['classe'=>$inscription->getClasse(), 'anneeAcademique'=>$annee]);
        if (!$modules) {
            $msg = "La classe ".$inscription->getClasse()->getCode()." n'a pas encore de programme academique pour l'année académique ".$annee->getDenomination();
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            throw new Exception($msg, 1);
        }
        $contratUtils->genererLeContrat($inscription, $modules, $this->entityManagerInterface);
        $this->entityManagerInterface->flush();
        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>false, 'msg'=>"Contrats générés ! La page va être rechargée dans quelques secondes.", 'reloadWindow'=>true]), '200', ['Content-type'=>'appplication/json']);
        }

        return $this->redirectToRoute('contrat_academique_afficher', ['matricule'=>$etudiant->getMatricule(), 'slug'=>$annee->getSlug()]);
    }

    /**
     * @Route("/cbbeb11e87767ee5c468a789a46e67ab/gestion-etudiants/contrats-academiques/ajouter-nouveau-contrat/{slugAnnee}/{id}", name="contrat_ajouter_une_nouvelle_matiere")
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     */
    public function ajouterContrat(AnneeAcademique $annee, EtudiantInscris $inscris, Request $request, 
        ContratRepository $contratRepository, ECModuleRepository $eCModuleRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($annee->getIsArchived()) {
            $msg = "L'année ".$annee->getDenomination()." est en lecture seule !";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            throw new Exception($msg, 1);
        }
        if (!$request->request->get('ecModule')) {
            $msg = "La matière n'a pas été spécifiée";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            throw $this->createNotFoundException();
        }
        $ecm = $eCModuleRepository->findOneBy(['id'=>$request->request->get('ecModule')]);
        $isDette = $request->request->get('isDette') ? true : false;
        $isOptionnal = $request->request->get('isOptionnal') ? true : false;
        if (!$ecm) {
            $msg = "La matière spécifiée n'existe pas !";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            $this->addFlash('danger', $msg);
            return $this->redirectToRoute('contrat_academique_afficher', ['matricule'=>$inscris->getEtudiant()->getMatricule(), 'slug'=>$annee->getSlug()]);
        }
        if ($contratRepository->findOneBy(['ecModule'=>$ecm, 'etudiantInscris'=>$inscris])) {
            $msg = "La matière spécifiée existe déjà dans le contrat de l'étudiant !";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            $this->addFlash('danger', $msg);
            return $this->redirectToRoute('contrat_academique_afficher', ['matricule'=>$inscris->getEtudiant()->getMatricule(), 'slug'=>$annee->getSlug()]);
        }
        $contrat = new Contrat();
        $contrat->setEcModule($ecm)
                ->setIsDette($isDette)
                ->setIsValidated(false)
                ->setEtudiantInscris($inscris)
                ->setIsOptionnal($isOptionnal);
        ;
        if ($isDette) {
            // On cherche le contrat precedent qui correspond à cette matière !
            $contratPrecedent = $contratRepository->findContratPrecedent($ecm, $inscris);
            if (!$contratPrecedent) {
                $msg = "Opération impossible ! Vous avez spécifié que cette matière est une dette mais aucun contrat précédent n'a été trouvé pour cette matière concernant cet étudiant !";
                if ($request->isXmlHttpRequest()) {
                    return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
                }
                $this->addFlash('danger', $msg);
                return $this->redirectToRoute('contrat_academique_afficher', ['matricule'=>$inscris->getEtudiant()->getMatricule(), 'slug'=>$annee->getSlug()]);
            }
            $contrat->setContratPrecedent($contratPrecedent);
        }
        $this->entityManagerInterface->persist($contrat);
        $this->entityManagerInterface->flush();
        $msg = "Contrat ajouté !";
        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>false, 'msg'=>$msg." <br>La page va être rechargée dans quelques secondes", 'reloadWindow'=>true]), '200', ['Content-type'=>'appplication/json']);
        }
        $this->addFlash('info', $msg);
        return $this->redirectToRoute('contrat_academique_afficher', ['matricule'=>$inscris->getEtudiant()->getMatricule(), 'slug'=>$annee->getSlug()]);
    }

    /**
     * Cette methode permet de modifier le statut d'un contrat (modifier le champ isDette)
     * @Route("/cbbeb11e87767ee5c468a789a46e67ab/etudiant/{matricule}/contrat/academique/{id}/modifier/status/{slug}", name="contrat_edit_status")
     */
    public function editStatus(Etudiant $etudiant, Contrat $contrat, AnneeAcademique $annee,
        ContratRepository $contratRepository, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($annee->getIsArchived()) {
            throw new Exception("L'année ".$annee->getDenomination()." est en lecture seule !", 1);
        }
        if (!$request->request->get('contrat')) {
            throw $this->createNotFoundException();
        }
        $contrat = $contratRepository->findOneBy(['id'=>$request->request->get('contrat')]);
        if (!$contrat) {
            throw new Exception("Cette matière n'existe pas !", 1);
        }
    }

    /**
     * Cette action du controller permet d'afficher la liste des matieres qu'un etudiant donné doit valider
     * @Route("/cbbeb11e87767ee5c468a789a46e67ab/etudiants/contrats/academiques/{matricule}/{slug}/show", name="contrat_academique_afficher")
     */
    public function afficherContratEtudiant(Etudiant $etudiant, AnneeAcademique $annee, 
        EtudiantInscrisRepository $etudiantInscrisRepository, ECModuleRepository $eCModuleRepository, 
        ExamenRepository $examenRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $inscription = $etudiantInscrisRepository->findOneBy(['anneeAcademique'=>$annee, 'etudiant'=>$etudiant]);
        if (!$inscription) {
            throw new Exception("L'étudiant dont vous souhaitez consulter le profil n'existe pas !", 1);
        }
        $othersECM = [];
        $others = $eCModuleRepository->findECModulesByClasse($inscription->getClasse());
        foreach ($others as $ecm) {
            $existe = false;
            foreach ($inscription->getContrats() as $contrat) {
                if ($ecm->getId() == $contrat->getECModule()->getId()) {
                    $existe = true;
                    break;
                }
            }
            if (!$existe) {
                $othersECM[] = $ecm;
            }
        }

        return $this->render('etudiant/profil/contrat.html.twig', [
            'inscription' => $inscription,
            'annee' => $annee,
            'li' => $this->link,
            'othersECModules' => $othersECM,
            'examens' => $examenRepository->findBy([], ['intitule'=>'DESC']),

        ]);
    }

    /**
     * @Route("/cbbeb11e87767ee5c468a789a46e67ab/etudiants/contrats/academiques/{slug}/{id}/{matricule}/supprimer/contrat/academique", name="contrat_supprimer")
     */
    public function supprimerContrat($slug, Contrat $contrat, $matricule, 
        AnneeAcademiqueRepository $anneeAcademiqueRepository, EtudiantRepository $etudiantRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $annee = $anneeAcademiqueRepository->findOneBy(['slug'=>$slug]);
        $etudiant = $etudiantRepository->findOneBy(['matricule'=>$matricule]);
        $inscris = $etudiantRepository->findOneBy(['etudiant'=>$etudiant, 'anneeAcademique'=>$annee]);
        if (!$annee || !$inscris || ($contrat->getEtudiantInscris()->getId() != $inscris->getId())) {
            throw new Exception("ACTION IMPOSSIBLE !", 1);
        }

        if ($annee->getIsArchived()) {
            throw new Exception("L'année ".$annee->getDenomination()." est en lecture seule !", 1);
        }
        
        $this->entityManagerInterface->remove($contrat);
        $this->entityManagerInterface->flush();
        return $this->redirectToRoute('contrat_academique_afficher', ['matricule'=>$etudiant->getMatricule(), 'slug'=>$annee->getSlug()]);
    }
    
}