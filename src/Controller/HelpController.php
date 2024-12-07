<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Dans ce controller nous allons gerer tout ce qui est documentation
 */
class HelpController extends AbstractController
{
    /**
     * @Route("/aide", name="help")
     */
    public function index()
    {
        return $this->render('help/index.html.twig', ['m'=>'ac']);
    }

    /**
     * @Route("/aide/gestion-etudiants", name="aide_etudiant")
     */
    public function gestionEtudiant() 
    {
        return $this->render('help/etudiant.html.twig', ['m'=>'et']);
    }


    /**
     * @Route("/aide/gestion-personnel", name="aide_personnel")
     */
    public function gestionPersonnel() 
    {
        return $this->render('help/personnel.html.twig', ['m'=>'pe']);
    }

    /**
     * @Route("/aide/gestion-paiement", name="aide_paiement")
     */
    public function gestionPaiement() 
    {
        return $this->render('help/paiement.html.twig', ['m'=>'pa']);
    }

    /**
     * @Route("/aide/gestion-notes", name="aide_note")
     */
    public function gestionNotes() 
    {
        return $this->render('help/note.html.twig', ['m'=>'no']);
    }

    /**
     * @Route("/aide/gestion-requetes", name="aide_requete")
     */
    public function gestionRequetes() 
    {
        return $this->render('help/requete.html.twig', ['m'=>'rq']);
    }

    /**
     * @Route("/aide/gestion-classes", name="aide_classes")
     */
    public function gestionClasses() 
    {
        return $this->render('help/classes.html.twig', ['m'=>'cl']);
    }

    /**
     * @Route("/aide/gestion-ec", name="aide_ec")
     */
    public function gestionEC() 
    {
        return $this->render('help/ec.html.twig', ['m'=>'ec']);
    }

    /**
     * @Route("/aide/gestion-examen", name="aide_examen")
     */
    public function gestionExamens() 
    {
        return $this->render('help/examen.html.twig', ['m'=>'ex']);
    }

    /**
     * @Route("/aide/gestion-programmes-academiques", name="aide_programme")
     */
    public function gestionProgrammes() 
    {
        return $this->render('help/programmes.html.twig', ['m'=>'pr']);
    }

    /**
     * @Route("/aide/statistiques", name="aide_stats")
     */
    public function gestionStats() 
    {
        return $this->render('help/stats.html.twig', ['m'=>'st']);
    }

    /**
     * @Route("/aide/gestion-utilisateurs", name="aide_user")
     */
    public function gestionUtilisateurs() 
    {
        return $this->render('help/user.html.twig', ['m'=>'ut']);
    }

    /**
     * @Route("/aide/configuration", name="aide_config")
     */
    public function configuration() 
    {
        return $this->render('help/config.html.twig', ['m'=>'co']);
    }

    /**
     * @Route("/aide/creation", name="aide_creation")
     */
    public function creation() 
    {
        return $this->render('help/crud.html.twig', ['m'=>'cr']);
    }

    /**
     * @Route("/aide/migrations", name="aide_migration")
     */
    public function gestionMigrations() 
    {
        return $this->render('help/migration.html.twig', ['m'=>'mi']);
    }

    /**
     * @Route("/aide/cloturer-annee-academique", name="aide_clorurer_annee")
     */
    public function cloturerAnnee() 
    {
        return $this->render('help/cloturer.html.twig', ['m'=>'ca']);
    }


    /**
     * @Route("/contact", name="aide_contact")
     */
    public function contact()
    {
        return $this->render('help/contact.html.twig', ['m'=>'ta']);
    }
}
