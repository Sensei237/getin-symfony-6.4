<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Examen;
use App\Entity\Filiere;
use App\Entity\Formation;
use App\Entity\Specialite;
use App\Service\ExportUtils;
use App\Entity\AnneeAcademique;
use App\Entity\EtudiantInscris;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class ExamenController extends AbstractController
{
    private $link = 'exam';
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @Route("/gestion-examen", name="examen")
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('examen/index.html.twig', [
            'examens' => $this->entityManagerInterface->getRepository(Examen::class)->findBy([], ['intitule'=>'ASC']),
            'li' => $this->link,
        ]);
    }

    /**
     * Cette action du controller permet de recuperer les etudiants suivant certains criteres
     *  - liste des etudiants qui sont alles au rattrapage peu importe la matiere et la classe
     *  - liste des etudiants qui alles au rattrapage sur un semestre
     *  - liste des etudiants qui ont tout validé en session normale pour un semestre donné ou peu importe le semestre
     * 
     * 
     * @Route("/gestion-examen/etudiants/{slugAnnee}/{slugExam}", name="examen_liste_etudiant")
     * @Route("/gestion-examen/etudiants/{slugAnnee}/{slugExam}/{slugClasse}", name="examen_liste_etudiant_classe")
     * @Route("/gestion-examen/etudiants/{slugAnnee}/", name="examen_liste_etudiant0")
     * 
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("examen", options={"mapping": {"slugExam": "slug"}})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function afficherEtudiantsExamen(AnneeAcademique $annee, Examen $examen, ?Classe $classe=null, Request $request, ExportUtils $exportUtils)
    {
        $etudiants = [];
        $sessionData = [];
        $etudiants = $this->entityManagerInterface->getRepository(EtudiantInscris::class)->findEtudiants($annee, 0, null, null, null, null, $classe);
        $isForSN = true;
        $selectedOption = 1;
        if ($request->get('selectedOption')) {
            $selectedOption = $request->get('selectedOption');
        }
        $data = [];
        $semestre = $request->get('semestre');
        foreach ($etudiants as $etudiant) {
            $contrats = []; $existe = false;
            foreach ($etudiant->getContrats() as $contrat) {
                // L'etudiant doit absolument avoir une note de cc et une note d'examen
                if ($contrat->getNoteCC() && $contrat->getNoteSN()){
                    $existe = true;
                    if (!$semestre || $semestre == $contrat->getEcModule()->getSemestre()) {
                        if ($examen->getType() == 'R' && $examen->getCode() == 'SR') {
                            // On cherche les etudiants qui sont concerné par le rattrapage
                            // Soit il doit faire le rattrapage soit il a fait le rattrapage
                            $isForSN = false;
                            if ($contrat->getNoteSR() || (!$contrat->getIsValidated() && !$contrat->getNoteSR())) {
                                $contrats[] = $contrat;
                            }
                        }elseif ($examen->getType() == 'E' && $examen->getCode() == 'SN') {
                            // On cherche les etudiants qui ont tout validé en SN
                            if (!$contrat->getIsValidated() || ($contrat->getIsValidated() && $contrat->getNoteSR())) {
                                $contrats[] = $contrat;
                            }
                        }
                    }
                }
            }
            
            if ($existe) {
                if ($examen->getType() == 'R' && $examen->getCode() == 'SR') {
                    if (!empty($contrats)) {
                        $sessionData[] = $etudiant;
                        $data[] = [
                            'etudiant' => $etudiant,
                            'contrats' => $contrats,
                        ];
                    }
                }elseif ($examen->getType() == 'E' && $examen->getCode() == 'SN') {
                    if (empty($contrats)) {
                        $sessionData[] = $etudiant;
                        $data[] = [
                            'etudiant' => $etudiant,
                        ];
                    }
                }
            }
        }

        $message = "Liste des etudiants ";
        if ($isForSN) {
            $message = 'LISTE DES ETUDIANTS AYANT TOUT VALIDE EN SESSION NORMALE ';
        }else {
            $message = "LISTE DES ETUDIANTS ATTENDUS EN SESSION DE RATTRAPAGE ";
        }
        if ($semestre) {
            if ($semestre == 1) {
                $message .= "SEMESTRE 1";
            }else {
                $message .= "SEMESTRE 2";
            }
        }

        if ($request->get('download')) {
            $result = $exportUtils->exportPDFEtudiants($data, $annee, $message, 'examen/etudiantpdf.html.twig', null, $classe);
            return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
        }

        $this->addFlash('info', $message);

        return $this->render('examen/etudiant.html.twig', [
            'data' => $data, 
            'annee' => $annee, 
            'classe' => $classe, 
            'examen' => $examen,
            'examenSN' => $this->entityManagerInterface->getRepository(Examen::class)->findOneBy(['type'=>'E', 'code'=>'SN']),
            'examenSR' => $this->entityManagerInterface->getRepository(Examen::class)->findOneBy(['type'=>'R', 'code'=>'SR']),
            'li' => $this->link,
            'selectedOption' => $selectedOption,
            'semestre' => $semestre,
            'formations' => $this->entityManagerInterface->getRepository(Formation::class)->findBy([], ['name'=>'ASC']),
            'filieres' => $this->entityManagerInterface->getRepository(Filiere::class)->findBy([], ['name'=>'ASC']),
            'specialites' => $this->entityManagerInterface->getRepository(Specialite::class)->findBy([], ['name'=>'ASC']),
            'classes' => $this->entityManagerInterface->getRepository(Classe::class)->findBy([], ['niveau'=>'ASC', 'nom'=>'ASC'])
            
        ]);
    }

    /**
     * @Route("/gestion-examen/modifier/{slug}/{champ}", name="examen_edit_pourcentage")
     */
    public function editPourcentageExam(Examen $exam, string $champ, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (mb_strtolower($champ, 'UTF8') == 'p') {
            $exam->setPourcentage($request->request->get('value'));
            if ($exam->getCode() == 'SN') {
                $request->getSession()->set('pourcentageSN', $request->request->get('value'));
            }elseif ($exam->getCode() == 'SR') {
                $request->getSession()->set('pourcentageSR', $request->request->get('value'));
            }
        }else {
            $exam->setPourcentageCC($request->request->get('value'));
            if ($exam->getCode() == 'SN') {
                $request->getSession()->set('pourcentageCC', $request->request->get('value'));
            }elseif ($exam->getCode() == 'SR') {
                $request->getSession()->set('pourcentageCC2', $request->request->get('value'));
            }
        }

        $this->entityManagerInterface->flush();

        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>false, 'msg'=>'Pourcentage modifié !']), '200', ['Content-type'=>'appplication/json']);
        }

        return $this->redirectToRoute('examen');
    }
}
