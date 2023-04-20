<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="root")
     * @Route("/{url}", name="home", requirements={"url"=".+"})
     */
    public function index(): Response
    {
        return new Response('Direct access to the application is not permitted! Find more info at https://github.com/assendk/symfony-api', Response::HTTP_FORBIDDEN);
    }
}
