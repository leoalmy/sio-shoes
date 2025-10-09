<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductForm;
use App\Repository\ProductRepository;
use App\Entity\AddProductHistory;
use App\Form\AddProductHistoryForm;
use App\Repository\AddProductHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/editor/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $query = $productRepository->createQueryBuilder('p')
            ->orderBy('p.id', 'ASC')
            ->getQuery();

        $products = $paginator->paginate(
            $query, /* Requête ou tableau */
            $request->query->getInt('page', 1), /* Numéro de page (par défaut 1) */
            10 /* Nombre d’éléments par page */
        );

        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductForm::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();

            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalName);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();
                try {
                    $image->move(
                        $this->getParameter('image_directory'),
                        $newFilename
                    );
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de l\'image.');
                    return $this->redirectToRoute('app_product_index');
                }
                $product->setImage($newFilename); 
            }
            $entityManager->persist($product);
            $entityManager->flush();

            $stockHistory = new AddProductHistory();
            $stockHistory->setQte($product->getStock());
            $stockHistory->setProduct($product);
            $stockHistory->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($stockHistory);
            $entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès.');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductForm::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();

            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalName);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$image->guessExtension();
                try {
                    $image->move(
                        $this->getParameter('image_directory'),
                        $newFilename
                    );
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de l\'image.');
                    return $this->redirectToRoute('app_product_index');
                }
                $product->setImage($newFilename); 
            }
            $entityManager->flush();

            $this->addFlash('success', 'Produit mis à jour avec succès.');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        $this->addFlash('danger', 'Produit supprimé avec succès.');
        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/add/product/{id}/stock', name: 'app_product_stock_add', methods: ['GET', 'POST'])]
    public function addStock($id, EntityManagerInterface $entityManager, Request $request, ProductRepository $productRepository): Response
    {
        $addStock = new AddProductHistory();
        $form = $this->createForm(AddProductHistoryForm::class, $addStock);
        $form->handleRequest($request);

        // ✅ Check if product exists
        $product = $productRepository->find($id);
        if (!$product) {
            $this->addFlash('danger', 'Produit non trouvé.');
            return $this->redirectToRoute('app_product_index');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ Check quantity validity
            if ($addStock->getQte() > 0) {
                $newQte = $product->getStock() + $addStock->getQte();
                $product->setStock($newQte);

                $addStock->setCreatedAt(new \DateTimeImmutable());
                $addStock->setProduct($product);

                $entityManager->persist($addStock);
                $entityManager->flush();

                $this->addFlash('success', 'Le stock du produit a été modifié !');
                return $this->redirectToRoute('app_product_index');
            } else {
                $this->addFlash('danger', "Impossible de mettre à jour le stock ! La quantité doit être > 0");
                return $this->redirectToRoute('app_product_stock_add', ['id' => $product->getId()]);
            }
        }

        return $this->render('product/addStock.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }

    #[Route('/add/product/{id}/stock/history', name: 'app_product_stock_add_history', methods: ['GET'])]
    public function stockHistory($id, ProductRepository $productrepository, AddProductHistoryRepository $addProductHistoryRepository): Response
    {
        $product = $productrepository->find($id);
        if (!$product) {
            $this->addFlash('danger', 'Produit non trouvé.');
            return $this->redirectToRoute('app_product_index');
        }

        $productAddedHistory = $addProductHistoryRepository->findBy(['product' => $product], ['id' => 'DESC']);

        return $this->render('product/addedStockHistory.html.twig', [
            'productsAdded' => $productAddedHistory,
            'product' => $product,
        ]);
    }
}
