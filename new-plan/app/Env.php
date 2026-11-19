<?php
/**
 * Env — parses the project's config.env into getenv()-style storage.
 *
 * Lookup order for the file (first that exists wins):
 *   1. FHE_CONFIG_PATH environment variable
 *   2. ../config.env relative to this app/ directory (cPanel: next to app/)
 *   3. ../../config.env (unusual layouts)
 *
 * Real environment variables always win over file values, so Docker /
 * CI can override anything without touching the file.
 */

final class Env
{
    /** @var array<string,string> */
    private static array $data = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        foreach (self::candidates() as $path) {
            if (is_file($path)) {
                self::$data = self::parse($path);
                break;
            }
        }
    }

    /** @return string[] */
    private static function candidates(): array
    {
        $paths = [];
        if (!empty($_SERVER['FHE_CONFIG_PATH'])) {
            $paths[] = $_SERVER['FHE_CONFIG_PATH'];
        }
        $appDir = __DIR__;
        $paths[] = dirname($appDir) . '/config.env';
        $paths[] = dirname($appDir, 2) . '/config.env';
        return $paths;
    }

    /** @return array<string,string> */
    private static function parse(string $path): array
    {
        $values = [];
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $values;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            // strip surrounding quotes
            if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
                $value = substr($value, 1, -1);
            }
            if ($key !== '') {
                $values[$key] = $value;
            }
        }
        return $values;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        self::load();
        $real = getenv($key);
        if ($real !== false && $real !== '') {
            return $real;
        }
        return self::$data[$key] ?? $default;
    }

    public static function fileUsed(): ?string
    {
        self::load();
        foreach (self::candidates() as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }
}
