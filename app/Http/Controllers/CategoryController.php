<?php

namespace App\Http\Controllers;

use App\Application\Services\ListCategoriesService;
use Illuminate\Http\JsonResponse;

final class CategoryController extends Controller
{
    public function __construct(private readonly ListCategoriesService $listCategories) {}

    public function index(): JsonResponse
    {
        return response()->json(['items' => $this->listCategories->execute()]);
    }
}
