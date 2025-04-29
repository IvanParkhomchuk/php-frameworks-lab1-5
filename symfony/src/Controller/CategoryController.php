<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Book;
use App\Repository\CategoryRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/categorys')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'category_index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categoryRepository): JsonResponse
    {
         
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(100, (int)$request->query->get('limit', 10)));  
        $offset = ($page - 1) * $limit;
        
         
        $queryBuilder = $categoryRepository->createQueryBuilder('c');
        
         
        if ($name = $request->query->get('name')) {
            $queryBuilder->andWhere('c.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }
        
         
        if ($description = $request->query->get('description')) {
            $queryBuilder->andWhere('c.description LIKE :description')
                ->setParameter('description', '%' . $description . '%');
        }
        
         
        $validSortFields = ['name', 'id'];
        $sortField = in_array($request->query->get('sort_by'), $validSortFields) 
            ? $request->query->get('sort_by') : 'id';
        $sortOrder = $request->query->get('sort_order') === 'DESC' ? 'DESC' : 'ASC';
        
        $queryBuilder->orderBy('c.' . $sortField, $sortOrder);
        
         
        $countQuery = clone $queryBuilder;
        $total = $countQuery->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
        
         
        $queryBuilder->setFirstResult($offset)
                     ->setMaxResults($limit);
        
         
        $categories = $queryBuilder->getQuery()->getResult();
        
        return $this->json([
            'data' => $categories,
            'pagination' => [
                'total_items' => $total,
                'items_per_page' => $limit,
                'current_page' => $page,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/{id}', name: 'category_show', methods: ['GET'])]
    public function show(Category $category): JsonResponse
    {
        return $this->json($category);
    }

    #[Route('/create', name: 'category_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $category = new Category();
        $category->setName($data['name']);
        $category->setDescription($data['description'] ?? null);

        $entityManager->persist($category);
        $entityManager->flush();

        return $this->json($category, 201);
    }

    #[Route('/{id}/update', name: 'category_update', methods: ['PUT'])]
    public function update(Request $request, Category $category, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $category->setName($data['name']);
        $category->setDescription($data['description'] ?? null);

        $entityManager->flush();

        return $this->json($category);
    }

    #[Route('/{id}/delete', name: 'category_delete', methods: ['DELETE'])]
    public function delete(Category $category, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($category);
        $entityManager->flush();

        return $this->json(['message' => 'Category deleted successfully']);
    }

    #[Route('/{id}/books', name: 'category_books', methods: ['GET'])]
    public function books(Category $category, BookRepository $bookRepository): JsonResponse
    {
        $books = $bookRepository->findBy(['category' => $category]);
        return $this->json($books);
    }
}