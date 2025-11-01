<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getGlobals(): array
    {
        return [
            // "categories" sera dispo dans TOUS tes templates Twig
            'categories' => $this->categoryRepository->findAll(),
        ];
    }
}
?>