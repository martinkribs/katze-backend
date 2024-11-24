<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Routing\Controller as BaseController;

class TranslationController extends BaseController
{
    /**
     * Get translations for a specific language and namespace.
     */
    public function get(string $lang, ?string $namespace = null): JsonResponse
    {
        $langPath = base_path("lang/{$lang}");

        // Check if language exists
        if (!File::exists($langPath)) {
            return response()->json([
                'message' => 'Language not found'
            ], 404);
        }

        // If namespace is provided, return only that namespace
        if ($namespace !== null) {
            $filePath = "{$langPath}/{$namespace}.php";
            if (!File::exists($filePath)) {
                return response()->json([
                    'message' => 'Translation namespace not found'
                ], 404);
            }

            return response()->json([
                'translations' => require $filePath
            ]);
        }

        // Otherwise return all translations for the language
        $translations = [];
        foreach (File::files($langPath) as $file) {
            $namespace = $file->getBasename('.php');
            $translations[$namespace] = require $file->getPathname();
        }

        return response()->json([
            'translations' => $translations
        ]);
    }
}
