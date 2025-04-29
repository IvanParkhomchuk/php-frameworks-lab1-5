<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/authors')]
class AuthorController extends AbstractController
{
    #[Route('/', name: 'author_index', methods: ['GET'])]
    public function index(Request $request, AuthorRepository $authorRepository): JsonResponse
    {
         
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(100, (int)$request->query->get('limit', 10)));  
        $offset = ($page - 1) * $limit;
        
         
        $queryBuilder = $authorRepository->createQueryBuilder('a');
        
         
        if ($firstName = $request->query->get('first_name')) {
            $queryBuilder->andWhere('a.firstName LIKE :first_name')
                ->setParameter('first_name', '%' . $firstName . '%');
        }
        
         
        if ($lastName = $request->query->get('last_name')) {
            $queryBuilder->andWhere('a.lastName LIKE :last_name')
                ->setParameter('last_name', '%' . $lastName . '%');
        }
        
         
        if ($biography = $request->query->get('biography')) {
            $queryBuilder->andWhere('a.biography LIKE :biography')
                ->setParameter('biography', '%' . $biography . '%');
        }
        
         
        if ($birthDateFrom = $request->query->get('birth_date_from')) {
            try {
                $dateFrom = new \DateTime($birthDateFrom);
                $queryBuilder->andWhere('a.birthDate >= :birth_date_from')
                    ->setParameter('birth_date_from', $dateFrom);
            } catch (\Exception $e) {
                 
            }
        }
        
        if ($birthDateTo = $request->query->get('birth_date_to')) {
            try {
                $dateTo = new \DateTime($birthDateTo);
                $queryBuilder->andWhere('a.birthDate <= :birth_date_to')
                    ->setParameter('birth_date_to', $dateTo);
            } catch (\Exception $e) {
                 
            }
        }
        
         
        $sortField = in_array($request->query->get('sort_by'), ['firstName', 'lastName', 'birthDate', 'id']) 
            ? $request->query->get('sort_by') : 'id';
        $sortOrder = $request->query->get('sort_order') === 'DESC' ? 'DESC' : 'ASC';
        
        $queryBuilder->orderBy('a.' . $sortField, $sortOrder);
        
         
        $countQuery = clone $queryBuilder;
        $total = $countQuery->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();
        
         
        $queryBuilder->setFirstResult($offset)
                     ->setMaxResults($limit);
        
         
        $authors = $queryBuilder->getQuery()->getResult();
        
        return $this->json([
            'data' => $authors,
            'pagination' => [
                'total_items' => $total,
                'items_per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/{id}', name: 'author_show', methods: ['GET'])]
    public function show(Author $author): JsonResponse
    {
        return $this->json($author);
    }

    #[Route('/create', name: 'author_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $author = new Author();
        $author->setFirstName($data['first_name']);
        $author->setLastName($data['last_name']);
        $author->setBiography($data['biography'] ?? null);
        $author->setBirthDate(new \DateTime($data['birth_date']));

        $entityManager->persist($author);
        $entityManager->flush();

        return $this->json($author, 201);
    }

    #[Route('/{id}/update', name: 'author_update', methods: ['PUT'])]
    public function update(Request $request, Author $author, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['first_name'])) {
            $author->setFirstName($data['first_name']);
        }

        if (isset($data['last_name'])) {
            $author->setLastName($data['last_name']);
        }

        $author->setBiography($data['biography'] ?? $author->getBiography());

        if (array_key_exists('birth_date', $data)) {
            if ($data['birth_date'] === null || $data['birth_date'] === '') {
                $author->setBirthDate(null);
            } else {
                $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
                if (!is_string($data['birth_date']) || !preg_match($datePattern, $data['birth_date'])) {
                    return $this->json([
                        'error' => 'Invalid date format. Please use YYYY-MM-DD format.'
                    ], 400);
                }
                
                try {
                    $birthDate = new \DateTime($data['birth_date']);
                    $author->setBirthDate($birthDate);
                } catch (\Exception $e) {
                    return $this->json([
                        'error' => 'Invalid date. Please provide a valid date.'
                    ], 400);
                }
            }
        }

        $entityManager->flush();

        return $this->json($author);
    }

    #[Route('/{id}/delete', name: 'author_delete', methods: ['DELETE'])]
    public function delete(Author $author, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($author);
        $entityManager->flush();

        return $this->json(['message' => 'Author deleted successfully']);
    }

    #[Route('/{id}/books', name: 'author_books', methods: ['GET'])]
    public function books(Author $author, BookRepository $bookRepository): JsonResponse
    {
        $books = $bookRepository->findBy(['author' => $author]);
        return $this->json($books);
    }
}