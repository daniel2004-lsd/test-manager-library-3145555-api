<?php

namespace App\Http\Controllers;

use App\Application\Services\CreateBookService;
use App\Application\Services\DeleteBookService;
use App\Application\Services\GetBookService;
use App\Application\Services\GetCoverService;
use App\Application\Services\RemoveCoverService;
use App\Application\Services\SearchBooksService;
use App\Application\Services\UpdateBookService;
use App\Application\Services\UploadCoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class BookController extends Controller
{
    public function __construct(
        private readonly SearchBooksService $search,
        private readonly GetBookService $get,
        private readonly CreateBookService $create,
        private readonly UpdateBookService $update,
        private readonly DeleteBookService $delete,
        private readonly UploadCoverService $uploadCover,
        private readonly RemoveCoverService $removeCover,
        private readonly GetCoverService $getCover,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json($this->search->execute(
            $data['q'] ?? null,
            $data['category_id'] ?? null,
            (int) ($data['page'] ?? 1),
            (int) ($data['size'] ?? 20),
        ));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->get->execute($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateBook($request);

        return response()->json(
            $this->create->execute($data['title'], $data['author'], $data['isbn'], $data['category_id'], (int) $data['total_copies']),
            201,
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $this->validateBook($request, forUpdate: true);

        return response()->json(
            $this->update->execute($id, $data['title'], $data['author'], $data['category_id'], (int) $data['total_copies']),
        );
    }

    public function destroy(string $id): Response
    {
        $this->delete->execute($id);

        return response()->noContent();
    }

    public function uploadCover(Request $request, string $id): JsonResponse
    {
        $request->validate(['file' => ['required', 'file']]);

        $file = $request->file('file');

        return response()->json(
            $this->uploadCover->execute($id, (string) $file->getRealPath(), (int) $file->getSize()),
        );
    }

    public function removeCover(string $id): JsonResponse
    {
        return response()->json($this->removeCover->execute($id));
    }

    public function cover(string $id): BinaryFileResponse
    {
        $file = $this->getCover->execute($id);

        return response()->file($file['path'], ['Content-Type' => $file['mime']]);
    }

    private function validateBook(Request $request, bool $forUpdate = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:250'],
            'author' => ['required', 'string', 'max:200'],
            'isbn' => $forUpdate ? ['prohibited'] : ['required', 'digits:13'],
            'category_id' => ['required', 'uuid'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ]);
    }
}
