<?php

namespace App\Controller;

use App\Entity\Loan;
use App\Entity\Book;
use App\Entity\Reader;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/loans')]
class LoanController extends AbstractController
{
    #[Route('/', name: 'loan_index', methods: ['GET'])]
    public function index(Request $request, LoanRepository $loanRepository): JsonResponse
    {
         
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(100, (int)$request->query->get('limit', 10)));  
        $offset = ($page - 1) * $limit;
        
         
        $queryBuilder = $loanRepository->createQueryBuilder('l')
            ->leftJoin('l.book', 'b')
            ->leftJoin('l.reader', 'r')
            ->addSelect('b')
            ->addSelect('r');
        
         
        if ($bookId = $request->query->get('book_id')) {
            $queryBuilder->andWhere('l.book = :book_id')
                ->setParameter('book_id', $bookId);
        }
        
         
        if ($bookTitle = $request->query->get('book_title')) {
            $queryBuilder->andWhere('b.title LIKE :book_title')
                ->setParameter('book_title', '%' . $bookTitle . '%');
        }
        
         
        if ($readerId = $request->query->get('reader_id')) {
            $queryBuilder->andWhere('l.reader = :reader_id')
                ->setParameter('reader_id', $readerId);
        }
        
         
        if ($readerName = $request->query->get('reader_name')) {
            $queryBuilder->andWhere('r.firstName LIKE :reader_name OR r.lastName LIKE :reader_name')
                ->setParameter('reader_name', '%' . $readerName . '%');
        }
        
         
        if ($status = $request->query->get('status')) {
            $queryBuilder->andWhere('l.status = :status')
                ->setParameter('status', $status);
        }
        
         
        if ($loanDateFrom = $request->query->get('loan_date_from')) {
            try {
                $dateFrom = new \DateTime($loanDateFrom);
                $queryBuilder->andWhere('l.loanDate >= :loan_date_from')
                    ->setParameter('loan_date_from', $dateFrom);
            } catch (\Exception $e) {
                 
            }
        }
        
        if ($loanDateTo = $request->query->get('loan_date_to')) {
            try {
                $dateTo = new \DateTime($loanDateTo);
                $queryBuilder->andWhere('l.loanDate <= :loan_date_to')
                    ->setParameter('loan_date_to', $dateTo);
            } catch (\Exception $e) {
                 
            }
        }
        
         
        if ($dueDateFrom = $request->query->get('due_date_from')) {
            try {
                $dateFrom = new \DateTime($dueDateFrom);
                $queryBuilder->andWhere('l.dueDate >= :due_date_from')
                    ->setParameter('due_date_from', $dateFrom);
            } catch (\Exception $e) {
                 
            }
        }
        
        if ($dueDateTo = $request->query->get('due_date_to')) {
            try {
                $dateTo = new \DateTime($dueDateTo);
                $queryBuilder->andWhere('l.dueDate <= :due_date_to')
                    ->setParameter('due_date_to', $dateTo);
            } catch (\Exception $e) {
                 
            }
        }
        
         
        if ($returnDateFrom = $request->query->get('return_date_from')) {
            try {
                $dateFrom = new \DateTime($returnDateFrom);
                $queryBuilder->andWhere('l.returnDate >= :return_date_from')
                    ->setParameter('return_date_from', $dateFrom);
            } catch (\Exception $e) {
                 
            }
        }
        
        if ($returnDateTo = $request->query->get('return_date_to')) {
            try {
                $dateTo = new \DateTime($returnDateTo);
                $queryBuilder->andWhere('l.returnDate <= :return_date_to')
                    ->setParameter('return_date_to', $dateTo);
            } catch (\Exception $e) {
                 
            }
        }
        
         
        if ($request->query->has('overdue') && $request->query->get('overdue') === '1') {
            $today = new \DateTime();
            $queryBuilder->andWhere('l.dueDate < :today AND (l.returnDate IS NULL OR l.status != :returned_status)')
                ->setParameter('today', $today)
                ->setParameter('returned_status', 'returned');
        }
        
         
        $validSortFields = ['id', 'loanDate', 'dueDate', 'returnDate', 'status'];
        $sortField = in_array($request->query->get('sort_by'), $validSortFields) 
            ? $request->query->get('sort_by') : 'id';
        $sortOrder = $request->query->get('sort_order') === 'DESC' ? 'DESC' : 'ASC';
        
        $queryBuilder->orderBy('l.' . $sortField, $sortOrder);
        
         
        $countQuery = clone $queryBuilder;
        $total = $countQuery->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();
        
         
        $queryBuilder->setFirstResult($offset)
                     ->setMaxResults($limit);
        
         
        $loans = $queryBuilder->getQuery()->getResult();
        
        return $this->json([
            'data' => $loans,
            'pagination' => [
                'total_items' => $total,
                'items_per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/{id}', name: 'loan_show', methods: ['GET'])]
    public function show(Loan $loan): JsonResponse
    {
        return $this->json($loan);
    }

    #[Route('/create', name: 'loan_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $book = $entityManager->getRepository(Book::class)->find($data['book_id']);
        $reader = $entityManager->getRepository(Reader::class)->find($data['reader_id']);

        if (!$book || !$reader) {
            return $this->json(['error' => 'Invalid book or reader ID'], 400);
        }

         
        if ($book->getAvailableCopies() <= 0) {
            return $this->json(['error' => 'No available copies of this book'], 400);
        }

        $loan = new Loan();
        $loan->setBook($book);
        $loan->setReader($reader);
        $loan->setLoanDate(new \DateTime($data['loan_date'] ?? 'now'));
        $loan->setDueDate(new \DateTime($data['due_date']));
        $loan->setReturnDate(isset($data['return_date']) ? new \DateTime($data['return_date']) : null);
        $loan->setStatus($data['status'] ?? 'borrowed');

         
        $book->setAvailableCopies($book->getAvailableCopies() - 1);

        $entityManager->persist($loan);
        $entityManager->flush();

        return $this->json($loan, 201);
    }

    #[Route('/{id}/update', name: 'loan_update', methods: ['PUT'])]
    public function update(Request $request, Loan $loan, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

         
        if (isset($data['book_id']) && $loan->getBook()->getId() != $data['book_id']) {
            $oldBook = $loan->getBook();
            $newBook = $entityManager->getRepository(Book::class)->find($data['book_id']);
            
            if (!$newBook) {
                return $this->json(['error' => 'Invalid book ID'], 400);
            }
            
             
            $oldBook->setAvailableCopies($oldBook->getAvailableCopies() + 1);
            
             
            if ($newBook->getAvailableCopies() <= 0) {
                return $this->json(['error' => 'No available copies of the new book'], 400);
            }
            $newBook->setAvailableCopies($newBook->getAvailableCopies() - 1);
            
            $loan->setBook($newBook);
        }

         
        if (isset($data['reader_id'])) {
            $reader = $entityManager->getRepository(Reader::class)->find($data['reader_id']);
            if (!$reader) {
                return $this->json(['error' => 'Invalid reader ID'], 400);
            }
            $loan->setReader($reader);
        }

        if (isset($data['loan_date'])) {
            $loan->setLoanDate(new \DateTime($data['loan_date']));
        }
        
        if (isset($data['due_date'])) {
            $loan->setDueDate(new \DateTime($data['due_date']));
        }
        
        if (isset($data['return_date'])) {
            $loan->setReturnDate(new \DateTime($data['return_date']));
        }
        
        if (isset($data['status'])) {
            $oldStatus = $loan->getStatus();
            $newStatus = $data['status'];
            $loan->setStatus($newStatus);
            
             
            if ($oldStatus === 'borrowed' && $newStatus === 'returned') {
                $book = $loan->getBook();
                $book->setAvailableCopies($book->getAvailableCopies() + 1);
                
                 
                if (!$loan->getReturnDate()) {
                    $loan->setReturnDate(new \DateTime());
                }
            }
        }

        $entityManager->flush();

        return $this->json($loan);
    }

    #[Route('/{id}/delete', name: 'loan_delete', methods: ['DELETE'])]
    public function delete(Loan $loan, EntityManagerInterface $entityManager): JsonResponse
    {
         
        if ($loan->getStatus() === 'borrowed') {
            $book = $loan->getBook();
            $book->setAvailableCopies($book->getAvailableCopies() + 1);
        }

        $entityManager->remove($loan);
        $entityManager->flush();

        return $this->json(null, 204);
    }
}