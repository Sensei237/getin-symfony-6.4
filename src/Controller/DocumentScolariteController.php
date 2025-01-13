<?php

namespace App\Controller;

use App\Entity\AnneeAcademique;
use App\Entity\Classe;
use App\Entity\Etudiant;
use App\Repository\EtudiantInscrisRepository;
use App\Service\ExportUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DocumentScolariteController extends AbstractController
{
    #[Route('/document/scolarite', name: 'app_document_scolarite')]
    public function index(): Response
    {
        return $this->render('document_scolarite/index.html.twig', [
            'controller_name' => 'DocumentScolariteController',
        ]);
    }

    #[Route('/documents/certificats/scolarite/{anneeSlug}/{classeSlug}', name: 'app_document_scolarite_classe')]
    #[Route('/documents/certificats/scolarite/{anneeSlug}/{classeSlug}/{matricule}', name: 'app_document_scolarite_etudiant')]
    #[ParamConverter("anneeAcademique", options: ['mapping' => ['anneeSlug' => 'slug']])]
    #[ParamConverter("classe", options: ['mapping' => ['classeSlug' => 'slug']])]
    #[ParamConverter("etudiant", options: ['mapping' => ['matricule' => 'matricule']])]
    public function certificat_scolarites(AnneeAcademique $anneeAcademique, Classe $classe, ?Etudiant $etudiant=null, Request $request, EtudiantInscrisRepository $etudiantInscrisRepository, ExportUtils $eu): Response
    {
        $etudiants = [];
        if ($etudiant) {
            $etudiants[] = $etudiantInscrisRepository->findOneBy(['etudiant' => $etudiant, 'anneeAcademique' => $anneeAcademique]);
        }else {
            $etudiants = $etudiantInscrisRepository->findBy(['anneeAcademique' => $anneeAcademique]);
        }

        $data = [
            'etudiants' => $etudiants,
            'annee' => $anneeAcademique,
            'classe' => $classe,
            'isForCertificatsScolarites' => true,
            'pageTitle' => "Certificats de scolarité",
            'li' => 'et'
        ];

        if (!empty($etudiants) && $request->get('export')) {
            // On genere le fichier pdf
            $result = $eu->getDocumentsScolairePdf($data, time()."certificats_scolarites.pdf");
            return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        }
        
        return $this->render('document_scolarite/index.html.twig', $data);
    }

    #[Route('/documents/quitus/paiement/{anneeSlug}/{classeSlug}', name: 'app_document_quitus_classe')]
    #[Route('/documents/quitus/paiement/{anneeSlug}/{classeSlug}/{matricule}', name: 'app_document_quitus_etudiants')]
    #[ParamConverter("anneeAcademique", options: ['mapping' => ['anneeSlug' => 'slug']])]
    #[ParamConverter("classe", options: ['mapping' => ['classeSlug' => 'slug']])]
    #[ParamConverter("etudiant", options: ['mapping' => ['matricule' => 'matricule']])]
    public function quitus_paiement(AnneeAcademique $anneeAcademique, Classe $classe, ?Etudiant $etudiant=null, Request $request, ExportUtils $eu, EtudiantInscrisRepository $etudiantInscrisRepository): Response
    {
        $etudiants = [];
        if ($etudiant) {
            $etudiants[] = $etudiantInscrisRepository->findOneBy(['etudiant' => $etudiant, 'anneeAcademique' => $anneeAcademique]);
        }else {
            $etudiants = $etudiantInscrisRepository->findBy(['anneeAcademique' => $anneeAcademique]);
        }

        $data = [
            'etudiants' => $etudiants,
            'annee' => $anneeAcademique,
            'classe' => $classe,
            'isForQuitusPaiement' => true,
            'pageTitle' => "Quitus de paiement"
        ];

        if (!empty($etudiants) && $request->get('export')) {
            // On genere le fichier pdf
            $result = $eu->getDocumentsScolairePdf($data, time()."certificats_scolarites.pdf");
            return $this->file($result['temp_file'], $result['fileName'], ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        }

        return $this->render('document_scolarite/index.html.twig', $data);
    }
}
