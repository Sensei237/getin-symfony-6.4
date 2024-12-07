<?php

namespace App\Controller;

use App\Entity\EC;
use App\Entity\Classe;
use App\Entity\Contrat;
use App\Entity\AnneeAcademique;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Dans ce controller nous allons gerer les requetes.
 * 
 */
class RequestController extends AbstractController
{
    private $link = 'req';
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @Route("/gestion-requetes/{slugAnnee}", name="request")
     * 
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     */
    public function index(AnneeAcademique $annee)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            throw new Exception("Opération impossible ! Les données de cette année sont en lecture seule.", 1);
        }
        return $this->redirectToRoute('pa_liste_matieres', ['slug'=>$annee->getSlug(), 'page'=>1]);
    }

    /**
     * @Route("/gestion-requetes/manage/{slugAnnee}/{slugEC}", name="request_manage")
     * @Route("/gestion-requetes/manage/{slugAnnee}/{slugEC}/{slugClasse}", name="request_manage_classe")
     * 
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("ec", options={"mapping": {"slugEC": "slug"}})
     * @ParamConverter("classe", options={"mapping": {"slugClasse": "slug"}})
     */
    public function manageRequest(AnneeAcademique $annee, EC $ec, ?Classe $classe=null, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            throw new Exception("Opération impossible ! Les données de cette année sont en lecture seule.", 1);
        }

        $editable = !$annee->getIsArchived();
        if ($classe) {
            $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsClasseForEC($annee, $ec, $classe);
        }else{
            $contrats = $this->entityManagerInterface->getRepository(Contrat::class)->findContratsForEC($annee, $ec);
        }
        
        return $this->render('request/index.html.twig', [
            'contrats' => $contrats,
            'editable' => $editable,
            'li' => $this->link,
            'annee' => $annee,
            'ec' => $ec,
            'ecs' => $this->entityManagerInterface->getRepository(EC::class)->findBy([], ['intitule'=>'ASC']),

        ]);
    }

    /**
     * @Route("/gestion-requetes/save/{slugAnnee}/{contratId}", name="request_save")
     * 
     * @ParamConverter("annee", options={"mapping": {"slugAnnee": "slug"}})
     * @ParamConverter("contrat", options={"mapping": {"contratId": "id"}})
     */
    public function saveRequests(AnneeAcademique $annee, Contrat $contrat, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($annee->getIsArchived() && !$this->isGranted('ROLE_SUPER_USER')) {
            throw new Exception("Opération impossible ! Les données de cette année sont en lecture seule.", 1);
        }
        
        $noteTPE = $request->get('noteTPE');
        $noteTP = $request->get('noteTP');
        $noteCC = $request->get('noteCC');
        $noteSN = $request->get('noteSN');
        $noteSR = $request->get('noteSR');
        $msg = ['errorCC'=>false, 'errorSR'=>false, 'errorSN'=>false, 'errorTPE'=>false, 'errorTP'=>false];
        $hasError = false;
        if ($noteCC) {
            if (trim($noteCC) === '') {
                $contrat->setNoteCC(NULL)->setIsDataHasChange(true);
            }
            else {
                if (is_numeric($noteCC) && $noteCC <= 20 && $noteCC >= 0) {
                    $contrat->setNoteCC($noteCC)->setIsDataHasChange(true);
                }else {
                    $msg['errorCC'] = true;
                    $hasError = true;
                }
            }
        }
        if ($noteSN) {
            if (trim($noteSN) === '') {
                $contrat->setNoteSN(NULL)->setIsDataHasChange(true);
            }
            else {
                if (is_numeric($noteSN) && $noteSN <= 20 && $noteSN >= 0) {
                    $contrat->setNoteSN($noteSN)->setIsDataHasChange(true);
                }else {
                    $msg['errorSN'] = true;
                    $hasError = true;
                }
            }
        }
        if ($noteSR) {
            if (trim($noteSR) === '') {
                $contrat->setNoteSR(NULL)->setIsDataHasChange(true);
            }
            else {
                if (is_numeric($noteSR) && $noteSR >= 0 && $noteSR <= 20 && $contrat->getNoteSN() && $contrat->getNoteCC()) {
                    $contrat->setNoteSR($noteSR)->setIsDataHasChange(true);
                }else {
                    $msg['errorSR'] = true;
                    $hasError = true;
                }
            }
        }
        if ($noteTPE) {
            if (trim($noteTPE) === '') {
                $contrat->setNoteTPE(NULL)->setIsDataHasChange(true);
            }
            else {
                if (is_numeric($noteTPE) && $noteTPE >= 0 && $noteTPE <= 20) {
                    $contrat->setNoteTPE($noteTPE)->setIsDataHasChange(true);
                }else {
                    $msg['errorTPE'] = true;
                    $hasError = true;
                }
            }
        }
        if ($noteTP) {
            if (trim($noteTP) === '') {
                $contrat->setNoteTP(NULL)->setIsDataHasChange(true);
            }
            else {
                if (is_numeric($noteTP) && $noteTP >= 0 && $noteTP <= 20) {
                    $contrat->setNoteTP($noteTP)->setIsDataHasChange(true);
                }else {
                    $msg['errorTP'] = true;
                    $hasError = true;
                }
            }
        }

        if (!$hasError) {
            $this->entityManagerInterface->flush();
        }

        if ($request->isXmlHttpRequest()) {
            return new Response(json_encode(['hasError'=>$hasError, 'msg'=>$msg]));
        }

        return $this->redirectToRoute('request_manage', ['slugAnnee'=>$annee->getSlug(), 'slugEC'=>$contrat->getEcModule()->getEc()->getSlug()]);
    }
}
