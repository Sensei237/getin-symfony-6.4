<?php

namespace App\Controller;

use App\Entity\Pays;
use App\Entity\User;
use App\Entity\Classe;
use App\Entity\Examen;
use App\Entity\Region;
use App\Entity\Contrat;
use App\Entity\Employe;
use App\Entity\Departement;
use App\Form\UploadFileType;
use App\Service\ImportUtils;
use App\Entity\Configuration;
use App\Entity\AnneeAcademique;
use App\Form\UploadProgramType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class MigrationController extends AbstractController
{
    private $link = 'mig';
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @Route("/migrations", name="migration")
     */
    public function index(ImportUtils $importUtils, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $annee = $request->getSession()->get('annee');

        $form = $this->createForm(UploadProgramType::class, null, [
            'action' => $this->generateUrl('migration_upload_note', ['slug'=>$annee->getSlug()]),
            'attr' => [
                // 'class' => 'form-confirm-ajax-action'
            ]
        ]);

        $form2 = $this->createForm(UploadProgramType::class, null, [
            'action' => $this->generateUrl('migration_upload_anonymats', ['slug'=>$annee->getSlug()]),
            'attr' => [
                // 'class' => 'form-confirm-ajax-action'
            ]
        ]);

        $form3 = $this->createForm(UploadProgramType::class, null, [
            'action' => $this->generateUrl('migration_upload_services', ['slug'=>$annee->getSlug()]),
            'attr' => [
                // 'class' => 'form-confirm-ajax-action'
            ]
        ]);
        $form3->add('type', ChoiceType::class, [
            'required' => true,
            'choices' => ['Services'=>'services', 'Filières'=>'filieres', 'Spécialités'=>'specialites', 'Classes'=>'classes'],
            'label' => "Sélectionner le type de données",
            'placeholder' => 'Sélectionner le type de données',
            'attr' => ['class' => 'select2'],
        ]);


        $form4= $this->createForm(UploadProgramType::class, null, [
            'action' => $this->generateUrl('migration_upload_personnel', ['slug'=>$annee->getSlug()]),
            'attr' => [
                // 'class' => 'form-confirm-ajax-action'
            ]
        ]);

        return $this->render('migration/index.html.twig', [
            'li' => $this->link,
            'form' => $form->createView(),
            'annee' => $annee,
            'formAnonymat' => $form2->createView(),
            'formService' => $form3->createView(),
            'formPersonnel' => $form4->createView(),
        ]);
    }

    /**
     * @Route("/migrations/upload-notes/{slug}", name="migration_upload_note")
     */
    public function uploadNotes(AnneeAcademique $annee, Request $request, ImportUtils $importUtils)
    {
        $form = $this->createForm(UploadProgramType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $flashAlert = $importUtils->doImportation($this->entityManagerInterface, $form, 'N', $annee, $this->getParameter('sample_directory'));
            $this->entityManagerInterface->flush();

            if ($request->isXmlHttpRequest()) {
                $hasError = $flashAlert != '' ? true : false;
                if ($flashAlert == '') {
                    $flashAlert = "L'importation a été effectuée !";
                }
                return new Response(json_encode(['hasError'=>$hasError, 'msg'=>$flashAlert, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }

            if ($flashAlert != '') {
                $this->addFlash('errors', $flashAlert);
            }else {
                $this->addFlash('success', 'L\'importation a été effectuée !');
            }
        }
        return $this->redirectToRoute('migration');
    }

    /**
     * @Route("/migrations/upload-services/{slug}", name="migration_upload_services")
     */
    public function uploadServices(AnneeAcademique $annee, Request $request, ImportUtils $importUtils)
    {
        $form = $this->createForm(UploadProgramType::class);
        $form->add('type', ChoiceType::class, [
            'required' => true,
            'choices' => ['Services'=>'services', 'Filières'=>'filieres', 'Spécialités'=>'specialites', 'Classes'=>'classes'],
            'label' => "Sélectionner le type de données",
            'placeholder' => 'Sélectionner le type de données',
            'attr' => ['class' => 'select2'],
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $type = $form->getData()['type'];
            $flashAlert = $importUtils->doImportation($this->entityManagerInterface, $form, mb_strtolower($type, 'UTF8'), $annee, $this->getParameter('sample_directory'));
            $this->entityManagerInterface->flush();

            if ($request->isXmlHttpRequest()) {
                $hasError = $flashAlert != '' ? true : false;
                if ($flashAlert == '') {
                    $flashAlert = "L'importation a été effectuée !";
                }
                return new Response(json_encode(['hasError'=>$hasError, 'msg'=>$flashAlert, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }

            if ($flashAlert != '') {
                $this->addFlash('errors', $flashAlert);
            }else {
                $this->addFlash('success', 'L\'importation a été effectuée !');
            }
        }else {
            $this->addFlash('errors', "Le formulaire contient des erreurs !");
        }
        
        return $this->redirectToRoute('migration');
    }

    /**
     * @Route("/migrations/upload-personnel/{slug}", name="migration_upload_personnel")
     */
    public function uploadPersonnel(AnneeAcademique $annee, Request $request, ImportUtils $importUtils)
    {
        $form = $this->createForm(UploadProgramType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $flashAlert = $importUtils->doImportation($this->entityManagerInterface, $form, 'personnel', $annee, $this->getParameter('sample_directory'));
            $this->entityManagerInterface->flush();

            if ($request->isXmlHttpRequest()) {
                $hasError = $flashAlert != '' ? true : false;
                if ($flashAlert == '') {
                    $flashAlert = "L'importation a été effectuée !";
                }
                return new Response(json_encode(['hasError'=>$hasError, 'msg'=>$flashAlert, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }

            if ($flashAlert != '') {
                $this->addFlash('errors', $flashAlert);
            }else {
                $this->addFlash('success', 'L\'importation a été effectuée !');
            }
        }
        return $this->redirectToRoute('migration');
    }

    /**
     * @Route("/migrations/upload-anonymats/{slug}", name="migration_upload_anonymats")
     */
    public function uploadAnonymats(AnneeAcademique $annee, Request $request, ImportUtils $importUtils)
    {
        $form = $this->createForm(UploadProgramType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $flashAlert = $importUtils->doImportation($this->entityManagerInterface, $form, 'A', $annee, $this->getParameter('sample_directory'));
            $this->entityManagerInterface->flush();

            if ($request->isXmlHttpRequest()) {
                $hasError = $flashAlert != '' ? true : false;
                if ($flashAlert == '') {
                    $flashAlert = "L'importation a été effectuée !";
                }
                return new Response(json_encode(['hasError'=>$hasError, 'msg'=>$flashAlert, 'reloadWindow'=>false]), '200', ['Content-type'=>'appplication/json']);
            }

            if ($flashAlert != '') {
                $this->addFlash('errors', $flashAlert);
            }else {
                $this->addFlash('success', 'L\'importation a été effectuée !');
            }
        }
        return $this->redirectToRoute('migration');
    }

    /**
     * @Route("/fixtures/notes/{slug}", name="examen_fixture_note")
     * @Route("/fixtures/notes/{slug}/{id}", name="examen_fixture_note_classe")
     */
    #[ParamConverter("classe", options: ['mapping' => ['id' => 'id']])]
    #[ParamConverter("annee", options: ['mapping' => ['slug' => 'slug']])]
    public function notesFixture(AnneeAcademique $annee, ?Classe $classe=null)
    {
        if ($annee->getIsArchived()) {
            $this->createAccessDeniedException("Année academique cloturee !");
        }
        if ($classe) {
            $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsClasse($annee, $classe);
        }else {
            $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findAll();
        }
        foreach ($contrats as $contrat) {
            $noteCC = rand(5, 19);
            $noteSN = rand(5, 19);
            $noteTPE = rand(5, 19);
            $noteTP = rand(5, 19);
            $contrat->setNoteCC($noteCC)
                    ->setNoteSN($noteSN)
                    ->setNoteSR(null)
                    ->setNoteTPE($noteTPE)
                    ->setNoteTP($noteTP)
                    ->setNoteJury(null);
        }
        $this->entityManagerInterface->flush();
        $this->addFlash('success', "Notes generees avec success !");

        return $this->redirectToRoute('home');
    }


    /**
     * @Route("/auto-configure/software", name="migration_auto_config")
     */
    public function autoConfigure(UserPasswordHasherInterface $encoder){
        $annee = new AnneeAcademique();
        $year = (int) date_format(new \DateTime(), 'Y');
        $annee->setSlug($year.'-'.($year+1))->setIsArchived(false)->setDenomination($year.' - '.($year+1));
        $this->entityManagerInterface->persist($annee);
        $config = new Configuration();
        $config->setAnneeAcademique($annee);
        $this->entityManagerInterface->persist($config);
        $exam1 = new Examen();
        $exam2 = new Examen();
        $exam3 = new Examen();
        $exam4 = new Examen();
        $exam5 = new Examen();
        $exam1->setIntitule("Contrôle Continu")->setCode("CC")->setSlug("controle-continu")->setType("C")->setPourcentage(70);
        $exam2->setIntitule("Session Normale")->setCode("SN")->setSlug("session-normale")->setType("E")->setPourcentage(70)->setPourcentageCC(30);
        $exam3->setIntitule("Session de Rattrapage")->setCode("SR")->setSlug("session-de-rattrapage")->setType("R")->setPourcentageCC(30)->setPourcentage(70);
        $exam4->setIntitule("Travaux Pratique")->setCode("TP")->setSlug("travaux-pratique")->setType("TP")->setPourcentageCC(0)->setPourcentage(0);
        $exam5->setIntitule("Travail Personnel de l'étudiant")->setCode("TPE")->setSlug("travail-personnel")->setType("TPE")->setPourcentageCC(0)->setPourcentage(0);
        $this->entityManagerInterface->persist($exam1);
        $this->entityManagerInterface->persist($exam2);
        $this->entityManagerInterface->persist($exam3);
        $this->entityManagerInterface->persist($exam4);
        $this->entityManagerInterface->persist($exam5);
        $pays = new Pays();
        $pays->setNom("Cameroun");
        $region = new Region(); $region->setNom("Region du Sud")->setSlug("region-du-sud");
        $region2 = new Region(); $region2->setNom("Region de l'Ouest")->setSlug("region-de-ouest");
        $region3 = new Region(); $region3->setNom("Region de l'Extreme Nord")->setSlug("region-de-extreme-nord");
        $region4 = new Region(); $region4->setNom("Region du Nord")->setSlug("region-du-nord");
        $region5 = new Region(); $region5->setNom("Region de l'Adamaoua")->setSlug("region-de-adamaoua");
        $region6 = new Region(); $region6->setNom("Region du Nord-Ouest")->setSlug("region-du-nord-ouest");
        $region7 = new Region(); $region7->setNom("Region du Sud-Ouest")->setSlug("region-du-sud-ouest");
        $region8 = new Region(); $region8->setNom("Region du Littoral")->setSlug("region-du-littoral");
        $region9 = new Region(); $region9->setNom("Region de l'Est")->setSlug("region-de-est");
        $region1 = new Region(); $region1->setNom("Region du Centre")->setSlug("region-du-centre");

        $this->entityManagerInterface->persist($pays); 
        $this->entityManagerInterface->persist($region);
        $this->entityManagerInterface->persist($region1);
        $this->entityManagerInterface->persist($region2);
        $this->entityManagerInterface->persist($region3);
        $this->entityManagerInterface->persist($region4);
        $this->entityManagerInterface->persist($region5);
        $this->entityManagerInterface->persist($region6);
        $this->entityManagerInterface->persist($region7);
        $this->entityManagerInterface->persist($region8);
        $this->entityManagerInterface->persist($region9);
        $dpts = [];
        foreach (['Mvila', 'Dja et lobo', 'Ocean', 'Vallée du Ntem'] as $key => $value) {
            $dpt = new Departement(); $dpt->setRegion($region)->setNom($value)->setSlug($value);
            $dpts[] = $dpt;
        }
        foreach (['Moungo', 'Nkam', 'Wouri', 'Sanaga Maritime'] as $key => $value) {
            $dpt = new Departement(); $dpt->setRegion($region8)->setNom($value)->setSlug($value);
            $dpts[] = $dpt;
        }
        foreach (['Haute-Sanaga', 'Lekié', 'Mbam et Inoubou', 'Méfou et Afamba', 'Mbam et Kim', 'Nyong et Mfoumou', 'Nyong et Kéllé', 'Mfoundi', 'Méfou et Akono', 'Nyong et Soo'] as $key => $value) {
            $dpt = new Departement(); $dpt->setRegion($region1)->setNom($value)->setSlug($value);
            $dpts[] = $dpt;
        }
        foreach (['Bamboutos', 'Haut Nkam', 'Hauts Plateaux', 'Koung Khi', 'Menoua', 'Mifi', 'Ndé', 'Noun'] as $key => $value) {
            $dpt = new Departement(); $dpt->setRegion($region2)->setNom($value)->setSlug($value);
            $dpts[] = $dpt;
        }
        foreach (['Boumba et Ngoko', 'Haut Nyong', 'Kadey', 'Lom-et-Djérem'] as $key => $value) {
            $dpt = new Departement(); $dpt->setRegion($region9)->setNom($value)->setSlug($value);
            $dpts[] = $dpt;
        }
        foreach (['Djérem', 'Faro-et-Déo', 'Mayo-Banyo', 'Mbéré', 'Vina'] as $key => $value) {
            $dpt = new Departement(); $dpt->setRegion($region5)->setNom($value)->setSlug($value);
            $dpts[] = $dpt;
        }
        foreach (['Diamaré', 'Logone et Chari', 'Mayo Danay', 'Mayo Kani', 'Mayo Sava', 'Mayo Tsanaga'] as $key => $value) {
            $dpt = new Departement(); $dpt->setRegion($region3)->setNom($value)->setSlug($value);
            $dpts[] = $dpt;
        }
        foreach (['Bénoué', 'Faro', 'Mayo Louti', 'Mayo Rey'] as $key => $value) {
            $dpt = new Departement(); $dpt->setRegion($region4)->setNom($value)->setSlug($value);
            $dpts[] = $dpt;
        }
        foreach (['Boyo', 'Bui', 'Donga Mantung', 'Menchum', 'Mezam', 'Momo', 'Ngo Ketunjia'] as $key => $value) {
            $dpt = new Departement(); $dpt->setRegion($region6)->setNom($value)->setSlug($value);
            $dpts[] = $dpt;
        }
        foreach (['Fako', 'Koupé Manengouba', 'Lebialem', 'Manyu', 'Meme', 'Ndian'] as $key => $value) {
            $dpt = new Departement(); $dpt->setRegion($region7)->setNom($value)->setSlug($value);
            $dpts[] = $dpt;
        }

        foreach ($dpts as $dpt) {
            $this->entityManagerInterface->persist($dpt);
        }

        $employe = new Employe();
        $employe->setNom("Madara")->setPrenom("Sensei")->setDateDeNaissance(new \DateTime())->setLieuDeNaissance("Konoha")->setSexe("M")->setTelephone("656967064")->setAdresseEmail("emmaberanger2@gmail.com")->setSituationMatrimoniale("C")->setNombreDenfants(0)->setIsVisible(false);
        $this->entityManagerInterface->persist($employe);
        $user = new User();
        $user->setEmploye($employe)->setUsername("master")->setRoles("ROLE_STUDENT_MANAGER-ROLE_CONTRATS_MANAGER-ROLE_USER-ROLE_SAISIE_NOTES-ROLE_SUPER_USER")->setIsValid(true)->setIsOnline(false)->setPassword($encoder->hashPassword($user, "test1234"));
        $this->entityManagerInterface->persist($user);

        $this->entityManagerInterface->flush();

        return $this->redirectToRoute('home');
    }

}
