<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {

        if (!$this->getUser()) {
            return $this->redirectToRoute("app_login");
        }

        if (!in_array("ROLE_ADMIN", $this->getUser()->getRoles()) && !in_array("ROLE_WEBMASTER", $this->getUser()->getRoles())) {
            return $this->redirectToRoute("app_login");
        }

        return $this->render('admin/dashboard.html.twig', [
            'username' => $this->getUser()->getUserIdentifier(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('NG OP | Staff Panel')
            ->renderContentMaximized()
            ->setLocales(['en', 'fr'])
            ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Staff Users', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Categories', 'fas fa-list', Category::class);
        yield MenuItem::linkToUrl('API', 'fas fa-code', '/apis');
    }
}
