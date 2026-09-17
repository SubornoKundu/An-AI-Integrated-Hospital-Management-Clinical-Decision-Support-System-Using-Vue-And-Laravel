<?php

namespace App\Support;

/**
 * Generates a simple, professional SVG avatar for a given name — used as
 * a placeholder until a user uploads a real photo.
 *
 * Two styles are supported:
 *  - initials(): a colored circle with the person's initials (Slack/Gmail
 *    style) — used when gender isn't relevant or known.
 *  - silhouette(): a colored circle with a simple person-silhouette icon,
 *    styled by gender (Male/Female get a distinct hair shape; Other or an
 *    unset gender shows both hair styles layered together).
 *
 * Fully self-contained: no external services, no network calls, no
 * copyright concerns. Every name deterministically gets the same color,
 * so a given person's placeholder avatar stays consistent across
 * regenerations.
 */
class AvatarGenerator
{
    /**
     * A small curated palette of professional, medical-app-appropriate
     * colors (avoids anything too loud/neon).
     */
    private const PALETTE = [
        ['bg' => '#2563EB', 'text' => '#FFFFFF'], // blue
        ['bg' => '#0891B2', 'text' => '#FFFFFF'], // cyan
        ['bg' => '#059669', 'text' => '#FFFFFF'], // emerald
        ['bg' => '#7C3AED', 'text' => '#FFFFFF'], // violet
        ['bg' => '#DB2777', 'text' => '#FFFFFF'], // pink
        ['bg' => '#D97706', 'text' => '#FFFFFF'], // amber
        ['bg' => '#4F46E5', 'text' => '#FFFFFF'], // indigo
        ['bg' => '#0D9488', 'text' => '#FFFFFF'], // teal
    ];

    /**
     * Build the initials shown on the avatar, e.g. "Dr. John Smith" -> "JS".
     */
    public static function initials(string $name): string
    {
        $clean = trim(preg_replace('/^(Dr\.?|Prof\.?|Mr\.?|Mrs\.?|Ms\.?)\s+/i', '', $name));
        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return '?';
        }

        if (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 1));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr(end($words), 0, 1));
    }

    /**
     * Deterministically pick a palette color for a given name, so the same
     * person always gets the same color across regenerations.
     */
    private static function colorFor(string $name): array
    {
        $index = crc32($name) % count(self::PALETTE);

        return self::PALETTE[$index];
    }

    /**
     * Plain colored-initials avatar (no gender styling).
     */
    public static function initialsSvg(string $name, int $size = 256): string
    {
        $initials = self::initials($name);
        $color = self::colorFor($name);
        $fontSize = (int) round($size * 0.38);
        $half = (int) round($size / 2);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
    <rect width="{$size}" height="{$size}" rx="{$half}" fill="{$color['bg']}" />
    <text x="50%" y="53%" dominant-baseline="middle" text-anchor="middle"
        font-family="Inter, Arial, sans-serif" font-weight="700"
        font-size="{$fontSize}" fill="{$color['text']}">{$initials}</text>
</svg>
SVG;
    }

    /**
     * Gender-styled person-silhouette avatar. gender: 'Male' | 'Female' |
     * 'Other' | null. 'Other' and null both show the male + female hair
     * shapes layered together (combined), as requested.
     *
     * Internally always drawn on a fixed 256x256 grid — the SVG's
     * width/height/viewBox handle scaling to whatever $size is requested,
     * so the shape geometry itself never needs to be recalculated.
     */
    public static function silhouetteSvg(string $name, int $size = 256, ?string $gender = null): string
    {
        $color = self::colorFor($name);
        $half = (int) round($size / 2);

        $shoulders = '<ellipse cx="128" cy="278" rx="98" ry="80" />';
        $head = '<circle cx="128" cy="102" r="40" />';

        // Hair is drawn BEFORE the head circle, so the head circle "cuts
        // into" the front of it — only the rim/sides/bottom of the hair
        // ellipse remains visible, giving a natural framing effect.
        $shortHair = '<ellipse cx="128" cy="90" rx="45" ry="42" />';
        $longHair = '<ellipse cx="128" cy="118" rx="52" ry="78" />';

        $hair = match ($gender) {
            'Male' => $shortHair,
            'Female' => $longHair,
            default => $shortHair.$longHair, // 'Other' / unset -> combined
        };

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 256 256">
    <defs>
        <clipPath id="avatarClip"><rect width="256" height="256" rx="128" /></clipPath>
    </defs>
    <rect width="256" height="256" rx="128" fill="{$color['bg']}" />
    <g clip-path="url(#avatarClip)" fill="{$color['text']}" fill-opacity="0.95">
        {$shoulders}
        {$hair}
        {$head}
    </g>
</svg>
SVG;
    }

    /**
     * Generate an avatar and save it to public/images/{folder}/, returning
     * the relative path to store in a photo column (same shape as an
     * uploaded photo, so existing `asset($photo)` display code needs no
     * special-casing). Uses the gender-styled silhouette when a gender is
     * known or defaults to the combined style otherwise.
     */
    public static function saveFor(string $name, string $folder, int|string $id, ?string $gender = null): string
    {
        $dir = public_path("images/{$folder}");
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = "generated-{$id}.svg";
        file_put_contents($dir.'/'.$filename, self::silhouetteSvg($name, 256, $gender));

        return "images/{$folder}/{$filename}";
    }
}
