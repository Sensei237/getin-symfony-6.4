<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestController extends AbstractController
{
    /**
     * @Route("/test", name="app_home")
     */
    public function index(): Response
    {
        return $this->render('test/index.html.twig', [
            'controller_name' => 'TestController',
        ]);
    }

    /**
     * @Route("/test-route", name="migration_test_route")
     */
    public function testRoute()
    {
        return new Response(md5(md5('gestion-des-contrats-academiques')));
    }
}
