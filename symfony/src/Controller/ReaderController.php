<?php

namespace App\Controller;

use App\Entity\Reader;
use App\Repository\ReaderRepository;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/readers')]
class ReaderController extends AbstractController
{
    #[Route('/', name: 'reader_index', methods: ['GET'])]
    public function index(Request $request, ReaderRepository $readerRepository): JsonResponse
    {
         
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(100, (int)$request->query->get('limit', 10)));  
        $offset = ($page - 1) * $limit;
        
         
        $queryBuilder = $readerRepository->createQueryBuilder('r');
        
         
        if ($firstName = $request->query->get('first_name')) {
            $queryBuilder->andWhere('r.firstName LIKE :first_name')
                ->setParameter('first_name', '%' . $firstName . '%');
        }
        
         
        if ($lastName = $request->query->get('last_name')) {
            $queryBuilder->andWhere('r.lastName LIKE :last_name')
                ->setParameter('last_name', '%' . $lastName . '%');
        }
        
         
        if ($email = $request->query->get('email')) {
            $queryBuilder->andWhere('r.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }
        
         
        if ($phone = $request->query->get('phone')) {
            $queryBuilder->andWhere('r.phone LIKE :phone')
                ->setParameter('phone', '%' . $phone . '%');
        }
        
         
        if ($address = $request->query->get('address')) {
            $queryBuilder->andWhere('r.address LIKE :address')
                ->setParameter('address', '%' . $address . '%');
        }
        
         
        if ($regDateFrom = $request->query->get('registration_date_from')) {
            try {
                $dateFrom = new \DateTime($regDateFrom);
                $queryBuilder->andWhere('r.registrationDate >= :reg_date_from')
                    ->setParameter('reg_date_from', $dateFrom);
            } catch (\Exception $e) {
                 
            }
        }
        
        if ($regDateTo = $request->query->get('registration_date_to')) {
            try {
                $dateTo = new \DateTime($regDateTo);
                $queryBuilder->andWhere('r.registrationDate <= :reg_date_to')
                    ->setParameter('reg_date_to', $dateTo);
            } catch (\Exception $e) {
                 
            }
        }
        
         
        $validSortFields = ['firstName', 'lastName', 'email', 'registrationDate', 'id'];
        $sortField = in_array($request->query->get('sort_by'), $validSortFields) 
            ? $request->query->get('sort_by') : 'id';
        $sortOrder = $request->query->get('sort_order') === 'DESC' ? 'DESC' : 'ASC';
        
        $queryBuilder->orderBy('r.' . $sortField, $sortOrder);
        
         
        $countQuery = clone $queryBuilder;
        $total = $countQuery->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();
        
         
        $queryBuilder->setFirstResult($offset)
                     ->setMaxResults($limit);
        
         
        $readers = $queryBuilder->getQuery()->getResult();
        
        return $this->json([
            'data' => $readers,
            'pagination' => [
                'total_items' => $total,
                'items_per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/{id}', name: 'reader_show', methods: ['GET'])]
    public function show(Reader $reader): JsonResponse
    {
        return $this->json($reader);
    }

    #[Route('/create', name: 'reader_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $reader = new Reader();
        $reader->setFirstName($data['first_name']);
        $reader->setLastName($data['last_name']);
        $reader->setEmail($data['email']);
        $reader->setPhone($data['phone'] ?? null);
        $reader->setAddress($data['address'] ?? null);
        $reader->setRegistrationDate(new \DateTime($data['registration_date'] ?? 'now'));

        $entityManager->persist($reader);
        $entityManager->flush();

        return $this->json($reader, 201);
    }

    #[Route('/{id}/update', name: 'reader_update', methods: ['PUT'])]
    public function update(Request $request, Reader $reader, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['first_name'])) {
            $reader->setFirstName($data['first_name']);
        }
        
        if (isset($data['last_name'])) {
            $reader->setLastName($data['last_name']);
        }
        
        if (isset($data['email'])) {
            $reader->setEmail($data['email']);
        }
        
        if (array_key_exists('phone', $data)) {
            $reader->setPhone($data['phone']);
        }
        
        if (array_key_exists('address', $data)) {
            $reader->setAddress($data['address']);
        }
        
        if (isset($data['registration_date'])) {
            $reader->setRegistrationDate(new \DateTime($data['registration_date']));
        }

        $entityManager->flush();

        return $this->json($reader);
    }

    #[Route('/{id}/delete', name: 'reader_delete', methods: ['DELETE'])]
    public function delete(Reader $reader, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($reader);
        $entityManager->flush();

        return $this->json(['message' => 'Reader deleted successfully']);
    }

    #[Route('/{id}/loans', name: 'reader_loans', methods: ['GET'])]
    public function loans(Reader $reader, LoanRepository $loanRepository): JsonResponse
    {
        $loans = $loanRepository->findBy(['reader' => $reader]);
        return $this->json($loans);
    }
    
    #[Route('/{id}/active-loans', name: 'reader_active_loans', methods: ['GET'])]
    public function activeLoans(Reader $reader, LoanRepository $loanRepository): JsonResponse
    {
        $activeLoans = $loanRepository->findBy([
            'reader' => $reader,
            'status' => 'borrowed'
        ]);
        return $this->json($activeLoans);
    }
    
    #[Route('/{id}/overdue-loans', name: 'reader_overdue_loans', methods: ['GET'])]
    public function overdueLoans(Reader $reader, LoanRepository $loanRepository): JsonResponse
    {
        $today = new \DateTime();
        $overdueLoans = $loanRepository->createQueryBuilder('l')
            ->where('l.reader = :reader')
            ->andWhere('l.due_date < :today')
            ->andWhere('l.status = :status')
            ->setParameter('reader', $reader)
            ->setParameter('today', $today)
            ->setParameter('status', 'borrowed')
            ->getQuery()
            ->getResult();

        return $this->json($overdueLoans);
    }
}