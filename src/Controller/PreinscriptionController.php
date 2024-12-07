<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Filiere;
use App\Entity\Etudiant;
use App\Entity\Formation;
use App\Service\ExportUtils;
use App\Service\EtudiantUtils;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use App\Form\EtudiantInscrisType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @author Mbozo'o Metou'ou Emmanuel Beranger Alias Sensei
 * Permet de gerer les preinscriptions des nouveaux etudiants afin que ceux ci puissent 
 * fournir leur données personnelles et académiques. 
 * Les action de ce controller sont accessibles hors connexion
 */
class PreinscriptionController extends AbstractController
{
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @Route("/", name="preinscriptionIndex")
     */
    public function index()
    {
        $annee = $this->entityManagerInterface->getRepository(AnneeAcademique::class)->findOneBy(['isArchived'=>false]);
        if (!$annee) {
            throw $this->createNotFoundException("Action impossible");
        }

        return $this->render('preinscription/index.html.twig', [
            'controller_name' => 'PreinscriptionController',
            'formations' => $this->entityManagerInterface->getRepository(Formation::class)->findAll(),
            'filieres' => $this->entityManagerInterface->getRepository(Filiere::class)->findBy([], ['name'=>'ASC']),
            'annee' => $annee,
            'classes' => $this->entityManagerInterface->getRepository(Classe::class)->findBy([], ['specialite'=>'ASC', 'formation'=>'DESC', 'niveau'=>'ASC']),

        ]);
    }

    /**
     * @Route("/preinscription/nouvelle-inscription/{slugAnnee}/{slugClasse}", name="preins_preinscription")
     * @Route("/preinscription/nouvelle-inscription/{slugAnnee}/", name="preins_preinscription2")
     * 
     * @Route("/preinscription/modifier-inscription/{slugAnnee}/{slugClasse}/{matricule}/{codeSecret}", name="preins_preinscription_edit")
     * @Route("/preinscription/modifier-inscription/{slugAnnee}", name="preins_preinscription_edit2")
     * 
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function preinscrire(AnneeAcademique $annee, Classe $classe, ?Etudiant $etudiant=null, ?string $codeSecret=null, Request $request, SerializerInterface $si, EtudiantUtils $etudiantUtils)
    {
        if ($annee->getIsArchived()) {
            throw $this->createNotFoundException("Action impossible");
        }
        $postUrl = $this->generateUrl('preins_preinscription', ['slugAnnee'=>$annee->getSlug(), 'slugClasse'=>$classe->getSlug()]);
        
        $inscription = new EtudiantInscris();
        if (($codeSecret != null && $etudiant == null)) {
            throw $this->createNotFoundException("Action impossible ! Votre préinscription n'existe pas");
        }
        if ($etudiant !== null && $codeSecret !== null) {
            $inscription = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findOneBy(['anneeAcademique'=>$annee, 'etudiant'=>$etudiant]);
            if (!$inscription || $codeSecret !== $etudiant->getCodeSecret()) {
                $this->addFlash('info', "Vous n'avez pas encore fait de preinscription cette année !");
                return $this->redirectToRoute('preins_preinscription', ['slugAnnee'=>$annee->getSlug(), 'slugClasse'=>$classe->getSlug()]);
            }
            $postUrl = $this->generateUrl('preins_preinscription_edit', ['slugAnnee'=>$annee->getSlug(), 'slugClasse'=>$classe->getSlug(), 'matricule'=>$etudiant->getMatricule(), 'codeSecret'=>$codeSecret]);
        }
        $form = $this->createForm(EtudiantInscrisType::class, $inscription, ['action'=>$postUrl]);
        $form->add('matricule', TextType::class, [
            'label' => 'Entrer le matricule SYSTAG',
            'required' => true,
            'attr' => [
                'placeholder' => "Matricule SYSTAG",
            ]
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($codeSecret === null) {
                    $codeSecret = time();
                }

                $inscription->setClasse($classe);
                $inscription->setAnneeAcademique($annee);
                $inscription->getEtudiant()->setMatricule($inscription->matricule);
                $inscription->getEtudiant()->setCodeSecret($codeSecret);
                $inscription->getEtudiant()->setLastUpdateAt(new \DateTimeImmutable());
                
                $this->entityManagerInterface->persist($inscription);
                
                $this->entityManagerInterface->flush();

                if ($request->isXmlHttpRequest()) {
                    $downloadUrl = $this->generateUrl('preins_fiche', ['slugAnnee'=>$annee->getSlug(), 'matricule'=>$inscription->getEtudiant()->getMatricule(), 'idIns'=>$inscription->getId()]);
                    $downloadUrl = "<a href ='".$downloadUrl."' target='_blank'>Télécharger votre fiche de preinscription ici</a>";
                    
                    return new Response($si->serialize(['formHasError'=>false, 'downloadUrl'=>$downloadUrl, 'msg'=>"Vos informations on été enregistrer avec success !"], 'json'));

                }

                return $this->redirectToRoute('preinscription');
            }else {
                if ($request->isXmlHttpRequest()) {
                    return new Response($si->serialize(['formHasError'=>true, 'msg'=>"Votre formulaire contient des erreurs"], 'json'));
                }
            }
        }

        return $this->render('preinscription/preinscrire.html.twig', [
            'form' => $form->createView(),
            'annee' => $annee,
            'classe' => $classe,
            'inscription' => $inscription,
        ]);
    }

    /**
     * @Route("/preinscription/fiche-inscription/{slugAnnee}/{idIns}/{matricule}", name="preins_fiche")
     * 
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("inscription", options={"mapping": {"idIns": "id"}})
     * 
     */
    public function generateFiche(AnneeAcademique $annee, EtudiantInscris $inscription, Etudiant $etudiant, ExportUtils $eu) {
        if ($annee->getId() !== $inscription->getAnneeAcademique()->getId() || $etudiant->getId() !== $inscription->getEtudiant()->getId()) {
            throw $this->createNotFoundException();
            
        }

        $result = $eu->getFichePreinscription($inscription);
        return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
    }

}
