<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteContentController extends Controller
{
    public function show(): JsonResponse
    {
        $content = SiteContent::first();

        return response()->json([
            'content' => $content ? $this->format($content) : SiteContent::defaults(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hero_badge' => ['required', 'string', 'max:80'],
            'hero_title' => ['required', 'string', 'max:150'],
            'hero_subtitle' => ['required', 'string', 'max:400'],
            'features' => ['required', 'array', 'size:3'],
            'features.*.icon' => ['required', 'string', 'max:10'],
            'features.*.title' => ['required', 'string', 'max:80'],
            'features.*.text' => ['required', 'string', 'max:200'],
            'faqs' => ['required', 'array', 'min:1', 'max:10'],
            'faqs.*.q' => ['required', 'string', 'max:150'],
            'faqs.*.a' => ['required', 'string', 'max:500'],
            'cta_title' => ['required', 'string', 'max:150'],
            'cta_subtitle' => ['required', 'string', 'max:300'],
        ]);

        $content = SiteContent::first() ?? new SiteContent();
        $content->fill($validated);
        $content->save();

        return response()->json([
            'message' => 'Contenido del sitio actualizado correctamente.',
            'content' => $this->format($content),
        ]);
    }

    private function format(SiteContent $content): array
    {
        $defaults = SiteContent::defaults();

        return [
            'hero_badge' => $content->hero_badge ?? $defaults['hero_badge'],
            'hero_title' => $content->hero_title ?? $defaults['hero_title'],
            'hero_subtitle' => $content->hero_subtitle ?? $defaults['hero_subtitle'],
            'features' => $content->features ?? $defaults['features'],
            'faqs' => $content->faqs ?? $defaults['faqs'],
            'cta_title' => $content->cta_title ?? $defaults['cta_title'],
            'cta_subtitle' => $content->cta_subtitle ?? $defaults['cta_subtitle'],
        ];
    }
}
