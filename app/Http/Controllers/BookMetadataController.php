<?php

namespace App\Http\Controllers;

use App\Actions\BookMetadata\FindBookMetadataByIsbn;
use App\Http\Requests\LookupBookMetadataRequest;
use Illuminate\Http\JsonResponse;

class BookMetadataController extends Controller
{
    public function lookup(LookupBookMetadataRequest $request, FindBookMetadataByIsbn $action): JsonResponse
    {
        return response()->json($action->handle($request->validated('isbn')));
    }
}
