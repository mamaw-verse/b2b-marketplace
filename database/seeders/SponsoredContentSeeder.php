<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Media;
use App\Models\SponsoredContent;
use App\Models\SponsoredContentPlacement;
use App\Models\SponsoredContentTier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds a full row of sponsored content demo entries — one per tier.
 *
 * Each entry gets both a wide creative (1200×300) and a tall creative (400×600)
 * as in-memory generated placeholder PNG images stored in S3/Minio.
 * All entries target the "homepage" placement and are scheduled as currently live.
 */
final class SponsoredContentSeeder extends Seeder
{
    /**
     * Wide creative aspect ratio in pixels.
     * Best display: 1200 × 300 px (4:1)
     */
    private const int WIDE_W = 1200;

    private const int WIDE_H = 300;

    /**
     * Tall creative aspect ratio in pixels.
     * Best display: 400 × 600 px (2:3)
     */
    private const int TALL_W = 400;

    private const int TALL_H = 600;

    /**
     * Demo entries. Each maps to one ad record with both creatives.
     *
     * @var list<array{
     *     title: string,
     *     destination_url: string,
     *     tier: string,
     *     placements: list<string>,
     *     wide_bg: int[],
     *     tall_bg: int[],
     *     label: string,
     * }>
     */
    private const array ENTRIES = [
        [
            'title' => 'Al Barakah Foods — Ramadan Super Sale',
            'destination_url' => 'https://albarakahfoods.example.com',
            'tier' => 'Gold',
            'placements' => ['homepage', 'search_results'],
            'wide_bg' => [24, 71, 40],   // dark green
            'tall_bg' => [15, 50, 28],
            'label' => 'Al Barakah Foods',
        ],
        [
            'title' => 'Majestic Halal Meat Co. — Premium Cuts',
            'destination_url' => 'https://majestichalalco.example.com',
            'tier' => 'Gold',
            'placements' => ['homepage', 'category_page'],
            'wide_bg' => [120, 36, 20],  // deep red
            'tall_bg' => [90, 22, 10],
            'label' => 'Majestic Halal',
        ],
        [
            'title' => 'Salam Spice Traders — New Arrivals',
            'destination_url' => 'https://salamspice.example.com',
            'tier' => 'Silver',
            'placements' => ['homepage', 'search_results'],
            'wide_bg' => [140, 90, 10],  // amber
            'tall_bg' => [100, 65, 5],
            'label' => 'Salam Spice',
        ],
        [
            'title' => 'HalalFresh PH — Farm-to-Table Delivery',
            'destination_url' => 'https://halalfresh.example.com',
            'tier' => 'Silver',
            'placements' => ['homepage'],
            'wide_bg' => [20, 100, 60],  // emerald
            'tall_bg' => [10, 75, 45],
            'label' => 'HalalFresh PH',
        ],
        [
            'title' => 'Nur Logistics — Halal Cold Chain',
            'destination_url' => 'https://nurlogistics.example.com',
            'tier' => 'Bronze',
            'placements' => ['homepage', 'category_page'],
            'wide_bg' => [45, 70, 110],  // blue-steel
            'tall_bg' => [30, 50, 85],
            'label' => 'Nur Logistics',
        ],
        [
            'title' => 'Berkah Dairy — Halal Cheese & Yogurt',
            'destination_url' => 'https://berkahdairy.example.com',
            'tier' => 'Bronze',
            'placements' => ['homepage'],
            'wide_bg' => [170, 130, 40], // warm gold
            'tall_bg' => [130, 95, 20],
            'label' => 'Berkah Dairy',
        ],
    ];

    public function run(): void
    {
        // Ensure tiers exist.
        (new SponsoredContentTierSeeder)->run();

        /** @var User $uploader */
        $uploader = User::first() ?? User::factory()->create();

        foreach (self::ENTRIES as $entry) {
            // Skip if already seeded (idempotent by title).
            if (SponsoredContent::where('title', $entry['title'])->exists()) {
                continue;
            }

            $tier = SponsoredContentTier::where('name', $entry['tier'])->first();

            if (! $tier) {
                continue;
            }

            $wideMedia = $this->createPlaceholderMedia(
                $uploader->id,
                self::WIDE_W,
                self::WIDE_H,
                $entry['wide_bg'],
                $entry['label'].' — Wide',
                'Sponsored content – wide creative',
            );

            $tallMedia = $this->createPlaceholderMedia(
                $uploader->id,
                self::TALL_W,
                self::TALL_H,
                $entry['tall_bg'],
                $entry['label'].' — Tall',
                'Sponsored content – tall creative',
            );

            $content = SponsoredContent::create([
                'sponsored_content_tier_id' => $tier->id,
                'title' => $entry['title'],
                'wide_media_id' => $wideMedia->id,
                'tall_media_id' => $tallMedia->id,
                'destination_url' => $entry['destination_url'],
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(3),
                'is_active' => true,
                'created_by' => $uploader->id,
            ]);

            foreach ($entry['placements'] as $key) {
                SponsoredContentPlacement::create([
                    'sponsored_content_id' => $content->id,
                    'placement_key' => $key,
                ]);
            }
        }
    }

    /**
     * Generate an in-memory placeholder PNG and store it to S3, returning a Media record.
     *
     * @param  int[]  $bg  [r, g, b]
     */
    private function createPlaceholderMedia(
        int $uploaderId,
        int $width,
        int $height,
        array $bg,
        string $text,
        string $description,
    ): Media {
        $png = $this->generatePlaceholderPng($width, $height, $bg, $text);

        $uuid = Str::uuid()->toString();
        $datePrefix = now()->format('Y/m/d');
        $filename = Str::slug($text).'-'.$uuid.'.png';
        $storagePath = "media/{$datePrefix}/{$filename}";

        Storage::disk('s3')->put($storagePath, $png, 'private');

        return Media::create([
            'original_filename' => $filename,
            'extension' => 'png',
            'storage_path' => $storagePath,
            'mime_type' => 'image/png',
            'description' => $description,
            'scan_status' => 'clean',
            'uploaded_by_id' => $uploaderId,
        ]);
    }

    /**
     * Generate a minimal solid-colour PNG with a centred label using PHP GD.
     * Returns raw PNG bytes.
     *
     * @param  int[]  $bg  [r, g, b]
     */
    private function generatePlaceholderPng(int $width, int $height, array $bg, string $label): string
    {
        $img = imagecreatetruecolor($width, $height);

        $bgColor = imagecolorallocate($img, $bg[0], $bg[1], $bg[2]);
        $textColor = imagecolorallocate($img, 255, 255, 255);
        $accentColor = imagecolorallocate($img, 212, 163, 36); // gold

        imagefill($img, 0, 0, $bgColor);

        // Thin gold border
        imagerectangle($img, 4, 4, $width - 5, $height - 5, $accentColor);

        // Centred label (built-in font size 5 = 9×15 px per char)
        $fontW = 9;
        $fontH = 15;
        $labelLen = mb_strlen($label);
        $textW = $labelLen * $fontW;
        $x = max(0, (int) (($width - $textW) / 2));
        $y = (int) (($height - $fontH) / 2);
        imagestring($img, 5, $x, $y, $label, $textColor);

        // Sponsored tag below label
        $tag = '[ SPONSORED DEMO ]';
        $tagW = mb_strlen($tag) * $fontW;
        $tx = max(0, (int) (($width - $tagW) / 2));
        imagestring($img, 3, $tx, $y + 20, $tag, $accentColor);

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return $png ?: '';
    }
}
