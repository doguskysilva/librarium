<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\InventoryImporter;
use App\Http\Requests\InventoryImportUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use JsonException;

class InventoryImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('inventory/Import');
    }

    public function preview(InventoryImportUploadRequest $request, InventoryImporter $importer): JsonResponse
    {
        return $this->runImport($request, $importer, apply: false);
    }

    public function apply(InventoryImportUploadRequest $request, InventoryImporter $importer): JsonResponse
    {
        return $this->runImport($request, $importer, apply: true);
    }

    private function runImport(InventoryImportUploadRequest $request, InventoryImporter $importer, bool $apply): JsonResponse
    {
        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            return response()->json([
                'message' => 'An inventory JSON file is required.',
                'errors' => ['file' => ['Choose a JSON file and try again.']],
            ], 422);
        }

        $path = $file->getRealPath();
        $contents = $path === false ? false : file_get_contents($path);

        if ($contents === false) {
            return response()->json([
                'message' => 'The uploaded inventory could not be read.',
                'errors' => ['file' => ['Choose the JSON file again and retry.']],
            ], 422);
        }

        try {
            $document = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json([
                'message' => 'The uploaded file is not valid JSON.',
                'errors' => ['file' => ['The selected file could not be parsed as JSON.']],
            ], 422);
        }

        if (! is_array($document) || array_is_list($document)) {
            return response()->json([
                'message' => 'The uploaded inventory must be a JSON object.',
                'errors' => ['file' => ['The top-level JSON value must be an object.']],
            ], 422);
        }

        $report = $importer->run($document, apply: $apply);

        if ($report['status'] === 'invalid') {
            $report['record_labels'] = $this->recordLabels($document);
        }

        return response()->json($report);
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, array<int, string>>
     */
    private function recordLabels(array $document): array
    {
        $labels = [];

        foreach (['authors', 'categories', 'collections', 'books'] as $entity) {
            if (! is_array($document[$entity] ?? null)) {
                continue;
            }

            foreach ($document[$entity] as $index => $record) {
                if (! is_array($record)) {
                    continue;
                }

                $identity = $entity === 'books' ? ($record['source_key'] ?? null) : ($record['name'] ?? null);
                $labels[$entity][$index] = is_string($identity) && $identity !== ''
                    ? $identity
                    : 'record '.($index + 1);
            }
        }

        return $labels;
    }
}
