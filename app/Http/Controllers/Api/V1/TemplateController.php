<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    // ─── GET /api/v1/templates ────────────────────────────────────────────────
    public function index(): JsonResponse
    {
        $templates = auth()->user()->templates()->latest()->get();

        return response()->json(['success' => true, 'data' => $templates]);
    }

    // ─── POST /api/v1/templates ───────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $user  = auth()->user();
        $limit = $user->plan?->max_templates ?? 20;

        if ($user->templates()->count() >= $limit) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'TEMPLATE_LIMIT_REACHED', 'message' => "Plan limit: {$limit} templates."],
            ], 403);
        }

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:60'],
            'type'      => ['required', 'in:text,image,document'],
            'body'      => ['nullable', 'string', 'max:4096'],
            'media_url' => ['nullable', 'url', 'max:2048'],
            'variables' => ['nullable', 'array'],
        ]);

        $data['user_id'] = $user->id;
        $template = Template::create($data);

        return response()->json(['success' => true, 'data' => $template], 201);
    }

    // ─── GET /api/v1/templates/{id} ───────────────────────────────────────────
    public function show(Template $template): JsonResponse
    {
        $this->authorize('view', $template);

        return response()->json(['success' => true, 'data' => $template]);
    }

    // ─── PUT /api/v1/templates/{id} ───────────────────────────────────────────
    public function update(Request $request, Template $template): JsonResponse
    {
        $this->authorize('update', $template);

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:60'],
            'type'      => ['sometimes', 'in:text,image,document'],
            'body'      => ['nullable', 'string', 'max:4096'],
            'media_url' => ['nullable', 'url', 'max:2048'],
            'variables' => ['nullable', 'array'],
        ]);

        $template->update($data);

        return response()->json(['success' => true, 'data' => $template]);
    }

    // ─── DELETE /api/v1/templates/{id} ────────────────────────────────────────
    public function destroy(Template $template): JsonResponse
    {
        $this->authorize('delete', $template);
        $template->delete();

        return response()->json(['success' => true, 'message' => 'Template removed.']);
    }
}
