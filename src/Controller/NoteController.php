<?php

namespace App\Controller;

use App\Entity\EC;
use App\Entity\Classe;
use App\Entity\Examen;
use App\Entity\Contrat;
use App\Entity\Filiere;
use App\Entity\ECModule;
use App\Entity\Etudiant;
use App\Entity\Formation;
use App\Entity\Specialite;
use App\Service\ExportUtils;
use App\Entity\MatiereASaisir;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use App\Service\ReleveNoteUtils;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * - Saisie des notes d'une matiere pour tous les étudiants qui font cette matière (OK)
 * - Saisie des notes d'une matière pour les élèves d'une classe donnée (OK)
 * - Saises des notes d'un étudiant donné pour tous ces contrats
 * - Afficher les notes d'une matière données
 * - Afficher les notes d'une classe pour une matière
 * - Afficher les notes d'un étudiant donné
 */
class NoteController extends AbstractController
{
    private $link = 'note';

    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @Route("/gestions-notes", name="note")
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('note/index.html.twig', [
            'li' => $this->link,
            'classes' => $this->entityManagerInterface->getRepository(Classe::class)->findBy([], ['nom' => 'ASC', 'specialite'=>'ASC']),

        ]);
    }

    /**
     * @Route("/gestion-notes/afficher/notes/matiere/{slugEC}/{slugAnnee}/page-{page}", name="note_show_notes_ec")
     * @Route("/gestion-notes/afficher/notes/matiere/{slugEC}/{slugAnnee}/page-{page}/{slugExamen}", name="note_show_notes_ec_examen")
     *
     *  @ParamConverter("ec", options={"mapping": {"slugEC": "slug"}})
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("examen", options={"mapping": {"slugExamen": "slug"}})
     */
    public function afficherNotesMatiere(EC $ec, AnneeAcademique $annee, int $page, ?Examen $examen=null, Request $request, ExportUtils $eu)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($page <= 0) {
            throw $this->createNotFoundException("La page $page n'existe pas !");
        }
        $classe = null;
        if ($request->get('classe')) {
            $classe = $this->entityManagerInterface->getRepository(Classe::class)->findOneBy(['slug'=>$request->get('classe')]);
        }
        
        $nbResultatPerPage = 15;
        $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsForEC($annee, $ec);
        $start = ($page-1)*$nbResultatPerPage;
        $nbPages = (int) ((count($contrats)/$nbResultatPerPage) + 1);
        $pages = [];
        if ($nbPages > 1) {
            for ($i=1; $i <= $nbPages; $i++) { 
                $pages[] = $i;
            }
        }

        if ($classe) {
            $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsClasseForEC($annee, $ec, $classe);
            $ecms = $this->entityManagerInterface->getRepository(ECModule::class)->findActualECModulesByClasse($annee, $classe);
        }else{
            $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsForEC($annee, $ec, null, 0);
            $ecms = $this->entityManagerInterface->getRepository(ECModule::class)->findActualECModulesByYear($annee);
        }
        // ( examen.type == 'R' and ( not c.isValidated or config.isRattrapageSurToutesLesMatieres ) )
        if ($examen && $examen->getType() == 'R' && $examen->getCode() == 'SR') {
            $contratsRat = [];
            foreach ($contrats as $c) {
                if (!$c->getIsValidated() || $c->getNoteSR()) {
                    $contratsRat[] = $c;
                }
            }
            $contrats = $contratsRat;
        }

        if ($request->get('download') && $contrats) {
            $fileTitle = "NOTES DE ".$ec->getIntitule()." (".$ec->getCode().")";

            $data = ['contrats' => $contrats, 'noSessionTitle'=>true, 'fileTitle'=>$fileTitle,'isForNote'=>true, 'annee' => $annee, 'examen'=>$examen, 'classe'=>$classe, 'ec'=>$ec];
            $result = $eu->generateInPDF($data);
            return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
        }

        $route = !$examen ? 'note_show_notes_ec' : 'note_show_notes_ec_examen';
        $parameters = ['slugEC'=>$ec->getSlug(), 'page'=>1, 'slugAnnee'=>$annee->getSlug(), 'download'=>true];
        if ($examen) {
            $parameters['slugExamen'] = $examen->getSlug();
        }
        if ($classe) {
            $parameters['classe'] = $classe->getSlug();
        }
        $downloadUrl = $this->generateUrl($route, $parameters);

        return $this->render('note/show_notes_ec.html.twig', [
            'contrats' => $contrats,
            'ec' => $ec,
            'annee' => $annee,
            'examen' => $examen,
            'li' => $this->link,
            'pages' => $pages,
            'currentPage' => $page,
            'nbPages' => $nbPages,
            'i' => (($page-1)*$nbResultatPerPage)+1,
            'examens' => $this->entityManagerInterface->getRepository(Examen::class)->findAll(),
            'classe' => $classe,
            'ecms' => $ecms,
            'downloadUrl' => $downloadUrl,
        ]);
    }

    /**
     * @Route("/gestion-notes/afficher/notes/classe/{slugClasse}/{slugAnnee}", name="note_show_notes_classe")
     * @Route("/gestion-notes/afficher/notes/classe/{slugClasse}/{slugAnnee}/{slugEC}", name="note_show_notes_classe_ec")
     * @ParamConverter("ec", options={"mapping": {"slugEC": "slug"}})
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function afficherNotesClasse(Classe $classe, AnneeAcademique $annee, EC $ec)
    {

    }

    /**
     * @Route("/gestion-notes/afficher/notes/etudiant/{slugAnnee}/{matricule}", name="note_show_notes_etudiant")
     * @Route("/gestion-notes/afficher/notes/etudiant/{slugAnnee}/{matricule}/{slugExamen}", name="note_show_notes_etudiant_examen")
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("examen", options={"mapping": {"slugExamen": "slug"}})
     */
    public function afficherNotesEtudiant(AnneeAcademique $annee, Etudiant $etudiant, ?Examen $examen=null)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $inscription = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findOneBy(['anneeAcademique'=>$annee, 'etudiant'=>$etudiant]);
        if (!$inscription) {
            $this->addFlash('danger', "Impossible de trouver l'étudiant indiqué !");
            return $this->redirectToRoute('etudiants');
        }

        return $this->render('note/afficher_notes_etudiant.html.twig', [
            'inscription' => $inscription,
            'annee' => $annee,
            'examen' => $examen,
            'examens' => $this->entityManagerInterface->getRepository(Examen::class)->findAll(),
            'li' => $this->link,
        ]);
    }

    /**
     * @Route("/gestions-notes/saisie/{slugAnnee}/{slugEC}/{slugExamen}", name="note_saisie_note_ec")
     * @Route("/gestions-notes/saisie/{slugAnnee}/{slugEC}/{slugExamen}/{slugClasse}", name="note_saisie_note_ec_classe")
     * @Route("/gestions-notes/saisie-anonymate/{slugAnnee}/{slugEC}/{slugExamen}/{slugClasse}/{withAnonymat}", name="note_saisie_note_ec_classe_anonymat")
     * 
     * @ParamConverter("ec", options={"mapping": {"slugEC": "slug"}})
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("examen", options={"mapping": {"slugExamen": "slug"}})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function saisirNotesEC(AnneeAcademique $annee, EC $ec, Examen $examen, ?Classe $classe=null, ?bool $withAnonymat=null, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            throw new Exception("Opération impossible ! Les données de cette année sont en lecture seule.", 1);
        }

        $this->denyAccessUnlessGranted('ROLE_SAISIE_NOTES');

        if ($this->isGranted('ROLE_SAISIE_NOTES') && !$this->isGranted('ROLE_NOTE_MANAGER')) {
            $MS = $this->entityManagerInterface->getRepository(MatiereASaisir::class)->findBy(['examen'=>$examen, 'user'=>$this->getUser(), 'anneeAcademique'=>$annee]);
            $existe = false;
            foreach ($MS as $ms) {
                if ($ms->getEcModule()->getEc()->getId() == $ec->getId()) {
                    $existe = true;
                    break;
                }
            }
            if (!$existe) {
                $this->denyAccessUnlessGranted('ROLE_INTRUS');
            }
        }

        $errorData = false;
        $oldNotes = null;

        if ($request->request->get('etudiant')) {
            $verificationResult = $this->verifierNotes((array)$request->request->get('etudiant'));
            if ($verificationResult['isValid']) {
                $this->saveNotes($examen, $verificationResult['notes']);
                $this->entityManagerInterface->flush();
                if ($request->isXmlHttpRequest()) {
                    return new Response(json_encode(['hasError'=>false, 'msg'=>'Les notes ont été enregistrées ! Vous pouvez quitter la page si vous le souhaiter ou continuer à les modifier', 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
                }
                $this->addFlash('success', "Les notes ont été enregistrées !");
                return $this->redirectToRoute('note_show_notes_ec_examen', [
                    'slugEC' => $ec->getSlug(),
                    'slugAnnee' => $annee->getSlug(),
                    'slugExamen' => $examen->getSlug(),
                    'page' => 1,
                ]);
            }
            else {
                $oldNotes = $verificationResult['notes'];
                $errorData = true;
                if ($request->isXmlHttpRequest()) {
                    return new Response(json_encode(['hasError'=>true, 'msg'=>'Des erreurs existent dans votre formulaire', 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
                }
            }
        }

        if ($classe) {
            if ($withAnonymat) {
                $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsClasseForECWidthAnonymats($annee, $ec, $classe, $examen);
            }else {
                $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsClasseForEC($annee, $ec, $classe);
            }
        }
        else {
            if ($withAnonymat) {
                $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsForECWidthAnonymats($annee, $ec, $examen);
            }else {
                $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsForEC($annee, $ec);
            }
        }

        if ($this->isGranted('ROLE_SUPER_USER')) {
            if ($classe) {
                $matieresSaisir = $this->entityManagerInterface->getRepository(ECModule::class)->findActualECModulesByClasse($annee, $classe);
            }else{
                $matieresSaisir = $this->entityManagerInterface->getRepository(ECModule::class)->findBy([], [], 25);
            }
            
        }else {
            $matieresSaisir = $this->entityManagerInterface->getRepository(MatiereASaisir::class)->findBy(['examen'=>$examen, 'user'=>$this->getUser(), 'anneeAcademique'=>$annee]);
        }

        return $this->render('note/saisir.html.twig', [
            'contrats' => $contrats,
            'annee' => $annee,
            'ec' => $ec,
            'examen' => $examen,
            'classe' => $classe,
            'li' => $this->link,
            'errorData' => $errorData,
            'oldNotes' => $oldNotes,
            'withAnonymat' => $withAnonymat,
            'config' => $annee->getConfiguration(),
            'matieresSaisir' => $matieresSaisir,

        ]);
    }

    /**
     * @Route("/gestion-notes/etudiants/saisie/notes/{slugAnnee}/{slugExamen}/{matricule}", name="note_saisie_notes_etudiant")
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("examen", options={"mapping": {"slugExamen": "slug"}})
     */
    public function saisirNotesEtudiant(AnneeAcademique $annee, Examen $examen, Etudiant $etudiant, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            throw new Exception("Opération impossible ! Les données de cette année sont en lecture seule.", 1);
        }

        $inscription = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findOneBy(['anneeAcademique'=>$annee, 'etudiant'=>$etudiant]);
        if (!$inscription) {
            throw new Exception("Impossible de trouver l'étudiant indiqué !", 1);
        }

        $errorData = false;
        $oldNotes = null;
        if ($request->request->get('etudiant')) {
            $verificationResult = $this->verifierNotes((array) $request->request->get('etudiant'));
            if ($verificationResult['isValid']) {
                $this->saveNotes($examen, $verificationResult['notes']);
                $this->entityManagerInterface->flush();
                $this->addFlash('success', "Les notes ont été enregistrées !");
                return $this->redirectToRoute('note_show_notes_etudiant_examen', [
                    'matricule' => $etudiant->getMatricule(),
                    'slugAnnee' => $annee->getSlug(),
                    'slugExamen' => $examen->getSlug(),
                ]);
            }
            else {
                $oldNotes = $verificationResult['notes'];
                $errorData = true;
            }
        }

        return $this->render('note/saisie_notes_etudiant.html.twig', [
            'examen' => $examen,
            'annee' => $annee,
            'inscription' => $inscription,
            'li' => $this->link,
            'errorData' => $errorData,
            'oldNotes' => $oldNotes,

        ]);
    }

    /**
     * @Route("/gestion-notes/classes/{slug}", name="note_show_classes")
     */
    public function afficherLaListeDesSallesDeClassePourLaSaisieDesNotes(AnneeAcademique $annee)
    { 
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('crud/filiere/specialite/classe/index.html.twig', [
            'classes' => $this->entityManagerInterface->getRepository(Classe::class)->findBy([], ['nom'=>'ASC']),
            'li' => $this->link,
            'annee' => $annee,
            'isForNoteController' => true,
            'examens' => $this->entityManagerInterface->getRepository(Examen::class)->findBy([], ['code'=>'ASC']),

        ]);
    }

    /**
     * @Route("/gestion-notes/lancer-calcul/{slug}", name="note_calculs_index")
     */
    public function lancerCalculsIndex(AnneeAcademique $annee)
    {
        if ($annee->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            throw new Exception("Opération impossible ! Les données de cette année sont en lecture seule.", 1);
        }

        return $this->render('note/calculs.html.twig', [
            'annee' => $annee,
            'li' => $this->link,
            'classes' => $this->entityManagerInterface->getRepository(Classe::class)->findBy([], ['specialite'=>'ASC']),
            'exams' => $this->entityManagerInterface->getRepository(Examen::class)->findBy([], ['code'=>'ASC']),

        ]);
    }

    /**
     * @Route("/find-liste-etudiants/{slugAnnee}", name="note_find_all_etudiants")
     * @Route("/find-liste-etudiants/{slugAnnee}/{niveau}", name="note_find_all_etudiants_niveau")
     * @Route("/find-liste-etudiants/{slugAnnee}/{niveau}/{slugClasse}", name="note_find_all_etudiants_classe")
     * 
     *  @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     *  @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function findEtudiants(AnneeAcademique $annee, ?string $niveau=null, ?Classe $classe=null, SerializerInterface $si)
    {
        $maxResults = 100;
        $etudiants = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, null, $formation=NULL, $filiere=NULL, $specialite=NULL, $classe, $status=NULL, $niveau);
        $nbE = count($etudiants);
        if ($nbE%$maxResults == 0) {
            $nbPages = $nbE/$maxResults;
        }else {
            $nbPages = (int) ($nbE/$maxResults) + 1;
        }

        $existe = $etudiants ? true : false;
        return new Response($si->serialize(['existe'=>$existe, 'nbPages'=>$nbPages, 'maxResults'=>$maxResults, 'etudiants'=>$etudiants], 'json', ['groups' => 'public']));
    }

    /**
     * @Route("/gestion-notes/lancer/calculs/{slugAnnee}", name="note_lancer_calculs")
     * @Route("/gestion-notes/lancer/calculs/{slugAnnee}/{slugExamen}/{semestre}/{niveau}", name="note_lancer_calculs_niveau")
     * @Route("/gestion-notes/lancer/calculs/{slugAnnee}/{slugExamen}/{semestre}/{niveau}/{slugClasse}", name="note_lancer_calculs_classe")
     * @Route("/gestion-notes/lancer/calculs/{slugAnnee}/{slugExamen}/{semestre}/{niveau}/{slugClasse}/{idIns}", name="note_lancer_calculs_note_etudiant")
     * 
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("examen", options={"mapping": {"slugExamen": "slug"}})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     * @ParamConverter("etudiant", options={"mapping": {"idIns": "id"}})
     */
    public function lancerLeCalculDesNotes(AnneeAcademique $annee, Examen $examen, $semestre,  ?string $niveau=null, ?Classe $classe=null, ?EtudiantInscris $etudiant=null, Request $request, ReleveNoteUtils $rn)
    {
        set_time_limit(0);

        // dd($etudiant);
        
        if ($annee->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            $msg = "Opération impossible ! Les données de cette année académique sont en lecture seule !";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            $this->addFlash('danger', $msg);
            return $this->redirectToRoute('note');
        }

        $maxResults = $request->get('max');
        if (!is_numeric($maxResults)) {
            $maxResults = 100;
        }
        $page = $request->get('page');
        if ($page == null) {
            $page = 1;
        }
        $start = ($page-1)*$maxResults;

        if ($etudiant) {
            $etudiants[] = $etudiant;
        }else {
            $etudiants = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants(annee:$annee, start:$start, maxResults:$maxResults, formation:NULL, filiere:NULL, specialite:NULL, classe:$classe, status:NULL, niveau:$niveau);
        }

        if (!$etudiants) {
            $msg = "Aucun étudiant trouvé !";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            $this->addFlash('warning', $msg);
            return $this->redirectToRoute('note');
        }

        $pourcentageCC = $examen->getPourcentageCC();
        $pourcentageSN = $examen->getPourcentage();
        

        if (!$pourcentageCC || !$pourcentageSN) {
            $msg = "Les pourcentages des CC et de l'examen doivent être definis !";
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>true, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }
            $this->addFlash('warning', $msg);
            return $this->redirectToRoute('note');
        }

        foreach ($etudiants as $e) {
            $rn->commencerProcessusDeCalcul($examen, [$e], $annee, $semestre, $pourcentageCC, $pourcentageSN, $this->entityManagerInterface, $this->entityManagerInterface);
        }

        $msg = "Calculs terminés !";
        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>false, 'msg'=>$msg, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
        }
        $this->addFlash('success', $msg);
        return $this->redirectToRoute('note');
    }


    /**
     * Cette fonction permet de verifier que le formulaire de saisie des notes est correctement remplie
     */
    private function verifierNotes(array $notes)
    {
        $isValid = true;
        $newNotes = [];
        foreach ($notes as $n) {
            if (!empty(trim($n['note'])) && (!is_numeric($n['note']) || $n['note'] > 20 || $n['note'] < 0)) {
                $n['error'] = "La note doit être un nombre inférieur ou égale à 20 !";
                $isValid = false;
            }
            $newNotes[] = $n;
        }
        return ['isValid' => $isValid, 'notes' => $newNotes];
    }

    /**
     * Cette fonction permet de sauvegarder les notes dans la base de données
     */
    private function saveNotes(Examen $examen, array $notes)
    {
        foreach ($notes as $n) {
            $contrat = $this->entityManagerInterface->getRepository(Contrat::class)->find($n['contrat']);
            $note = is_numeric(trim($n['note'])) ? trim($n['note']) : NULL;
            if ($contrat) {
                switch (\strtoupper($examen->getCode())) {
                    case 'SN':
                        $contrat->setNoteSN($note)->setIsDataHasChange(true);
                        break;
                    case 'CC':
                        $contrat->setNoteCC($note)->setIsDataHasChange(true);
                        break;
                    case 'SR':
                        $contrat->setNoteSR($note)->setIsDataHasChange(true);
                        break;
                    case 'TP':
                        $contrat->setNoteTP($note)->setIsDataHasChange(true);
                        break;
                    case 'TPE':
                        $contrat->setNoteTPE($note)->setIsDataHasChange(true);
                        break;
                }
            }
        }
    }

    /*
        ===================================================================
        |                                                                 |
        |                     GESTION DES DELIBERATIONS                   |
        |                                                                 |
        ===================================================================
    */

    /**
     * @Route("/gestion-notes/delibererations/{slug}", name="note_deliberation_index")
     */
    public function delibererations(AnneeAcademique $annee, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($annee->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            throw new Exception("Opération impossible ! Les données de cette année sont en lecture seule.", 1);
        }

        if ($request->request->get('formDeliberations') && $request->request->get('deliberationParameters')) {
            $result = $this->startDeliberations($annee, $request->request->get('deliberationParameters'));
            if (!$result['hasError']) {
                
                $this->entityManagerInterface->flush();
            }

            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>$result['hasError'], 'msg'=>$result['msg'], 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }

            if ($result['hasError']) {
                $this->addFlash('warning', $result['msg']);
            }else {
                $this->addFlash('success', 'Critères de delirations appliqué ! Pensez à relancer les calculs une fois terminé.');
            }
            
            return $this->redirectToRoute('note_deliberation_index', ['slug'=>$annee->getSlug()]);
        }
        
        return $this->render('note/deliberation.html.twig', [
            'semestres' => [1, 2],
            'exams' => $this->entityManagerInterface->getRepository(Examen::class)->findBy([], ['code'=>'ASC']),
            'formations' => $this->entityManagerInterface->getRepository(Formation::class)->findBy([], ['name'=>'ASC']),
            'niveaux' => [1, 2, 3, 4, 5],
            'filieres' => $this->entityManagerInterface->getRepository(Filiere::class)->findBy([], ['name'=>'ASC']),
            'li' => $this->link,
            'annee' => $annee,
        ]);
    }

    private function startDeliberations(AnneeAcademique $annee, $parameters)
    {
        // dump($parameters); die();
        $parameters = json_decode(json_encode($parameters));
        $errorMsg = '';
        $hasError = false;
        if (!isset($parameters->session)) {
            $errorMsg .= "Vous devez precisez la session";
            $hasError = true;
        }
        $niveau = null;
        if (is_numeric($parameters->niveau)) {
            $niveau = $parameters->niveau;
        }
        $session = $this->entityManagerInterface->getRepository(Examen::class)->findOneBy(['slug'=>$parameters->session]);
        if (!$session) {
            $errorMsg .= "La session specifiée n'existe pas !";
            $hasError = true;
        }

        $formation = $this->entityManagerInterface->getRepository(Formation::class)->findOneBy(['slug'=>$parameters->formation]);
        $filiere = $this->entityManagerInterface->getRepository(Filiere::class)->findOneBy(['slug'=>$parameters->filiere]);
        $specialite = null;
        if (!empty($parameters->specialite)) {
            $specialite = $this->entityManagerInterface->getRepository(Specialite::class)->findOneBy(['slug'=>$parameters->specialite]);
        }
        $classe = null;
        if (!empty($parameters->classe)) {
            $classe = $this->entityManagerInterface->getRepository(Classe::class)->findOneBy(['slug'=>$parameters->classe]);
        }

        $etudiants = [];
        // dump($parameters); die();

        if (isset($parameters->etudiants) && !empty($parameters->etudiants)) {
            foreach ($parameters->etudiants as $key => $id) {
                $ins = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->find($id);
                if ($ins) {
                    $etudiants[] = $ins;
                }
            }
            // dump($parameters); die();
        }else {
            $inscris = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, NULL, $formation, $filiere, $specialite, $classe);
            // dump($inscris); die();
            foreach ($inscris as $e) {
                if (empty($niveau) || $niveau == $e->getClasse()->getNiveau()) {
                    $etudiants[] = $e;
                }
            }
        }

        // dump($etudiants);die();

        if (empty($etudiants)) {
            $errorMsg .= "Aucun etudiant trouvé !";
            $hasError = true;
        }

        if (!$hasError) {
            if (!empty($parameters->ecsModules)) {
                // dump($parameters->ecsModules); die();
                foreach ($parameters->ecsModules as $key => $id) {
                    $ecm = $this->entityManagerInterface->getRepository(ECModule::class)->find($id);
                    if ($ecm) {
                        $this->delibererEc($ecm, $etudiants, $parameters->noteMin, $parameters->noteMax, $parameters->newNote, $session, $parameters->semestre);
                    }
                }
            } else {
                $this->delibererTousLescontrats($etudiants, $parameters->noteMin, $parameters->noteMax, $parameters->newNote, $session, $parameters->semestre);
            }
        }

        return ['msg'=>$errorMsg, 'hasError'=>$hasError];
    }

    private function delibererTousLescontrats(Array $etudiants, $noteMin, $noteMax, $newVal, Examen $exam, $semestre)
    {
        foreach ($etudiants as $et) {
            foreach ($et->getContrats() as $contrat) {
                // Pour appliquer la deliberation, l'etudiant doit avoir la note de cc et la note de session normale
                if ($contrat->getNoteCC() && $contrat->getNoteSN()) {
                    $this->calulerDeliberationValue($contrat, $exam, $noteMin, $noteMax, $newVal,$semestre);
                }    
            }
        }
    }
    
    private function delibererEc(ECModule $ecm, Array $etudiants, $noteMin, $noteMax, $newVal, Examen $exam, $semestre)
    {
        foreach ($etudiants as $etudiant) {
            $contrat = $this->entityManagerInterface->getRepository(Contrat::class)->findOneBy(['etudiantInscris'=>$etudiant, 'ecModule'=>$ecm]);
            if ($contrat->getNoteCC() && $contrat->getNoteSN()) {
                $this->calulerDeliberationValue($contrat, $exam, $noteMin, $noteMax, $newVal, $semestre);
            }
        }
    }

    private function calulerDeliberationValue(Contrat $contrat=null, Examen $exam, $noteMin, $noteMax, $newVal, $semestre)
    {
        if ($contrat && $contrat->getMoyDefinitive() >= $noteMin && $contrat->getMoyDefinitive() < $noteMax && $semestre == $contrat->getEcModule()->getSemestre()) { 
            
            $noteJury = ((100*$newVal) - ($contrat->getNoteCC()*$exam->getPourcentageCC()))/$exam->getPourcentage();
            $sv = $exam->getCode() == 'SN' ? 'N' : 'R';
            $noteJury = number_format($noteJury, 2);
            $contrat->setNoteDefinitive($noteJury)
                    ->setNoteJury($noteJury)
                    ->setMoyApresJury($newVal)
                    ->setMoyDefinitive($newVal)
                    ->setSessionValidation($sv)
                    ->setIsDataHasChange(true);
        }
    }

    /**
     * Cette function permet d'afficher le releve de note d'un etudiant pour une annee académique
     * donnée. 
     * 
     * @Route("/gestion-notes/releve/show/{slugAnnee}/{slugClasse}", name="note_releves_note_classe")
     * @Route("/gestion-notes/releve/show/{slugAnnee}/{slugClasse}/{matriculeEtudiant}", name="note_releve_note_etudiant")
     * 
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("etudiant", options={"mapping": {"matriculeEtudiant": "matricule"}})
     */
    public function afficherReleverNotes(AnneeAcademique $annee, Classe $classe, ?Etudiant $etudiant=null, Request $request, ReleveNoteUtils $rn)
    {
        // echo memory_get_usage(); die();
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $inscris = []; $isEmpty = false;
        $inscri = null;
        $pages = [];
        $nbPages = 1;
        $page = 1;
        if ($etudiant) {
            $inscri = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findOneBy(['anneeAcademique'=>$annee, 'classe'=>$classe, 'etudiant'=>$etudiant]);
            if ($inscri) {
                $inscris[] = $inscri;
            }
        }else {
            $maxResults = 30;
            if ($request->get('page')) {
                $page =  $request->get('page');
            }
            
            $start = 0;
            if ($page && $page > 0) {
                $start = ($page-1)*$maxResults;
            }
            if (!($request->get('file') && $request->get('file')=='pdf')) {
                $nbPages = (int) (count($this->entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, NULL, $classe->getFormation(), $classe->getSpecialite()->getFiliere(), $classe->getSpecialite(), $classe))/$maxResults)+1;
                if ($nbPages > 1) {
                    for ($i=1; $i <= $nbPages; $i++) { 
                        $pages[] = $i;
                    }
                }
            }
            
            foreach ($this->entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, $start, $maxResults, NULL, NULL, NULL, $classe) as $ins) {
                // if ($ins->getCanTakeReleve()) {
                    $inscris[] = $ins;
                // }
            }
            
        }

        if (empty($inscris)) {
            $this->addFlash('warning', "Aucun etudiant trouvé !");
            $isEmpty = true;
        }

        $semestre = $request->get('semestre');

        if ($inscris && $request->get('file') && $request->get('file')=='pdf') {
            $result = $rn->genererReleves($inscris, $annee, true, 'releves.html.twig', $semestre);
            return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
        }

        $filePath = 'note/releve.html.twig';
        if ($etudiant) {
            $filePath = 'etudiant/profil/releve.html.twig';
        }

        return $this->render($filePath, [
            'releves' => $rn->genererReleves($inscris, $annee, false, 'releves.html.twig', $semestre),
            'li' => $this->link,
            'classe' => $classe,
            'annee' => $annee,
            'isEmpty' => $isEmpty,
            'semestre' => $semestre,
            'etudiant' => $etudiant,
            'inscription' => $inscri,
            'pages' => $pages,
            'currentPage' => $page,
            'nbPages' => $nbPages,
        ]);
        
    }

    /**
     * Cette action est appele pendant la saisie des notes pour enregistrer automatiquement les notes saisies
     * lorsque l'utilisateur quitte la zone de saisie. Cette fonction sera appelée uniquement en ajax
     * 
     * @Route("saisie-notes/auto-save/{contratId}/{examenId}/{noteValue}", name="note_auto_save_on_blur")
     * @Route("saisie-notes/auto-save/{contratId}/{examenId}", name="note_auto_save_on_blur0")
     * 
     * @ParamConverter("contrat", options={"mapping": {"contratId": "id"}})
     * @ParamConverter("examen", options={"mapping": {"examenId": "id"}})
     */
    public function autoSaveNotes(Contrat $contrat, Examen $examen, ?float $noteValue=null, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->createNotFoundException();
        }

        $hasError = false;

        if ($noteValue && (!is_numeric($noteValue) || $noteValue < 0 || $noteValue > 20)) {
            $hasError = true;
        }

        if (!$hasError) {
            switch (strtoupper($examen->getCode())) {
                case 'CC':
                    $contrat->setNoteCC($noteValue)->setIsDataHasChange(true);
                    break;
                case 'SN':
                    $contrat->setNoteSN($noteValue)->setIsDataHasChange(true);
                    break;
                case 'SR':
                    $contrat->setNoteSR($noteValue)->setIsDataHasChange(true);
                    
                    break;
                
                default:
                    $hasError = true;
                    break;
            }
            $this->entityManagerInterface->flush();
        }

        return new Response(json_encode(['hasError'=>$hasError]));
    }

    /**
     * @Route("/test", name="test_route")
     */
    public function test()
    {
        foreach ($variable = ['','A','B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q','R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'] as $key => $value) {
            $var = $variable; unset($var[0]);
            foreach ($var as $key => $val) {
                echo '"'.trim($value.$val).'",';
            }
        }
        return new Response();
    }
}
