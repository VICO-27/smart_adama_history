<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Http\Requests\Books\StoreSectionRequest;
use App\Http\Requests\Books\UpdateSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Chapter;
use App\Models\Section;
use Illuminate\Http\JsonResponse;

/**
 * Admin section management (Req 3.2, 3.4)
 */
class AdminSectionController extends Controller
{
    /**
     * POST /admin/chapters/{chapter}/sections
     */
    public function store(StoreSectionRequest $request, Chapter $chapter): JsonResponse
    {
        $order = $request->order
            ?? ($chapter->sections()->max('order') ?? 0) + 1;

        $section = $chapter->sections()->create([
            'title'    => $request->title,
            'order'    => $order,
            'raw_text' => $request->raw_text,
        ]);

        return response()->json([
            'section' => new SectionResource($section),
        ], 201);
    }

    /**
     * PATCH /admin/sections/{section}
     * Editing section text marks the parent chapter's ingestion as stale (Req 3.4).
     */
    public function update(UpdateSectionRequest $request, Section $section): JsonResponse
    {
        $section->update($request->validated());

        // If raw_text changed, the chapter needs re-ingestion
        if ($request->has('raw_text')) {
            $section->chapter()->update(['ingestion_status' => 'draft']);
        }

        return response()->json([
            'section' => new SectionResource($section->fresh()),
        ]);
    }
}
