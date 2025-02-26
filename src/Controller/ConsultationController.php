<?php

namespace App\Controller;

use App\Entity\Service;
use App\Entity\Consultation;
use App\Form\ConsultationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Repository\ConsultationRepository;
use App\Service\TwilioSmsService;  // Import the Twilio service
use Psr\Log\LoggerInterface; 

final class ConsultationController extends AbstractController
{
    private TwilioSmsService $twilioSmsService;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;  // Use Psr\Log\LoggerInterface

    // Inject LoggerInterface into the constructor
    public function __construct(TwilioSmsService $twilioSmsService, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->twilioSmsService = $twilioSmsService;
        $this->em = $em;
        $this->logger = $logger;  // Assign logger to the property
    }

     

    #[Route('/consultation', name: 'app_consultation', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TwilioSmsService $twilioSmsService, LoggerInterface $logger): Response
    {
        // Create a new consultation object
        $consultation = new Consultation();
    
        // Dynamically set the status if needed (default to "In Progress")
        $status = $request->get('status', Consultation::STATUS_IN_PROGRESS);
        $consultation->setStatus($status);
    
        // Create the form
        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);
    
        // Form submission and validation
        if ($form->isSubmitted() && $form->isValid()) {
            $patientIdentifier = $consultation->getPatientIdentifier();
    
            // Validate patientIdentifier is not empty
            if (empty($patientIdentifier)) {
                $this->addFlash('error', 'Patient Identifier cannot be empty.');
                return $this->redirectToRoute('app_consultation');
            }
    
            // Check for an existing consultation for the same patient
            $existingConsultation = $entityManager->getRepository(Consultation::class)
                ->findOneBy(['patientIdentifier' => $patientIdentifier]);
    
            if ($existingConsultation) {
                $this->addFlash('error', 'A consultation for this patient already exists.');
                return $this->redirectToRoute('app_consultation');
            }
    
            // Persist and save the consultation
            $entityManager->persist($consultation);
            $entityManager->flush();
    
            // Ensure the phone number starts with "+216" for Tunisia
            $patientPhoneNumber = $consultation->getPhoneNumber();
    
            // Check if the phone number starts with the country code "+216"
            if (!str_starts_with($patientPhoneNumber, '+216')) {
                $patientPhoneNumber = '+216' . $patientPhoneNumber;
            }
    
            // Send SMS using TwilioSmsService
            if ($patientPhoneNumber) {
                $response = $twilioSmsService->sendSms(
                    $patientPhoneNumber,
                    'Your consultation has been successfully added!'
                );
                
                // Log Twilio Response (optional)
                $logger->info("Twilio Response: {$response}");
                $this->addFlash('success', 'SMS sent successfully!');
            } else {
                $this->addFlash('error', 'No phone number found for the patient.');
            }
    
            // Redirect to the confirmation page
            return $this->redirectToRoute('app_consultation_confirmation', [
                'patientIdentifier' => $patientIdentifier ?? 'No Identifier',
            ]);
        }
    
        // Get all services for the form
        $services = $entityManager->getRepository(Service::class)->findAll();
    
        return $this->render('consultation/new.html.twig', [
            'form' => $form->createView(),
            'services' => $services,
        ]);
    }
    
     
     

    // Route for consultation confirmation
    #[Route('/consultation/confirmation/{patientIdentifier}', name: 'app_consultation_confirmation')]
    public function consultationConfirmation(string $patientIdentifier): Response
    {
        return $this->render('consultation/confirmation.html.twig', [
            'patientIdentifier' => $patientIdentifier,
        ]);
    }

    // Route for viewing consultations of a patient
    #[Route('/patient/consultations/{patientIdentifier}', name: 'app_patient_consultations')]
    public function patientConsultations(string $patientIdentifier, EntityManagerInterface $entityManager): Response
    {
        $consultations = $entityManager->getRepository(Consultation::class)
            ->findBy(['patientIdentifier' => $patientIdentifier]);

        if (!$consultations) {
            $this->addFlash('info', 'No consultations found for this patient.');
        }

        return $this->render('consultation/view.html.twig', [
            'consultations' => $consultations,
            'patientIdentifier' => $patientIdentifier,
        ]);
    }

    #[Route('/consultation/search', name: 'app_search_consultation')]
    public function searchConsultation(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder()
            ->add('patientIdentifier', TextType::class, [
                'label' => 'Enter Patient Identifier',
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $patientIdentifier = $form->getData()['patientIdentifier'];

            // If patientIdentifier is empty, do not redirect but show a message
            if (empty($patientIdentifier)) {
                return $this->render('consultation/search.html.twig', [
                    'form' => $form->createView(),
                    'message' => 'Please enter a patient identifier to search.',
                ]);
            }

            // Proceed with the normal redirect if identifier is valid (non-empty)
            return $this->redirectToRoute('app_patient_consultations', [
                'patientIdentifier' => $patientIdentifier,
            ]);
        }

        return $this->render('consultation/search.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Route for editing a consultation (updated version)
    #[Route('/consultation/{id}/edit', name: 'app_consultation_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $consultation = $entityManager->getRepository(Consultation::class)->find($id);
        if (!$consultation) {
            throw $this->createNotFoundException('Consultation not found.');
        }

        $form = $this->createForm(ConsultationType::class, $consultation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Consultation updated successfully!');
            return $this->redirectToRoute('app_patient_consultations', [
                'patientIdentifier' => $consultation->getPatientIdentifier(),
            ]);
        }

        return $this->render('consultation/edit.html.twig', [
            'form' => $form->createView(),
            'consultation' => $consultation,
        ]);
    }

    // Route for deleting a consultation
    #[Route('/consultation/{id}/delete', name: 'app_delete_consultation')]
    public function delete(int $id, EntityManagerInterface $entityManager): Response
    {
        $consultation = $entityManager->getRepository(Consultation::class)->find($id);

        if (!$consultation) {
            $this->addFlash('error', 'Consultation not found.');
            return $this->redirectToRoute('app_consultation');
        }

        $entityManager->remove($consultation);
        $entityManager->flush();

        $this->addFlash('success', 'Consultation successfully deleted.');
        return $this->redirectToRoute('app_patient_consultations', [
            'patientIdentifier' => $consultation->getPatientIdentifier(),
        ]);
    }

    // Admin route for viewing all consultations
    #[Route('/admin/consultations', name: 'app_admin_consultations')]
    public function adminConsultations(EntityManagerInterface $entityManager): Response
    {
        $consultations = $entityManager->getRepository(Consultation::class)->findAll();

        return $this->render('admin/consultations.html.twig', [
            'consultations' => $consultations,
        ]);
    }

    // src/Controller/ConsultationController.php
// src/Controller/ConsultationController.php
#[Route('/consultation/{id}/edit-status', name: 'app_edit_consultation_status')]
public function editStatus(Consultation $consultation, Request $request, TwilioSmsService $twilioSmsService, LoggerInterface $logger): Response
{
    if ($request->isMethod('POST')) {
        $status = $request->request->get('status');
        $consultation->setStatus($status);  // Update status

        // Log status update
        $logger->info("Status for Consultation ID {$consultation->getId()} updated to: {$status}");

        // Save to DB
        $this->em->flush();

        // Get the patient's phone number
        $patientPhoneNumber = $consultation->getPhoneNumber(); // Ensure this method returns the phone number

        // Log the phone number
        $logger->info("Patient Phone Number: {$patientPhoneNumber}");

        // Ensure the phone number starts with the country code "+216" for Tunisia
        if ($patientPhoneNumber && !str_starts_with($patientPhoneNumber, '+216')) {
            $patientPhoneNumber = '+216' . $patientPhoneNumber;
        }

        // Check if we have a valid phone number
        if ($patientPhoneNumber) {
            // Send SMS using the TwilioSmsService
            $response = $twilioSmsService->sendSms(
                $patientPhoneNumber, 
                'Your consultation status has been updated.'
            );

            // Log Twilio Response
            $logger->info("Twilio Response: {$response}");

            // Optionally, add a success message
            $this->addFlash('success', 'SMS Sent successfully!');
        } else {
            $this->addFlash('error', 'No phone number found for the patient.');
            $logger->error("No phone number found for the patient in Consultation ID {$consultation->getId()}");
        }

        // Add success message for status update
        $this->addFlash('success', 'Status updated successfully.');

        return $this->redirectToRoute('app_admin_consultations');
    }

    return $this->render('consultation/edit_status.html.twig', [
        'consultation' => $consultation,
    ]);
}



 
    // Route for listing consultations
    #[Route('/consultations', name: 'app_consultations_list')]
    public function listConsultations(ConsultationRepository $consultationRepository)
    {
        // Fetch consultations from the repository
        $consultations = $consultationRepository->findAll();

        return $this->render('admin/consultations_list.html.twig', [
            'consultations' => $consultations,
        ]);
    }
}
