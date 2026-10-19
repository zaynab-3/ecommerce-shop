<?php

namespace App\Services;

use enshrined\svgSanitize\data\AttributeInterface;
use enshrined\svgSanitize\data\TagInterface;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryIconService
{
    public function store(UploadedFile $file): string
    {
        $svg = $file->get();
        if (strlen($svg) > 131072 || substr_count($svg, '<') > 3000 || preg_match('/<!DOCTYPE|<!ENTITY|url\s*\(/i', $svg)) {
            throw ValidationException::withMessages(['icon_file' => 'Upload a simple SVG icon under 128 KB without document entities.']);
        }
        $sanitizer = new Sanitizer;
        $sanitizer->removeRemoteReferences(true);
        $sanitizer->setAllowedTags(new class implements TagInterface {
            public static function getTags()
            {
                return ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'title', 'desc'];
            }
        });
        $sanitizer->setAllowedAttrs(new class implements AttributeInterface {
            public static function getAttributes()
            {
                return ['xmlns', 'viewBox', 'width', 'height', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'fill-rule', 'clip-rule', 'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'points', 'transform', 'opacity', 'stroke-opacity', 'fill-opacity'];
            }
        });
        $clean = $sanitizer->sanitize($svg);
        if (! $clean || ! preg_match('/<svg[\s>]/i', $clean) || preg_match('/url\s*\(/i', $clean)) {
            throw ValidationException::withMessages(['icon_file' => 'Choose a valid SVG icon without linked resources.']);
        }
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $valid = $document->loadXML($clean, LIBXML_NONET) && $document->documentElement?->localName === 'svg';
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $valid) {
            throw ValidationException::withMessages(['icon_file' => 'The uploaded file must be an SVG document.']);
        }
        $path = 'category-icons/'.Str::uuid().'.svg';
        if (! Storage::disk('local')->put($path, $clean)) {
            throw new \RuntimeException('The icon could not be saved.');
        }

        return $path;
    }
}
