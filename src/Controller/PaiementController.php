<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Tranche;
use App\Entity\Etudiant;
use App\Entity\Paiement;
use App\Service\ExportUtils;
use App\Service\ImportUtils;
use App\Entity\PaiementClasse;
use App\Entity\TypeDePaiement;
use App\Form\TypePaiementType;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use App\Form\UploadProgramType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class PaiementController extends AbstractController
{
    private $link = 'paie';

    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }
    
    /**
     * @Route("/paiements", name="paiement")
     */
    public function index(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $searchText = $request->get('searchText');

        return $this->render('paiement/index.html.twig', [
            'typePaiements' => $this->entityManagerInterface->getRepository(TypeDePaiement::class)->findAll(),
            'li' => $this->link,
            'searchText' => $searchText,
            'annee' => $request->getSession()->get('annee'),

        ]);
    }

    /**
     * @Route("/paiements/add", name="paiement_add")
     * 
     */
    public function addTypePaiement(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $typePaiement = new TypeDePaiement();
        $editionMode = false;
        $form = $this->createForm(TypePaiementType::class, $typePaiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // die();
            foreach ($typePaiement->getPaiementClasses() as $pc) {
                foreach ($pc->classes as $classe) {
                    $pcl = new PaiementClasse();
                    $pcl->setClasse($classe)
                        ->setMontant($pc->getMontant())
                        ->setIsObligatoire($pc->getIsObligatoire());
                    $pc->setClasse($classe);
                    foreach ($pc->getTranches() as $t) {
                        $tranche = new Tranche();
                        $tranche->setDenomination($t->getDenomination())
                                ->setMontant($t->getMontant());
                        $pcl->addTranch($tranche);
                    }
                    $typePaiement->addPaiementClass($pcl);
                    $typePaiement->removePaiementClass($pc);
                }
            }
            $this->entityManagerInterface->persist($typePaiement);
            $this->entityManagerInterface->flush();

            return $this->redirectToRoute('paiement');
        }

        return $this->render('paiement/add_type_paiement.html.twig', [
            'li' => $this->link,
            'form' => $form->createView(),
            'editionMode' => $editionMode,
            'classes' => $this->entityManagerInterface->getRepository(Classe::class)->findAll(),
            'tp' => $typePaiement,
        ]);
    }

    /**
     * @Route("/paiements/edit/{slug}", name="paiement_edit")
     */
    public function editTypePaiement(TypeDePaiement $typePaiement, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $editionMode = true;
        $form = $this->createForm(TypePaiementType::class, $typePaiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($typePaiement->getPaiementClasses() as $pc) {
                foreach ($pc->classes as $classe) {
                    $pcl = $this->entityManagerInterface->getRepository(PaiementClasse::class)->findOneBy(['classe'=>$classe, 'typeDePaiement'=>$typePaiement]);
                    if (!$pcl) {
                        $pcl = new PaiementClasse();
                    }
                    $pcl->setClasse($classe)
                        ->setMontant($pc->getMontant())
                        ->setIsObligatoire($pc->getIsObligatoire());
                    foreach ($pc->getTranches() as $t) {
                        $tranche = $this->entityManagerInterface->getRepository(Tranche::class)->findOneBy(['paiementClasse'=>$pc, 'denomination'=>$t->getDenomination()]);
                        if (!$tranche) {
                            $tranche = new Tranche();
                        }
                        $tranche->setDenomination($t->getDenomination())
                                ->setMontant($t->getMontant());
                        $pcl->addTranch($tranche);
                    }
                    $typePaiement->addPaiementClass($pcl);
                    // $typePaiement->removePaiementClass($pc);
                }
            }
            $this->entityManagerInterface->flush();

            return $this->redirectToRoute('paiement');
        }

        return $this->render('paiement/add_type_paiement.html.twig', [
            'li' => $this->link,
            'form' => $form->createView(),
            'editionMode' => $editionMode,
            'classes' => $this->entityManagerInterface->getRepository(Classe::class)->findAll(),
            'tp' => $typePaiement,
        ]);
    }

    /**
     * @Route("/paiements/{slugTP}/classes", name="paiement_classes_concernees")
     * @Route("/gestion-paiements/importer-quitus/{slugTP}/{anneeSlug}", name="paiement_importer_quitus")
     * 
     * @ParamConverter("tp", options={"mapping": {"slugTP": "slug"}})
     * @ParamConverter("annee", options={"mapping": {"anneeSlug": "slug"}})
     */
    public function showClasses(TypeDePaiement $tp, ?AnneeAcademique $annee=null, Request $request, ImportUtils $iu)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $searchText = $request->get('searchText');

        $anneeSlug = $annee ? $annee->getSlug() : $request->getSession()->get('annee')->getSlug();

        $form = $this->createForm(UploadProgramType::class, null, [
            'action' => $this->generateUrl('paiement_importer_quitus', [
                'anneeSlug' => $anneeSlug,
                'slugTP' => $tp->getSlug(),
            ]),
            'attr' => [
                'class' => 'form-confirm-ajax-action',
            ]
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $flashAlert = $iu->doImportation($this->entityManagerInterface, $form, 'quitus', $request->getSession()->get('annee'), $this->getParameter('sample_directory'), $tp);
            $this->entityManagerInterface->flush();
            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode([]), 200, []);
            }

            $this->addFlash('success', $flashAlert);
            return $this->redirectToRoute('paiement_classes_concernees', ['slugTP'=>$tp->getSlug()]);
            
        }
        
        return $this->render('paiement/classes.html.twig', [
            'tp' => $tp,
            'li' => $this->link,
            'isFormClasse' => true,
            'form' => $form->createView(),
            'searchText' => $searchText,
            'annee' => $request->getSession()->get('annee'),
        ]);
    }

    /**
     * @Route("/paiements/{slugTP}/{anneeSlug}/{slugClasse}", name="paiement_etudiants")
     * @Route("/paiements/search-etudiant/{anneeSlug}", name="paiement_etudiants_search")
     * 
     * @ParamConverter("tp", options={"mapping": {"slugTP": "slug"}})
     * @ParamConverter("annee", options={"mapping": {"anneeSlug": "slug"}})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function showStudents(AnneeAcademique $annee, ?TypeDePaiement $tp=null, ?Classe $classe=null, Request $request, ExportUtils $eu)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $searchText = $request->get('search');
        if ($searchText) {
            $inscris = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->search($annee, $searchText);
        }else {
            $inscris = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, NULL, NULL, NULL, NULL, $classe);
        }

        

        $data = [
            'classe' => $classe,
            'li' => $this->link,
            'annee' => $annee,
            'isForStudent' => true,
            'inscris' => $inscris,
            'tp' => $tp,
            'searchText' => $searchText,
            'classe' => $classe,
            'etats' => true,
            'tp' => $tp,
            'pc' => $this->entityManagerInterface->getRepository(PaiementClasse::class)->findOneBy(['typeDePaiement'=>$tp, 'classe'=>$classe]),
            'inPDF' => $request->get('file') && in_array($request->get('file'), ['pdf', 'excel']) ? true : false,

        ];
        // die($request->get('file'));
        if ($request->get('file') && in_array($request->get('file'), ['pdf', 'excel'])) {
            $result = $eu->exportStudents($data, 'paiement/classes.html.twig');
            return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
        }

        if ($tp) {
            $form = $this->createForm(UploadProgramType::class, null, [
                'action' => $this->generateUrl('paiement_importer_quitus', [
                    'anneeSlug' => $annee->getSlug(),
                    'slugTP' => $tp->getSlug(),
                ]),
                'attr' => [
                    'class' => 'form-confirm-ajax-action',
                ]
            ]);
            $data['form'] = $form->createView();
        }

        return $this->render('paiement/classes.html.twig', $data);
    }

    /**
     * @Route("/paiements-etudiant/etats-paiement/{slug}/{matricule}", name="paiement_etat_etudiant")
     */
    public function afficherLEtatDesPaiementsDunEtudiant(AnneeAcademique $annee, Etudiant $etudiant)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $inscris = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findOneBy(['anneeAcademique'=>$annee, 'etudiant'=>$etudiant]);
        if (!$inscris) {
            $this->addFlash('warning', "Aucun étudiant ne correspond à ce matricule !");
            return $this->redirectToRoute('paiement');
        }

        return $this->render('paiement/etat_etudiant.html.twig', [
            'annee' => $annee,
            'inscription' => $inscris,
            'li' => $this->link,
        ]);
    }

    /**
     * @Route("/gestion-paiements/validation/{id}/{slugTranche}", name="paiement_validation")
     * 
     * @ParamConverter("tranche", options={"mapping": {"slugTranche": "slug"}})
     */
    public function validerPaiement(EtudiantInscris $inscris, Tranche $tranche, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($inscris->getAnneeAcademique()->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            throw new Exception("L'année ".$inscris->getAnneeAcademique()->getDenomination()." est en lecture seule !", 1);
        }

        if ($request->request->get('numero_quitus')) {
            $numeroQuitus = $request->request->get('numero_quitus');
            $paiement = $this->entityManagerInterface->getRepository(Paiement::class)->findOneBy(['numeroQuitus'=>$numeroQuitus, 'etudiantInscris'=>$inscris, 'tranche'=>$tranche]);
            if (!$paiement) {
                if ($request->isXmlHttpRequest()) {
                    return new Response(json_encode(['hasError'=>true, 'msg'=>'Le numero de quitus saisie n\'est pas valide !', 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
                }
                $this->addFlash("warning", 'Le numero de quitus saisie n\'est pas valide !');
                return $this->redirectToRoute('paiement_etat_etudiant', ['slug'=>$inscris->getAnneeAcademique()->getSlug(), 'matricule'=>$inscris->getEtudiant()->getMatricule()]);
            }
            // $paiement = new Paiement();
            // $paiement->setEtudiantInscris($inscris)
            //          ->setTranche($tranche)
            //          ->setIsPaied(true)
            //          ->setNumeroQuitus($numeroQuitus);
            // $objectManager->persist($paiement);

            $paiement->setIsPaied(true);
            $this->entityManagerInterface->flush();

            // On verifie le gar a fini tous ses paiements obligatoires
            $canTakeReleve = true;
            foreach ($inscris->getClasse()->getPaiementClasses() as $pc) {
                if ($pc->getIsObligatoire()) {
                    foreach ($pc->getTranches() as $tranche) {
                        if (!$this->entityManagerInterface->getRepository(Paiement::class)->findOneBy(['etudiantInscris'=>$inscris, 'tranche'=>$tranche, 'isPaied'=>true])) {
                            $canTakeReleve = false;
                        }
                    }
                }
            }
            $inscris->setCanTakeReleve($canTakeReleve);
            $this->entityManagerInterface->flush();

            if ($request->isXmlHttpRequest()) {
                return new Response(json_encode(['hasError'=>false, 'idPayment'=>$paiement->getId(), 'msg'=>'Paiement validé !', 'reloadWindow'=>true]), '200', ['Content-type'=>'appplication/json']);
            }
            $this->addFlash("success", 'Paiement validé !');
            return $this->redirectToRoute('paiement_etat_etudiant', ['slug'=>$inscris->getAnneeAcademique()->getSlug(), 'matricule'=>$inscris->getEtudiant()->getMatricule()]);
        }
        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>true, 'msg'=>'Un problème est survenu', 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
        }
        $this->addFlash("warning", 'Un problème est survenu !');
        return $this->redirectToRoute('paiement_etat_etudiant', ['slug'=>$inscris->getAnneeAcademique()->getSlug(), 'matricule'=>$inscris->getEtudiant()->getMatricule()]);
    }


    /**
     * @Route("/gestion-paiements/remove/{id}", name="paiement_remove")
     * @Route("/gestion-paiements/remove/", name="paiement_remove2")
     */
    public function annulerValidation(Paiement $paiement, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $inscris = $paiement->getEtudiantInscris();
        // $objectManager->remove($paiement);
        $paiement->setIsPaied(false);
        $this->entityManagerInterface->flush();

        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>false,'isDeleted'=>true, 'msg'=>'Validation annulée ! La page va être rechargée dans quelques instants.', 'reloadWindow'=>true]), '200', ['Content-type'=>'appplication/json']);
        }

        return $this->redirectToRoute('paiement_etat_etudiant', ['slug'=>$inscris->getAnneeAcademique()->getSlug(), 'matricule'=>$inscris->getEtudiant()->getMatricule()]);
    }

    /**
     * Cette fonction permet d'afficher la liste des etudiants qui
     *  - ont soldé un paiement donné
     *  - ont soldé une tranche donnée d'un paiement donné 
     *  - n'ont pas encore payé
     * 
     * @Route("/gestion-paiements/{anneeSlug}/{slugTP}/{slugClasse}/liste-des-etudiants", name="paiement_liste_etudiant_etat")
     * 
     * @ParamConverter("tp", options={"mapping": {"slugTP": "slug"}})
     * @ParamConverter("annee", options={"mapping": {"anneeSlug": "slug"}})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function afficherEtudiantsParEtatPaiement(AnneeAcademique $annee, TypeDePaiement $tp, Classe $classe, Request $request, ExportUtils $eu)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $tranche = $this->entityManagerInterface->getRepository(Tranche::class)->findOneBy(['slug'=>$request->get('tranche')]);
        $hasPaied = ($request->get('statut') && mb_strtolower($request->get('statut'), 'UTF8') == 'solde') ? true : false;

        if ($tranche && $tranche->getPaiementClasse()->getTypeDePaiement()->getId() != $tp->getId()) {
            die();
        }
        
        $etudiants = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, NULL, NULL, NULL, NULL, $classe);
        $etudiantsSoldes = [];
        $etudiantsInsolvables = [];
        if ($tranche) {
            foreach ($etudiants as $et) {
                if ($this->entityManagerInterface->getRepository(Paiement::class)->findOneBy(['etudiantInscris'=>$et, 'tranche'=>$tranche, 'isPaied'=>true])) {
                    $etudiantsSoldes[] = $et;
                } else {
                    $etudiantsInsolvables[] = $et;
                }
            }
        }
        else {
            $pc = $this->entityManagerInterface->getRepository(PaiementClasse::class)->findOneBy(['classe'=>$classe, 'typeDePaiement'=>$tp]);
            $tranches = $this->entityManagerInterface->getRepository(Tranche::class)->findBy(['paiementClasse'=>$pc,]);
            foreach ($etudiants as $et) {
                if ($pc && $tranches) {
                    $nbTranchesPayees = 0;
                    foreach ($tranches as $tranch) {
                        $paie = $this->entityManagerInterface->getRepository(Paiement::class)->findOneBy(['etudiantInscris'=> $et, 'tranche'=>$tranch, 'isPaied'=>true]);
                        if ($paie) {
                            $nbTranchesPayees++;
                        }
                    }
                    if ($nbTranchesPayees == count($tranches)) {
                        $etudiantsSoldes[] = $et;
                    } else {
                        $etudiantsInsolvables[] = $et;
                    }
                }
            }
        }

        $data = [
            'li' => $this->link,
            'inscris' => $hasPaied ? $etudiantsSoldes : $etudiantsInsolvables,
            'isForStudent' => true,
            'annee' => $annee,
            'searchText' => '',
            'tranche' => $tranche,
            'hasPaied' => $hasPaied,
            'classe' => $classe,
            'etats' => true,
            'tp' => $tp,
            'pc' => $this->entityManagerInterface->getRepository(PaiementClasse::class)->findOneBy(['typeDePaiement'=>$tp, 'classe'=>$classe]),
            'inPDF' => $request->get('file') && in_array($request->get('file'), ['pdf', 'excel']) ? true : false,
        ];
        // die($request->get('file'));
        if ($request->get('file') && in_array($request->get('file'), ['pdf', 'excel'])) {
            $htmData['data'][] = [
                'classe' => $data['classe'],
                'etudiants' => $data['inscris'],
    
            ];
            $htmData['annee'] = $data['annee'];$htmData['tranche'] = $data['tranche'];
            $htmData['hasPaied'] = $data['hasPaied'];$htmData['etats'] = $data['etats'];
            $htmData['tp'] = $data['tp'];$htmData['pc'] = $data['pc'];
            $htmData['inPDF'] = $data['inPDF'];;

            $result = $eu->exportStudents($htmData);
            return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
        }

        $form = $this->createForm(UploadProgramType::class, null, [
            'action' => $this->generateUrl('paiement_importer_quitus', [
                'anneeSlug' => $annee->getSlug(),
                'slugTP' => $tp->getSlug(),
            ]),
            'attr' => [
                'class' => 'form-confirm-ajax-action',
            ]
        ]);

        $data['form'] = $form->createView();

        return $this->render('paiement/classes.html.twig', $data);
        
    }

    /**
     * @Route("/gestion-paiements/change-paiement-status{id}", name="paiement_change_pc_status")
     * @Route("/gestion-paiements/change-paiement-status", name="paiement_change_pc_statusb")
     */
    public function changePaiementClasseState(PaiementClasse $pc, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $state = !$pc->getIsObligatoire();
        $pc->setIsObligatoire($state);
        $this->entityManagerInterface->flush();

        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>false, 'reloadWindow'=>true, 'msg'=>'Action effectuée ! La page va être rechargée.']));
        }
        $this->addFlash('success', 'Action effectuée !');
        return $this->redirectToRoute('paiement_classes_concernees', ['slugTP'=>$pc->getTypeDePaiement()->getSlug()]);
    }

    /**
     * @Route("/gestion-paiements/{id}/delete-paiement-classe", name="paiement_delete_pc")
     */
    public function deletePaiementClasse(PaiementClasse $pc, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$request->get('strict') || $request->get('strict') != 'yes' || !in_array('ROLE_SUPER_USER', $this->getUser()->getRoles())) {
            foreach ($pc->getTranches() as $t) {
                if ($t->getPaiements()) {
                    if ($request->isXmlHttpRequest()) {
                        return new Response(json_encode(['hasError'=>true, 'reloadWindow'=>false, 'msg'=>'Action impossible ! des etudiants ont déjà validé ce paiment.']));
                    }
                    $this->addFlash('danger', 'Action impossible ! des etudiants ont déjà validé ce paiment.');
                    return $this->redirectToRoute('paiement_classes_concernees', ['slugTP'=>$pc->getTypeDePaiement()->getSlug()]);
                }
            }
        }
        
        foreach ($pc->getTranches() as $t) {
            foreach ($t->getPaiements() as $p) {
                $this->entityManagerInterface->remove($p);
            }
            $this->entityManagerInterface->remove($t);
        }

        $this->entityManagerInterface->remove($pc);
        $backToTPIndex = false;
        if (!$pc->getTypeDePaiement()->getPaiementClasses()) {
            $this->entityManagerInterface->remove($pc->getTypeDePaiement());
            $backToTPIndex = true;
        }
        $this->entityManagerInterface->flush();

        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>false, 'reloadWindow'=>true, 'msg'=>'Action effectuée ! La page va être rechargée.']));
        }

        $this->addFlash('success', 'Action effectuée !');
        if ($backToTPIndex) {
            return $this->redirectToRoute('paiement');
        }
        return $this->redirectToRoute('paiement_classes_concernees', ['slugTP'=>$pc->getTypeDePaiement()->getSlug()]);
    }

    /**
     * @Route("/gestion-paiements/type-de-paiements/{slug}/delete", name="paiement_delete_tp")
     */
    public function deleteTypePaiement(TypeDePaiement $tp, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        if (!$request->get('strict') || $request->get('strict') != 'yes' || !in_array('ROLE_SUPER_USER', $this->getUser()->getRoles())) {
            foreach ($tp->getPaiementClasses() as $pc) {
                foreach ($pc->getTranches() as $t) {
                    if ($t->getPaiements()) {
                        if ($request->isXmlHttpRequest()) {
                            return new Response(json_encode(['hasError'=>true, 'reloadWindow'=>false, 'msg'=>'Action impossible ! des etudiants ont déjà validé ce paiment.']));
                        }
                        $this->addFlash('danger', 'Action impossible ! des etudiants ont déjà validé ce paiment.');
                        return $this->redirectToRoute('paiement_classes_concernees', ['slugTP'=>$pc->getTypeDePaiement()->getSlug()]);
                    }
                }
            }
                
        }
        foreach ($tp->getPaiementClasses() as $pc) {
            foreach ($pc->getTranches() as $t) {
                foreach ($t->getPaiements() as $p) {
                    $this->entityManagerInterface->remove($p);
                }
                $this->entityManagerInterface->remove($t);
            }
            $this->entityManagerInterface->remove($pc);
        }

        $this->entityManagerInterface->remove($tp);
        $this->entityManagerInterface->flush();

        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>false, 'reloadWindow'=>true, 'msg'=>'Action effectuée ! La page va être rechargée.']));
        }
        $this->addFlash('success', 'Action effectuée !');
        return $this->redirectToRoute('paiement');
    }
}
