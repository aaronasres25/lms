<?php

namespace App\Support;

/**
 * Every picture you drop into  public/images/  (or  public/images/hero/ , which is listed first)
 * is used automatically in the hero carousels and banners. logo.png / placeholder.jpg are ignored.
 */
class SitePhotos
{
    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
    private const IGNORE = ['logo.png', 'placeholder.jpg', 'placeholder.png', 'favicon.png', 'favicon.ico'];

    private static ?array $cache = null;

    /** @return string[] public URLs */
    public static function all(): array
    {
        if (self::$cache !== null) return self::$cache;

        $urls = [];
        foreach (['images/hero', 'images'] as $dir) {
            $path = public_path($dir);
            if (! is_dir($path)) continue;

            $files = array_filter(scandir($path) ?: [], function ($f) use ($path) {
                return is_file($path . DIRECTORY_SEPARATOR . $f)
                    && in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), self::EXTENSIONS, true)
                    && ! in_array(strtolower($f), self::IGNORE, true);
            });
            natcasesort($files);

            foreach ($files as $f) {
                $urls[] = asset($dir . '/' . rawurlencode($f)) . '?v=' . @filemtime($path . DIRECTORY_SEPARATOR . $f);
            }
        }
        return self::$cache = array_values(array_unique($urls));
    }

    /** The n-th picture (wraps around), or null when the folder has none. */
    public static function pick(int $i = 0): ?string
    {
        $all = self::all();
        return $all ? $all[$i % count($all)] : null;
    }
}
