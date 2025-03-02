<?php
  // src/Controller/ServiceController.php

namespace App\Controller;

use App\Entity\Service;
use App\Form\FormNameType;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ServiceController extends AbstractController
{
    #[Route('/service', name: 'app_service_index', methods: ['GET'])]
    public function index(ServiceRepository $serviceRepository, Request $request): Response
    {
        $query = $request->query->get('query', '');
        $page = $request->query->getInt('page', 1);
        $perPage = 10;

        // Fetch services based on search query with pagination
        $services = $serviceRepository->searchByNameOrDescription($query, $page, $perPage);

        $totalServices = $serviceRepository->countBySearchQuery($query);
        $totalPages = ceil($totalServices / $perPage);

        return $this->render('admin/services_list.html.twig', [
            'services' => $services,
            'searchQuery' => $query,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/service/new', name: 'app_service_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $service = new Service();
        $form = $this->createForm(FormNameType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($service);
            $entityManager->flush();

            return $this->redirectToRoute('app_service_index');
        }

        return $this->render('admin/services_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/service/{id}/edit', name: 'app_service_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FormNameType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_service_index');
        }

        return $this->render('admin/edit.html.twig', [
            'form' => $form->createView(),
            'service' => $service,
        ]);
    }

    #[Route('/service/{id}', name: 'app_service_delete', methods: ['POST'])]
    public function delete(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $service->getId(), $request->request->get('_token'))) {
            $entityManager->remove($service);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_service_index');
    }

    #[Route('/service/search', name: 'service_search', methods: ['GET'])]
    public function search(ServiceRepository $serviceRepository, Request $request): Response
    {
        $query = $request->query->get('query', '');
        $page = $request->query->getInt('page', 1);
        $perPage = 10;

        // Fetch services based on search query with pagination
        $services = $serviceRepository->searchByNameOrDescription($query, $page, $perPage);

        // Log search results for debugging
        error_log("Search Query: " . $query);
        error_log("Results Count: " . count($services));

        if (empty($services)) {
            error_log("⚠️ No services found for query: " . $query);
        }

        // Return JSON response with services and pagination info
        return $this->json([
            'services' => array_map(fn($s) => [
                'id' => $s->getId(),
                'name' => $s->getName(),
                'description' => $s->getDescription(),
            ], $services),
            'currentPage' => $page,
            'totalPages' => ceil($serviceRepository->countBySearchQuery($query) / $perPage),
        ]);
    }
}
