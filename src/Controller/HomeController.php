<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\SubCategoryRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $data = $productRepository->findBy([], ['name' => 'ASC']);
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('home/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/home/product/{id}/show', name: 'app_home_product_show', methods: ['GET'])]
    public function show(Product $product, ProductRepository $productRepository): Response
    {
        $lastProducts = $productRepository->findBy([], ['id' => 'DESC'], limit: 12);

        return $this->render('home/show.html.twig', [
            'product' => $product,
            'products' => $lastProducts,
        ]);
    }

    #[Route('/home/product/subcategory/{id}/filter', name: 'app_home_filter', methods: ['GET'])]
    public function filter(
        int $id,
        SubCategoryRepository $subCategoryRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $subCategory = $subCategoryRepository->find($id);

        if (!$subCategory) {
            throw $this->createNotFoundException('SubCategory not found');
        }

        $products = $paginator->paginate(
            $subCategory->getProducts(),
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('home/filter.html.twig', [
            'products'    => $products,
            'category'    => $subCategory->getCategory(),
            'subCategory' => $subCategory,
        ]);
    }
}
