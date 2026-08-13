<?php

namespace App\Http\Controllers\Api\V1\Books;

use App\Http\Controllers\Controller;
use App\Http\Requests\Books\StoreSectionRequest;
use App\Http\Requests\Books\UpdateSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Chapter;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin section management (Req 3.2, 3.4)
 */
class AdminSectionController extends Controller
{
    /**
     * GET /admin/chapters/{chapter}/sections
     * Get all sections for a chapter.
     */
    public function index(Chapter $chapter): JsonResponse
    {
        $sections = $chapter->sections()->orderBy('order')->get();

        return response()->json([
            'sections' => SectionResource::collection($sections),
        ]);
    }

    /**
     * POST /admin/chapters/{chapter}/sections
     * Create a new section with explicit section_number (structured ingestion).
     */
    public function store(StoreSectionRequest $request, Chapter $chapter): JsonResponse
    {
        $order = $request->order
            ?? ($chapter->sections()->max('order') ?? 0) + 1;

        $section = $chapter->sections()->create([
            'section_number' => $request->section_number,
            'title'          => $request->title,
            'order'          => $order,
            'raw_text'       => $request->raw_text,
        ]);

        return response()->json([
            'section' => new SectionResource($section),
        ], 201);
    }

    /**
     * PATCH /admin/sections/{section}
     * Edit section (section_number, title, order, raw_text).
     */
    public function update(UpdateSectionRequest $request, Section $section): JsonResponse
    {
        $validated = $request->validated();
        
        // If raw_text changed, mark chapter as draft
        if ($request->has('raw_text') && $section->raw_text !== $request->raw_text) {
            $section->chapter()->update(['ingestion_status' => 'draft']);
        }
        
        // If section_number changed, verify uniqueness within chapter
        if ($request->has('section_number')) {
            $existing = Section::where('chapter_id', $section->chapter_id)
                ->where('section_number', $request->section_number)
                ->where('id', '!=', $section->id)
                ->first();
            
            if ($existing) {
                return response()->json([
                    'error' => [
                        'code' => 'DUPLICATE_SECTION_NUMBER',
                        'message' => 'Section number already exists in this chapter',
                    ],
                ], 422);
            }
        }
        
        $section->update($validated);

        return response()->json([
            'section' => new SectionResource($section->fresh()),
        ]);
    }

    /**
     * DELETE /admin/sections/{section}
     * Delete a section.
     * Only allows deletion if chapter has more than 0 sections.
     */
    public function destroy(Section $section): JsonResponse
    {
        $chapter = $section->chapter;
        
        // Guard: chapter must have at least one section after deletion
        if ($chapter->sections()->count() <= 1) {
            return response()->json([
                'error' => [
                    'code' => 'LAST_SECTION',
                    'message' => 'Chapter must have at least one section.',
                ],
            ], 422);
        }

        $section->delete();

        return response()->json([
            'message' => 'Section deleted successfully',
        ]);
    }

    /**
     * PATCH /admin/sections/{section}/reorder
     * Reorder a section within its chapter.
     * 
     * Body: { "new_order": 2 }
     */
    public function reorder(Section $section, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'new_order' => 'required|integer|min:1',
        ]);

        $newOrder = $validated['new_order'];
        $currentOrder = $section->order;
        $chapterId = $section->chapter_id;

        // If no change, return early
        if ($newOrder === $currentOrder) {
            return response()->json([
                'section' => new SectionResource($section),
            ]);
        }

        DB::transaction(function () use ($section, $newOrder, $currentOrder, $chapterId) {
            // Shift other sections in the chapter
            if ($newOrder > $currentOrder) {
                // Moving down (to a higher number)
                Section::where('chapter_id', $chapterId)
                    ->where('order', '>', $currentOrder)
                    ->where('order', '<=', $newOrder)
                    ->decrement('order');
            } else {
                // Moving up (to a lower number)
                Section::where('chapter_id', $chapterId)
                    ->where('order', '<', $currentOrder)
                    ->where('order', '>=', $newOrder)
                    ->increment('order');
            }

            // Update the moved section
            $section->update(['order' => $newOrder]);
        });

        return response()->json([
            'section' => new SectionResource($section->fresh()),
        ]);
    }
}
