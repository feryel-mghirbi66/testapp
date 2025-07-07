<?php

namespace App\Controller;

use App\Entity\Service;
use App\Entity\User; 
use App\Entity\Admin;
use App\Form\AdminType;

use App\Form\FormNameType; // Correct form type
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ConsultationRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
 // Correct namespace


class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_home')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/services', name: 'admin_services', methods: ['GET'])]
 
    #[Route('/admin/services', name: 'admin_services', methods: ['GET'])]
public function services(ServiceRepository $serviceRepository, Request $request): Response
{
    $query = $request->query->get('query', '');
    $page = $request->query->getInt('page', 1);
    $perPage = 10;

    // Fetch services based on pagination (assuming your repository has pagination logic)
    $services = $serviceRepository->searchByNameOrDescription($query, $page, $perPage);
    $totalServices = $serviceRepository->countBySearchQuery($query);
    $totalPages = ceil($totalServices / $perPage);

    return $this->render('admin/services_list.html.twig', [
        'services' => $services,
        'searchQuery' => $query,
        'currentPage' => $page,
        'totalPages' => $totalPages, // Pass total pages
    ]);
}



    #[Route('/admin/services/new', name: 'admin_service_new', methods: ['GET', 'POST'])]
    public function newService(Request $request, EntityManagerInterface $entityManager): Response
    {
        $service = new Service();
        $form = $this->createForm(FormNameType::class, $service); // Use FormNameType

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($service);
            $entityManager->flush();

            return $this->redirectToRoute('admin_services');
        }

        return $this->render('admin/services_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

 // New consultations route (to list all consultations)
 #[Route('/admin/consultations', name: 'admin_consultations', methods: ['GET'])]
 public function consultations(ConsultationRepository $consultationRepository): Response
 {
     return $this->render('admin/consultations_list.html.twig', [
         'consultations' => $consultationRepository->findAll(),
     ]);
 }


    #[Route('/admin/users', name: 'admin_users')]
    public function users(): Response
    {
        return $this->render('admin/users.html.twig');
    }

    #[Route('/admin/equipment', name: 'admin_equipment')]
    public function equipment(): Response
    {
        return $this->render('admin/equipment.html.twig');
    }

    #[Route('/admin/infrastructure', name: 'admin_infrastructure')]
    public function infrastructure(): Response
    {
        return $this->render('admin/infrastructure.html.twig');
    }

    #[Route('/admin/medical-records', name: 'admin_medical_records')]
    public function medicalRecords(
        ConsultationRepository $consultationRepository,
        UserRepository $userRepository
    ): Response {
        // Get all consultations with their status
        $consultations = $consultationRepository->findAll();
        
        return $this->render('admin/medical_records.html.twig', [
            'consultations' => $consultations,
            'statuses' => [
                Consultation::STATUS_PENDING,
                Consultation::STATUS_IN_PROGRESS,
                Consultation::STATUS_COMPLETED
            ]
        ]);
    }

    #[Route('/admin/medication-stock', name: 'admin_medication_stock')]
    public function medicationStock(
        \App\Repository\MedicamentRepository $medicamentRepository,
        \Knp\Component\Pager\PaginatorInterface $paginator,
        Request $request
    ): Response {
        $searchTerm = $request->query->get('search', '');
        $sortField = $request->query->get('sortField', 'm.nom_medicament');
        $sortOrder = $request->query->get('sortOrder', 'asc');
        $page = $request->query->getInt('page', 1);

        $query = $medicamentRepository->findByFiltersQuery($searchTerm, $sortField, $sortOrder);
        $medicaments = $paginator->paginate($query, $page, 10);

        return $this->render('medicament/list_medicaments.html.twig', [
            'medicaments' => $medicaments,
            'searchTerm' => $searchTerm,
            'sortField' => $sortField,
            'sortOrder' => $sortOrder
        ]);
    }
    #[Route('/admin/users/create/admin', name: 'admin_create')]
public function createAdmin(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
{
    // 🔹 Récupérer l'ID de l'utilisateur depuis la session
    $userId = $session->get('user_id');

    if (!$userId) {
        $this->addFlash('error', 'Aucun utilisateur trouvé.');
        return $this->redirectToRoute('user_create');
    }

    // 🔹 Trouver l'utilisateur par son ID
    $user = $entityManager->getRepository(User::class)->find($userId);

    if (!$user) {
        $this->addFlash('error', 'Utilisateur non trouvé.');
        return $this->redirectToRoute('user_create');
    }

    // Vérifier si l'utilisateur est déjà un admin
    if ($user instanceof Admin) {
        $this->addFlash('error', 'Cet utilisateur est déjà un administrateur.');
        return $this->redirectToRoute('admin_users');
    }

    // ✅ Convertir User en Admin
    $admin = new Admin();
    $admin->setEmail($user->getEmail());
    $admin->setNom($user->getNom());
    $admin->setPrenom($user->getPrenom());
    $admin->setPassword($user->getPassword());
    $admin->setRoles(array_unique(array_merge($user->getRoles(), ['ROLE_ADMIN'])));

    // 🔹 Création du formulaire d'Admin
    $form = $this->createForm(AdminType::class, $admin);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // 🔹 Remplir les champs spécifiques à Admin
        $admin->setAdresse($form->get('adresse')->getData());
        $admin->setTelephone($form->get('telephone')->getData());
        $admin->setDateNaissance($form->get('dateNaissance')->getData());

        // 🔹 Supprimer l'ancien User pour éviter un conflit d'ID
        $entityManager->remove($user);
        $entityManager->flush();

        // 🔹 Persister le Admin avec les nouvelles infos
        $entityManager->persist($admin);
        $entityManager->flush();

        // 🔹 Nettoyer la session après conversion
        $session->remove('user_id');

        // ✅ Succès
        $this->addFlash('success', 'Admin créé avec succès.');

        return $this->redirectToRoute('admin_users');
    }

    // 🔹 Afficher le formulaire si non soumis
    return $this->render('user/admin_create.html.twig', [
        'form' => $form->createView(),
    ]);
}

    #[Route('/admin/medical-record/new', name: 'admin_medical_record_new')]
    public function newMedicalRecord(Request $request, EntityManagerInterface $entityManager): Response
    {
        // TODO: Implement new medical record form
        return $this->render('admin/medical_record_form.html.twig', [
            'mode' => 'new'
        ]);
    }

    #[Route('/admin/medical-record/{id}/view', name: 'admin_medical_record_view')]
    public function viewMedicalRecord(int $id, UserRepository $userRepository): Response
    {
        $patient = $userRepository->find($id);
        
        if (!$patient) {
            throw $this->createNotFoundException('Patient non trouvé');
        }

        return $this->render('admin/medical_record_view.html.twig', [
            'patient' => $patient
        ]);
    }

    #[Route('/admin/medical-record/{id}/edit', name: 'admin_medical_record_edit')]
    public function editMedicalRecord(int $id, UserRepository $userRepository): Response
    {
        $patient = $userRepository->find($id);
        
        if (!$patient) {
            throw $this->createNotFoundException('Patient non trouvé');
        }

        return $this->render('admin/medical_record_form.html.twig', [
            'patient' => $patient,
            'mode' => 'edit'
        ]);
    }

    #[Route('/admin/medical-record/{id}/delete', name: 'admin_medical_record_delete', methods: ['POST'])]
    public function deleteMedicalRecord(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $patient = $userRepository->find($id);
        
        if (!$patient) {
            throw $this->createNotFoundException('Patient non trouvé');
        }

        if ($this->isCsrfTokenValid('delete'.$patient->getId(), $request->request->get('_token'))) {
            $entityManager->remove($patient);
            $entityManager->flush();
            $this->addFlash('success', 'Dossier médical supprimé avec succès');
        }

        return $this->redirectToRoute('admin_medical_records');
    }
}
