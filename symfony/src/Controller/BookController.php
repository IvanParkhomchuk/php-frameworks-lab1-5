<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\Category;
use App\Repository\BookRepository;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/books')]
class BookController extends AbstractController
{
    #[Route('/', name: 'book_index', methods: ['GET'])]
    public function index(Request $request, BookRepository $bookRepository): JsonResponse
    {
         
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(100, (int)$request->query->get('limit', 10)));  
        $offset = ($page - 1) * $limit;
        
         
        $queryBuilder = $bookRepository->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')
            ->leftJoin('b.category', 'c')
            ->addSelect('a')
            ->addSelect('c');
        
         
        if ($title = $request->query->get('title')) {
            $queryBuilder->andWhere('b.title LIKE :title')
                ->setParameter('title', '%' . $title . '%');
        }
        
         
        if ($isbn = $request->query->get('isbn')) {
            $queryBuilder->andWhere('b.isbn LIKE :isbn')
                ->setParameter('isbn', '%' . $isbn . '%');
        }
        
         
        if ($pubYearFrom = $request->query->get('pub_year_from')) {
            try {
                $dateFrom = new \DateTime($pubYearFrom);
                $queryBuilder->andWhere('b.publicationYear >= :pub_year_from')
                    ->setParameter('pub_year_from', $dateFrom);
            } catch (\Exception $e) {
                 
            }
        }
        
        if ($pubYearTo = $request->query->get('pub_year_to')) {
            try {
                $dateTo = new \DateTime($pubYearTo);
                $queryBuilder->andWhere('b.publicationYear <= :pub_year_to')
                    ->setParameter('pub_year_to', $dateTo);
            } catch (\Exception $e) {
                 
            }
        }
        
         
        if ($request->query->has('min_copies')) {
            $minCopies = (int)$request->query->get('min_copies');
            $queryBuilder->andWhere('b.availableCopies >= :min_copies')
                ->setParameter('min_copies', $minCopies);
        }
        
        if ($request->query->has('max_copies')) {
            $maxCopies = (int)$request->query->get('max_copies');
            $queryBuilder->andWhere('b.availableCopies <= :max_copies')
                ->setParameter('max_copies', $maxCopies);
        }
        
         
        if ($authorId = $request->query->get('author_id')) {
            $queryBuilder->andWhere('b.author = :author_id')
                ->setParameter('author_id', $authorId);
        }
        
         
        if ($authorName = $request->query->get('author_name')) {
            $queryBuilder->andWhere('a.firstName LIKE :author_name OR a.lastName LIKE :author_name')
                ->setParameter('author_name', '%' . $authorName . '%');
        }
        
         
        if ($categoryId = $request->query->get('category_id')) {
            $queryBuilder->andWhere('b.category = :category_id')
                ->setParameter('category_id', $categoryId);
        }
        
         
        $validSortFields = ['title', 'isbn', 'publicationYear', 'availableCopies', 'id'];
        $sortField = in_array($request->query->get('sort_by'), $validSortFields) 
            ? $request->query->get('sort_by') : 'id';
        $sortOrder = $request->query->get('sort_order') === 'DESC' ? 'DESC' : 'ASC';
        
        $queryBuilder->orderBy('b.' . $sortField, $sortOrder);
        
         
        $countQuery = clone $queryBuilder;
        $total = $countQuery->select('COUNT(b.id)')->getQuery()->getSingleScalarResult();
        
         
        $queryBuilder->setFirstResult($offset)
                     ->setMaxResults($limit);
        
         
        $books = $queryBuilder->getQuery()->getResult();
        
        return $this->json([
            'data' => $books,
            'pagination' => [
                'total_items' => $total,
                'items_per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Book $book): JsonResponse
    {
        return $this->json($book);
    }

    #[Route('/create', name: 'book_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $author = $entityManager->getRepository(Author::class)->find($data['author_id']);
        $category = $entityManager->getRepository(Category::class)->find($data['category_id']);

        if (!$author || !$category) {
            return $this->json(['error' => 'Invalid author or category ID'], 400);
        }

        $book = new Book();
        $book->setTitle($data['title']);
        $book->setIsbn($data['isbn']);
        $book->setPublicationYear(new \DateTime($data['publication_year']));
        $book->setAvailableCopies($data['available_copies']);
        $book->setAuthor($author);
        $book->setCategory($category);

        $entityManager->persist($book);
        $entityManager->flush();

        return $this->json($book, 201);
    }

    #[Route('/{id}/update', name: 'book_update', methods: ['PUT'])]
    public function update(Request $request, Book $book, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $author = $entityManager->getRepository(Author::class)->find($data['author_id']);
        $category = $entityManager->getRepository(Category::class)->find($data['category_id']);

        if (!$author || !$category) {
            return $this->json(['error' => 'Invalid author or category ID'], 400);
        }

        $book->setTitle($data['title']);
        $book->setIsbn($data['isbn']);
        $book->setPublicationYear(new \DateTime($data['publication_year']));
        $book->setAvailableCopies($data['available_copies']);
        $book->setAuthor($author);
        $book->setCategory($category);

        $entityManager->flush();

        return $this->json($book);
    }

    #[Route('/{id}/delete', name: 'book_delete', methods: ['DELETE'])]
    public function delete(Book $book, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($book);
        $entityManager->flush();

        return $this->json(['message' => 'Book deleted successfully']);
    }

    #[Route('/{id}/loans', name: 'book_loans', methods: ['GET'])]
    public function loans(Book $book, LoanRepository $loanRepository): JsonResponse
    {
        $loans = $loanRepository->findBy(['book' => $book]);
        return $this->json($loans);
    }
}