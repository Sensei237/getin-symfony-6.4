<?php

namespace App\Controller;

use App\Entity\EC;
use App\Entity\Classe;
use App\Entity\Examen;
use App\Entity\Contrat;
use App\Entity\Filiere;
use App\Entity\Anonymat;
use App\Entity\Etudiant;
use App\Entity\Formation;
use App\Entity\Specialite;
use App\Service\ExportUtils;
use App\Service\ImportUtils;
use App\Service\EtudiantUtils;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use App\Form\UploadProgramType;
use App\Form\EtudiantInscrisType;
use App\Repository\AnneeAcademiqueRepository;
use App\Repository\ClasseRepository;
use App\Repository\EtudiantInscrisRepository;
use App\Repository\FiliereRepository;
use App\Repository\FormationRepository;
use App\Repository\SpecialiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Ce controller contient toutes les actions qu'il est possible de faire concernant un etudiant
 * @author : MBOZO'O METOU'OU Emmanuel Beranger emmaberanger2@gmail.com
 */
class EtudiantController extends AbstractController
{
    /*
        ||==========================================================================||
        ||   LES FONCTIONS QUI SUIVENT PERMETTENT DE GERER LES ETUDIANTS DE L'ECOLE ||
        ||==========================================================================||
    */

    private $link = 'et';
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @Route("/gestion-etudiants/etudiants/{slug}/{page}", name="etudiants", defaults={"page"=1})
     */
    public function index(AnneeAcademique $annee, int $page=1, Request $request,
        SpecialiteRepository $specialiteRepository, FiliereRepository $filiereRepository, 
        ClasseRepository $classeRepository, FormationRepository $formationRepository,
        EtudiantInscrisRepository $etudiantInscrisRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $request->getSession()->set('subTitle', null);
        if ($page <= 0) {
            throw $this->createNotFoundException("La page $page n'existe pas !");
        }
        $slug_filiere = $request->get('filiere');
        $slug_formation = $request->get('formation');
        $slug_classe = $request->get('classe');
        $slug_specialite = $request->get('specialite');
        $searchText = trim($request->get('search'));
        $status = trim($request->get('statut'));
        $nbResultatPerPage = 20;

        $specialite = $specialiteRepository->findOneBy(['slug'=>$slug_specialite]);
        $filiere = $filiereRepository->findOneBy(['slug'=>$slug_filiere]);
        $classe = $classeRepository->findOneBy(['slug'=>$slug_classe]);
        $formation = $formationRepository->findOneBy(['slug'=>$slug_formation]);

        $specialites = $specialiteRepository->findBy(['filiere'=>$filiere]);
        $classes = $classeRepository->findBy(['specialite'=>$specialite]);
        $start = ($page-1)*$nbResultatPerPage;
        $nbPages = $searchText ? 1 : (int) (count($etudiantInscrisRepository->findEtudiants($annee, 0, NULL, $formation, $filiere, $specialite, $classe, $status))/$nbResultatPerPage)+1;
        $pages = [];
        if ($nbPages > 1) {
            for ($i=1; $i <= $nbPages; $i++) { 
                $pages[] = $i;
            }
        }
        return $this->render('etudiant/index.html.twig', [
            'etudiants' => $searchText ? $etudiantInscrisRepository->search($annee, $searchText) : $etudiantInscrisRepository->findEtudiants($annee, $start, $nbResultatPerPage, $formation, $filiere, $specialite, $classe, $status),
            'currentPage' => $page,
            'pages' => $pages,
            'formations' => $formationRepository->findAll(),
            'filieres' => $filiereRepository->findAll(),
            'specialites' => $specialites,
            'classes' => $classes,
            'filiere' => $filiere,
            'classe' => $classe,
            'specialite' => $specialite,
            'formation' => $formation,
            'annee' => $annee,
            'nbPages' => $nbPages,
            'searchText' => $searchText,
            'li' => $this->link,
            'allSpecialites' => $specialiteRepository->findAll(),
            'statut' => $status,
            'cmp' => (($page-1)*$nbResultatPerPage)+1,
            
        ]);
    }

    /**
     * @Route("/gestion-etudiants/etudiants/ajouter/{slug_annee}/{slug}", name="ajouter_etudiant")
     */
    public function ajouterEtudiant(string $slug_annee, Classe $classe, 
        AnneeAcademiqueRepository $anneeAcademiqueRepository, 
         EntityManagerInterface $objectManager, Request $request, EtudiantUtils $etudiantUtils)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $annee = $anneeAcademiqueRepository->findOneBy(['slug'=>$slug_annee]);
        if (!$annee) {
            throw $this->createNotFoundException('Page not found');
        }
        if ($annee->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            throw new Exception("L'année ".$annee->getDenomination()." est en lecture seule !", 1);
        }

        $postUrl = $this->generateUrl('ajouter_etudiant', ['slug_annee'=>$slug_annee, 'slug'=>$classe->getSlug()]);
        $inscription = new EtudiantInscris();
        $form = $this->createForm(EtudiantInscrisType::class, $inscription, ['action'=>$postUrl]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $inscription->setClasse($classe);
            $inscription->setAnneeAcademique($annee);

            $objectManager->persist($inscription);
            if (!$inscription->getEtudiant()->getMatricule()) {
                $etudiantUtils->genererMatricule($annee, $inscription, $objectManager);
            }
            $inscription->getEtudiant()->setLastUpdateAt(new \DateTimeImmutable());
            $objectManager->flush();

            return $this->redirectToRoute('etudiants', ['slug'=>$annee->getSlug()]);
        }

        return $this->render('etudiant/ajouter.html.twig', [
            'form' => $form->createView(),
            'annee' => $annee,
            'classe' => $classe,
            'li' => $this->link,
        ]);
    }

    /**
     * Cette fonction va servir pour l'importation des etudiants. 
     * Le fichier pris en charge ici est le fichier excel au format xlsx
     * Chaque feuille du fichier doit contenir la liste des etudiants de la classe.
     * la colonne B2 du fichier doit contenir le code de la classe.
     * @Route("/gestion-etudiants/etudiants/importer/fichier/excel/{slug}", name="importer_etudiants")
     */
    public function importerEtudiants(AnneeAcademique $annee, Request $request, EntityManagerInterface $objectManager, ImportUtils $importUtils)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            throw new Exception("L'année ".$annee->getDenomination()." est en lecture seule !", 1);
        }
        
        $form = $this->createForm(UploadProgramType::class);
        $form->add('hasMatricule', CheckboxType::class, [
            'required' => false,
            'label' => 'Le fichier contient des matricules (Cocher si oui)'
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $flashAlert = $importUtils->doImportation($objectManager, $form, 'e', $annee, $this->getParameter('sample_directory'));
            $objectManager->flush();
            if ($flashAlert != '') {
                $this->addFlash('errors', $flashAlert);
            }else {
                $this->addFlash('success', 'L\'importation effectuée ! Vérifiez dans la liste ci-dessous.');
            }
            
            return $this->redirectToRoute('etudiants', ['slug'=>$annee->getSlug()]);
        }

        return $this->render('etudiant/importer.html.twig', [
            'pageTitle' => 'Importer les étudiants',
            'annee' => $annee,
            'form' => $form->createView(),
            'li' => $this->link,
        ]);
    }

    /**
     * @Route("/gestion-etudiants/etudiants/exporter/fichier/{slug}/format/{format}", name="etudiant_exporter_etudiants")
     */
    public function exporterEtudiants(AnneeAcademique $annee, $format, Request $request, EntityManagerInterface $entityManagerInterface, ExportUtils $exportUtils)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $slug_filiere = $request->get('filiere');$slug_formation = $request->get('formation');
        $slug_classe = $request->get('classe');$slug_specialite = $request->get('specialite');

        $specialite = $entityManagerInterface->getRepository(Specialite::class)->findOneBy(['slug'=>$slug_specialite]);
        $filiere = $entityManagerInterface->getRepository(Filiere::class)->findOneBy(['slug'=>$slug_filiere]);
        $classe = $entityManagerInterface->getRepository(Classe::class)->findOneBy(['slug'=>$slug_classe]);
        $formation = $entityManagerInterface->getRepository(Formation::class)->findOneBy(['slug'=>$slug_formation]);
        $status = trim($request->get('statut'));
        $etudiants = [];
        if ($classe) {
            $etudiants[] = [
                'classe' => $classe,
                'etudiants' => $entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, null, $classe->getFormation(), $classe->getSpecialite()->getFiliere(), $classe->getSpecialite(), $classe, $status),
            ];
            
        }elseif ($specialite) {
            $i = 0;
            foreach ($specialite->getClasses() as $cl) {
                $etudiants[] = [
                    'classe' => $cl,
                    'etudiants' => $entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, null, $cl->getFormation(), $cl->getSpecialite()->getFiliere(), $cl->getSpecialite(), $cl, $status),
                ];
            }
        }elseif ($filiere) {
            foreach ($filiere->getSpecialites() as $s) {
                foreach ($s->getClasses() as $cl) {
                    $etudiants[] = [
                        'classe' => $cl,
                        'etudiants' => $entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, null, $cl->getFormation(), $cl->getSpecialite()->getFiliere(), $cl->getSpecialite(), $cl, $status),
                    ];
                }
            }
        }else {
            if ($formation) {
                foreach ($formation->getClasses() as $cl) {
                    $etudiants[] = [
                        'classe' => $cl,
                        'etudiants' => $entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, null, $cl->getFormation(), $cl->getSpecialite()->getFiliere(), $cl->getSpecialite(), $cl, $status),
                    ];
                }
            }else {
                $classes = $entityManagerInterface->getRepository(Classe::class)->findAll();
                foreach ($classes as $cl) {
                    $etudiants[] = [
                        'classe' => $cl,
                        'etudiants' => $entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, null, $cl->getFormation(), $cl->getSpecialite()->getFiliere(), $cl->getSpecialite(), $cl, $status),
                    ];
                }
            }

        }
        $request->getSession()->set('subTitle', null);
        if ($status == 'add') {
            $request->getSession()->set('subTitle', "Admis définitivement");
        }elseif ($status == 'adc') {
            $request->getSession()->set('subTitle', "Admis avec compensation");
        }elseif ($status == 'redouble') {
            $request->getSession()->set('subTitle', "Redoublants");
        }elseif ($status == 'nouveaux') {
            $request->getSession()->set('subTitle', "Nouveaux dans la classe");
        }elseif ($status == 'redoublants') {
            $request->getSession()->set('subTitle', "Reprennent la classe");
        }

        $result = $exportUtils->doExportation($etudiants, $annee, $format);
        return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @Route("/gestion-etudiants/etudiants/profile/{matricule}/{slug}/show", name="etudiant_profile_etudiant")
     */
    public function showProfile(Etudiant $etudiant, AnneeAcademique $annee, EntityManagerInterface $entityManagerInterface)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $inscription = $entityManagerInterface->getRepository(EtudiantInscris::class)->findOneBy(['anneeAcademique'=>$annee, 'etudiant'=>$etudiant]);
        if (!$inscription) {
            throw new Exception("L'étudiant dont vous souhaitez consulter le profil n'existe pas !", 1);
        }

        return $this->render('etudiant/profil/profile.html.twig', [
            'inscription' => $inscription,
            'annee' => $annee,
            'li' => $this->link,
        ]);
    }

    /**
     * @Route("/gestion-etudiants/etudaints/profile/{matricule}/{slug}/modifier", name="etudiant_edit_profile_etudiant")
     */
    public function editProfile(Etudiant $etudiant, AnneeAcademique $annee, Request $request, EntityManagerInterface $manager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $inscription = $manager->getRepository(EtudiantInscris::class)->findOneBy(['anneeAcademique'=>$annee, 'etudiant'=>$etudiant]);
        if (!$inscription) {
            throw new Exception("L'étudiant dont vous souhaitez modifier le profil n'existe pas !", 1);
        }
        $postUrl = $this->generateUrl('etudiant_edit_profile_etudiant', ['matricule'=>$etudiant->getMatricule(), 'slug'=>$annee->getSlug()]);
        $form = $this->createForm(EtudiantInscrisType::class, $inscription, ['action'=>$postUrl]);
        $form->add('classe', EntityType::class, [
            'class' => Classe::class,
            'choice_label' => 'nom',
            'attr' => [
                'class' => 'select2',
            ],
            'required' => true,
            'label' => "Actuellement inscrit en classe de"
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();
            $inscription->getEtudiant()->setLastUpdateAt(new \DateTimeImmutable());
            
            return $this->redirectToRoute('etudiant_profile_etudiant', ['matricule'=>$etudiant->getMatricule(), 'slug'=>$annee->getSlug()]);
        }

        return $this->render('etudiant/profil/edit_profile.html.twig', [
            'inscription' => $inscription,
            'annee' => $annee,
            'form' => $form->createView(),
            'isEditMode' => true, 
            'li' => $this->link,
        ]);
    }
    
    /**
     * @Route("/gestion-etudiants/liste/etudiants/suivent/matiere/{slug}/{slug_annee}/page/{page}", name="etudiant_suivent_matiere")
     * @Route("/gestion-etudiants/liste/etudiants/suivent/matiere/classe/{slug}/{slug_annee}/page/{page}/{slugClasse}", name="etudiant_suivent_matiere_classe")
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function listeDesEtudiantsQuiSuiventLaMatiere(EC $ec, $slug_annee, $page, ?Classe $classe=null, EntityManagerInterface $entityManagerInterface, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $annee = $entityManagerInterface->getRepository(AnneeAcademique::class)->findOneBy(['slug'=>$slug_annee]);
        if (!$annee) {
            throw new Exception("L'année académique $slug_annee n'existe pas", 1);
        }
        if ($page <= 0) {
            throw $this->createNotFoundException("La page $page n'existe pas !");
        }
        $nbResultatPerPage = 15;
        $contrats = $this->getContrats($annee, $ec, $classe, $request->get('statut'), null, null, true, $request);
        // if (!$classe) {
        //     $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsForEC($annee, $ec);
        // }else {
        //     $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsClasseForEC($annee, $ec, $classe);
        // }
        
        $start = ($page-1)*$nbResultatPerPage;
        $nbPages = (int) ((count($contrats)/$nbResultatPerPage) + 1);
        $pages = [];
        if ($nbPages > 1) {
            for ($i=1; $i <= $nbPages; $i++) { 
                $pages[] = $i;
            }
        }

        $visibleContrats = [];
        for ($i=$start; $i < $nbResultatPerPage+$start; $i++) { 
            if (isset($contrats[$i])) {
                // dump($contrats[$i]);die();
                $visibleContrats[] = $contrats[$i];
            }
        }

        return $this->render('etudiant/etudiant_suivent_matiere.html.twig', [
            'contrats' => $visibleContrats, //$this->getContrats($annee, $ec, $classe, $request->get('statut'), $nbResultatPerPage, $start),
            'annee' => $annee,
            'li' => $this->link,
            'ec' => $ec,
            'pages' => $pages,
            'currentPage' => $page,
            'nbPages' => $nbPages,
            'i' => (($page-1)*$nbResultatPerPage)+1,
            'classe' => $classe,
            'examens' => $entityManagerInterface->getRepository(Examen::class)->findBy([], ['intitule'=>'DESC']),
            'statut' => $request->get('statut'),
        ]);
    }

    private function getContrats(AnneeAcademique $annee, EC $ec, Classe $classe=null, $statut=null, $nbResultatPerPage=null, $start=null, $addFlash=false, Request $request)
    {
        $contrats = $contrats2 = [];
        if ($classe) {
            $contrats2 = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsClasseForEC($annee, $ec, $classe, $nbResultatPerPage, $start);
        }else {
            $contrats2 = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsForEC($annee, $ec, $nbResultatPerPage, $start);   
        }
        $flashMsg = '';
        switch ($statut) {
            case 'VSN':
                // On s'occupe uniquement des etudiants ayant valide en session normale
                $contrats = [];
                foreach ($contrats2 as $c) {
                    if ($c->getIsValidated() && $annee->getConfiguration()->getNotePourValiderUneMatiere() <= $c->getNoteSN()) {
                        $contrats[] = $c;
                    }
                }
                $flashMsg = "Liste des etudiants ayant validé cette matière en session normale";
                break;
            case 'VSR':
                // Les etudiants qui ont validé  au rattrappage
                foreach ($contrats2 as $c) {
                    if ($c->getIsValidated() && $annee->getConfiguration()->getNotePourValiderUneMatiere() <= $c->getNoteSR()) {
                        $contrats[] = $c;
                    }
                }
                $flashMsg = "Liste des etudiants ayant validé cette matière au rattrappage ";
                break;
            case 'DEL':
                // Les etudiants qui ont subit une délibération
                foreach ($contrats2 as $c) {
                    if ($c->getNoteJury()) {
                        $contrats[] = $c;
                    }
                }
                $flashMsg = "Liste des etudiants ayant été délibérés pour cette matière";
                break;
            case 'VDEL':
                // Les étudiants qui ont validé apres la délibération
                foreach ($contrats2 as $c) {
                    if ($c->getIsValidated() && $c->getNoteJury()) {
                        $contrats[] = $c;
                    }
                }
                $flashMsg = "Liste des etudiants ayant validé après les délibérations pour cette matière";
                break;
            case 'PRAT':
                // Les étudiants qui sont attendu au rattrapage
                foreach ($contrats2 as $c) {
                    if (!$c->getIsValidated() && $c->getNoteCC() && $c->getNoteSN() && $annee->getConfiguration()->getNotePourValiderUneMatiere() > $c->getNoteSN()) {
                        $contrats[] = $c;
                    }
                }
                $flashMsg = "Liste des etudiants devant faire le rattrapage pour cette matière";
                break;
            case 'NCC':
                // Les etudiants qui n'ont pas de notes de cc
                foreach ($contrats2 as $c) {
                    if (!$c->getNoteCC()) {
                        $contrats[] = $c;
                    }
                }
                $flashMsg = "Liste des etudiants sans notes de CC pour cette matière";
                break;
            case 'NNSN':
                // Les etudiants qui n'ont pas de notes de session normale
                foreach ($contrats2 as $c) {
                    if (!$c->getNoteSN()) {
                        $contrats[] = $c;
                    }
                }
                $flashMsg = "Liste des etudiants sans notes de session normale pour cette matière";
                break;
            default:
                $contrats = $contrats2;
                break;
        }
        if ($addFlash) {
            $this->addFlash('info', $flashMsg);
        }
        $request->getSession()->set('subTitle', $flashMsg);
        return $contrats;
    }
    

    /**
     * @Route("/gestion-etudiants/telecharger/pdf/liste/etudiants/suivent/matiere/{slug}/{slug_annee}", name="etudiant_suivent_matiere_pdf")
     * @Route("/gestion-etudiants/telecharger/pdf/liste/etudiants/suivent/matiere/classe/{slug}/{slug_annee}/{slugClasse}", name="etudiant_suivent_matiere_classe_pdf")
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function listeDesEtudiantsQuiSuiventLaMatierePDF(EC $ec, $slug_annee, ?Classe $classe=null, ExportUtils $exportUtils, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $isForNote = $withAnonymat = false;
        if ($request->get('anonymat')) {
            $withAnonymat = true;
        }
        $exam = $this->entityManagerInterface->getRepository(Examen::class)->findOneBy(['slug'=>$request->get('session')]);

        $annee = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findOneBy(['slug'=>$slug_annee]);
        if (!$annee) {
            throw new Exception("L'année académique $slug_annee n'existe pas", 1);
        }
        if (!$classe) {
            if ($withAnonymat && $exam) {
                $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsForECWidthAnonymats($annee, $ec, $exam);
            }else {
                $contrats = $this->getContrats($annee, $ec, $classe, $request->get('statut'), null, null, false, $request);
            }
        }else {
            if ($withAnonymat && $exam) {
                $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsClasseForECWidthAnonymats($annee, $ec, $classe, $exam);
            }else {
                $contrats = $this->getContrats($annee, $ec, $classe, $request->get('statut'), null, null, false, $request);
            }
            
        }
        
        if ($request->get('type') && $request->get('type') == 'releve-notes') {
            $isForNote = true;
        }

        $withNames = false;
        if ($request->get('withNames') == 'yes') {
            $withNames = true;
        }
        
        // dump($withAnonymat);die();
        $result = $exportUtils->genererPDFEtudiantsByEC($contrats, $annee, $ec, $classe, $isForNote, $withAnonymat, $exam, $withNames);
        return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @Route("/gestion-etudiants/etudiant/delete/{slugAnnee}/{idInscription}", name="etudiant_delete_inscription")
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("inscription", options={"mapping": {"idInscription": "id"}})
     */
    public function supprimerInscription(AnneeAcademique $annee, EtudiantInscris $inscription, ObjectManager $manager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived()) {
            $this->createNotFoundException("Les données de cette année académique sont en lecture seule !");
        }
        $isDeleted = false;
        $msg = "Impossible de supprimer cette inscription !";
        if ($inscription->getAnneeAcademique()->getId() == $annee->getId()) {
            $etudiant = $inscription->getEtudiant();
            foreach($inscription->getContrats() as $c) {
                $manager->remove($c);
            }
            $manager->remove($inscription);
            if ($etudiant->getEtudiantInscris()->isEmpty()) {
                $manager->remove($etudiant);
            }
            $manager->flush();
            $isDeleted = true;
            $msg = "Inscription supprimée définitivement ! La page va être rechargée dans quelques secondes";
        }
        return new Response(json_encode(['isDeleted'=>$isDeleted, 'msg'=>$msg]), '200', ['Content-type'=>'appplication/json']);
    }

    /**
     * @Route("/gestion-etudiants/etudiant/edit-anonymats/{slugAnnee}/{slugExam}/{slugEC}", name="etudiant_saisir_anonymats")
     * 
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("ec", options={"mapping": {"slugEC": "slug"}})
     * @ParamConverter("exam", options={"mapping": {"slugExam": "slug"}})
     */
    public function saisirAnonymats(AnneeAcademique $annee, Examen $exam, EC $ec, Request $request, ObjectManager $manager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived()) {
            throw new Exception("Les données de cette année académique sont en lecture seule !", 1);
        }

        $anonymats = []; 
        $hasPost = false;
        if ($request->request->get('anonymats')) {
            $hasPost = true;
            $anonymats = (array) $request->request->get('anonymats');
            if ($this->validerOrSaveAnonymats($anonymats, $exam, $manager)) {
                $manager->flush();
                if ($request->isXmlHttpRequest()) {
                    return new Response(json_encode(['hasError'=>false, 'msg'=>'Anonymats enregistrés ! Vous pouvez quitter la page si vous le souhaiter ou continuer à les modifier', 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
                }else {
                    $this->addFlash('success', 'Anonymats enregistrés !');
                    return $this->redirectToRoute('etudiant_saisir_anonymats', ['slugAnnee'=>$annee->getSlug(), 'slugExam'=>$exam->getSlug(), 'slugEC'=>$ec->getSlug()]);
                }
            }else {
                if ($request->isXmlHttpRequest()) {
                    return new Response(json_encode(['hasError'=>true, 'msg'=>'Il y a une erreur dans cotre formulaire !', 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
                }else {
                    $this->addFlash('danger', 'Il y a une erreur dans votre formulaire !');
                    $anonymats = $request->request->get('anonymats');
                }
            }
        }
        
        $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsForEC($annee, $ec);
        
        if (!$hasPost) {
            if ($request->get('generate') && $request->get('generate') == 'auto') {
                $this->genererAutomatiquementLesAnonymats($contrats, $exam, $manager);
                $this->addFlash('success', 'Génération termiées !');
                return $this->redirectToRoute('etudiant_saisir_anonymats', ['slugAnnee'=>$annee->getSlug(), 'slugExam'=>$exam->getSlug(), 'slugEC'=>$ec->getSlug()]);
            }
        }

        return $this->render('etudiant/add_anonymat.html.twig', [
            'contrats' => $contrats,
            'annee' => $annee,
            'li' => 'ec',
            'ec' => $ec,
            'exam' => $exam,
            'anonymats' => $anonymats,
        ]);
    }


    /**
     * Cette fonction verifie que les anonymats saisies sont valides et les enregistre
     */
    private function validerOrSaveAnonymats(Array $anonymats, Examen $exam, ObjectManager $manager)
    {
        $isValid = true;
        foreach ($anonymats as $key => $value) {
            $contrat = $this->entityManagerInterface->getRepository(Contrat::class)->findOneBy(['id'=>$key]);
            if (empty(trim($value)) || strlen($value) != 5 || !$contrat) {
                $isValid = false;
                break;
            }else {
                $anonymat = $this->entityManagerInterface->getRepository(Anonymat::class)->findOneBy(['contrat'=>$contrat, 'examen'=>$exam]);
                if (!$anonymat) {
                    $anonymat = new Anonymat();
                }
                $anonymat->setContrat($contrat)->setExamen($exam)->setAnonymat(strtoupper($value));
                $manager->persist($anonymat);
            }
        }

        return $isValid;
    }

    private function genererAutomatiquementLesAnonymats($contrats, Examen $exam, ObjectManager $manager)
    {
        $anonymats = [];
        foreach ($contrats as $c) {
            $existe = true;
            while ($existe) {
                $anonymat = $this->genererAnonymat();
                if (!in_array($anonymat, $anonymats)) {
                    $existe = false;
                    $anonymats[] = $anonymat;
                    $a = $this->entityManagerInterface->getRepository(Anonymat::class)->findOneBy(['contrat'=>$c, 'examen'=>$exam]);
                    if (!$a) {
                        $a = new Anonymat();
                    }
                    $a->setContrat($c)->setExamen($exam)->setAnonymat(strtoupper($anonymat));
                    $manager->persist($a);
                }
            }
        }
        $manager->flush();
    }

    /**
     * Cette action du controller permet de generer la liste des etudiants en fonction des champs qui ont été cochées
     * 
     * @Route("/gestion-des-etudiants/listes/{slugAnnee}", name="etudant_list")
     * @Route("/gestion-des-etudiants/listes/{slugAnnee}/{slugFormation}", name="etudant_list_f")
     * @Route("/gestion-des-etudiants/listes/{slugAnnee}/{slugFormation}/{slugFiliere}", name="etudant_list_ff")
     * @Route("/gestion-des-etudiants/listes/{slugAnnee}/{slugFormation}/{slugFiliere}/{slugSpecialite}", name="etudant_list_fds")
     * @Route("/gestion-des-etudiants/listes/{slugAnnee}/{slugFormation}/{slugFiliere}/{slugSpecialite}/{slugClasse}", name="etudant_list_fdsc")
     * 
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("formation", options={"mapping": {"slugFormation": "slug"}})
     * @ParamConverter("filiere", options={"mapping": {"slugFiliere": "slug"}})
     * @ParamConverter("specialite", options={"mapping": {"slugSpecialite": "slug"}})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function showCustomListOfClasses(AnneeAcademique $annee, ?Formation $formation=null, ?Filiere $filiere=null, ?Specialite $specialite=null, ?Classe $classe=null, Request $request, ExportUtils $eu)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($request->request->get('generer') && $request->request->get('champs')) {
            $classes = [];
            if ($classe) {
                // $etudiants = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, NULL, $formation, $filiere, $specialite, $classe);
                $classes[] = $classe;
            }elseif ($specialite) {
                foreach ($specialite->getClasses() as $cl) {
                    if (!$formation || $formation->getId() == $cl->getFormation()->getId()) {
                        $classes[] = $cl;
                    }
                }
            }elseif ($filiere) {
                foreach ($filiere->getSpecialites() as $sp) {
                    foreach ($sp->getClasses() as $cl) {
                        if (!$formation || $formation->getId() == $cl->getFormation()->getId()) {
                            $classes[] = $cl;
                        }
                    }
                }
            }elseif ($formation) {
                $classes = $formation->getClasses();
            }else {
                $classes = $this->entityManagerInterface->getRepository(Classe::class)->findBy([], ['specialite'=>'ASC', 'formation'=>'ASC', 'niveau'=>'ASC']);
            }

            if (empty($classes)) {
                $this->addFlash('warning', "Aucune classe trouvée !");
            }else {
                $result = $eu->doExportation(['classes'=>$classes, 'champs'=>$request->request->get('champs')], $annee, 'custom-xls-students', [], null, $this->entityManagerInterface);
                return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
            }
        }

        return $this->render('etudiant/list.html.twig', [
            'champs' => $eu->getChamps(),
            'li' => $this->link,
            'formations' => $this->entityManagerInterface->getRepository(Formation::class)->findBy([], ['name'=>'ASC']),
            'filieres' => $this->entityManagerInterface->getRepository(Filiere::class)->findBy([], ['name'=>'ASC']),
            'annee' => $annee,
            
        ]);
    }

    private function getRandomCar()
	{
		$str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		return $str[rand(0, 25)];
	}

	private function genererAnonymat(){
        $str = "QWERTYUIOPLKJHGFDSAZXCVBNM1234567890"; $cles = array();
        srand((double)microtime()*1000000);
        $item="";
        for ($i=0; $i<5 ; $i++) {
            $j = rand()%strlen($str);
            $item .= $str[$j];
        }
        return $item;
    }
    
}
