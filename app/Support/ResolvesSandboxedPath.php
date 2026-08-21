<?php

namespace App\Support;

trait ResolvesSandboxedPath
{
    protected function resolveWithinRoot(string $root, string $requestedPath, bool $mustExist = false): string
    {
        abort_unless(is_dir($root), 404, 'La raiz del sitio aun no existe.');
        $root = rtrim(str_replace('\\', '/', realpath($root) ?: $root), '/');
        $segments = [];

        foreach (explode('/', str_replace('\\', '/', $requestedPath)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                abort_if($segments === [], 403, 'Ruta invalida.');
                array_pop($segments);

                continue;
            }
            abort_if(str_contains($part, "\0"), 403, 'Ruta invalida.');
            $segments[] = $part;
        }

        $candidate = $segments === [] ? $root : $root.'/'.implode('/', $segments);
        abort_if($mustExist && ! file_exists($candidate), 404, 'No encontrado.');

        $real = realpath($candidate);
        if ($real === false) {
            $ancestor = $candidate;
            while (! file_exists($ancestor)) {
                abort_if(is_link($ancestor), 403, 'Ruta invalida.');
                $parent = dirname($ancestor);
                abort_if($parent === $ancestor, 403, 'Ruta invalida.');
                $ancestor = $parent;
            }
            $realAncestor = str_replace('\\', '/', realpath($ancestor) ?: $ancestor);
            abort_unless($realAncestor === $root || str_starts_with($realAncestor, $root.'/'), 403, 'Ruta invalida.');

            return $candidate;
        }

        $real = str_replace('\\', '/', $real);
        abort_unless($real === $root || str_starts_with($real, $root.'/'), 403, 'Ruta invalida.');

        return $real;
    }

    protected function deleteDirectory(string $directory): void
    {
        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path) && ! is_link($path)) {
                $this->deleteDirectory($path);
            } else {
                abort_unless(@unlink($path), 500, 'No se pudo eliminar '.$item.'.');
            }
        }
        abort_unless(@rmdir($directory), 500, 'No se pudo eliminar la carpeta.');
    }
}
