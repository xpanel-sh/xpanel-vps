<?php

namespace App\Services;

use App\Models\Site;
use App\Support\ResolvesSandboxedPath;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class NativeFileManager
{
    use ResolvesSandboxedPath;

    public function list(Site $site, string $requestedPath): array
    {
        $directory = $this->resolve($site, $requestedPath, true);
        abort_unless(is_dir($directory), 422, 'La ruta no es una carpeta.');
        $path = $this->normalizePath($requestedPath);
        $entries = [];

        foreach (scandir($directory) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $full = $directory.DIRECTORY_SEPARATOR.$name;
            $isDirectory = is_dir($full);
            $entries[] = [
                'name' => $name,
                'path' => rtrim($path, '/').'/'.$name,
                'is_dir' => $isDirectory,
                'size' => $isDirectory ? 0 : (filesize($full) ?: 0),
                'mode' => substr(sprintf('%o', fileperms($full) ?: 0), -4),
                'mod_time' => date(DATE_ATOM, filemtime($full) ?: time()),
            ];
        }

        usort($entries, fn (array $a, array $b) => [! $a['is_dir'], strtolower($a['name'])] <=> [! $b['is_dir'], strtolower($b['name'])]);

        return ['path' => $path, 'entries' => $entries];
    }

    public function read(Site $site, string $requestedPath): array
    {
        $path = $this->resolve($site, $requestedPath, true);
        abort_if(is_dir($path), 422, 'No se puede editar una carpeta.');
        abort_if(filesize($path) > 2 * 1024 * 1024, 422, 'Archivo demasiado grande para editar (maximo 2 MB).');

        return ['path' => $this->normalizePath($requestedPath), 'content' => file_get_contents($path)];
    }

    public function write(Site $site, string $requestedPath, string $content): array
    {
        $path = $this->resolve($site, $requestedPath);
        abort_if(is_dir($path), 422, 'No se puede escribir sobre una carpeta.');
        abort_unless(is_dir(dirname($path)) && is_writable(file_exists($path) ? $path : dirname($path)), 422, 'El panel no tiene permiso de escritura.');
        abort_if(@file_put_contents($path, $content, LOCK_EX) === false, 500, 'No se pudo guardar el archivo.');

        return ['status' => 'saved', 'path' => $this->normalizePath($requestedPath)];
    }

    public function mkdir(Site $site, string $requestedPath): array
    {
        $path = $this->resolve($site, $requestedPath);
        abort_if($path === $this->root($site), 422, 'No se puede crear la raiz.');
        abort_if(file_exists($path), 422, 'La ruta ya existe.');
        abort_unless(is_dir(dirname($path)) && is_writable(dirname($path)), 422, 'El panel no tiene permiso de escritura.');
        abort_unless(@mkdir($path, 0770), 500, 'No se pudo crear la carpeta.');

        return ['status' => 'created', 'path' => $this->normalizePath($requestedPath)];
    }

    public function create(Site $site, string $requestedPath, string $name, string $type): array
    {
        abort_if($name === '' || $name === '.' || $name === '..' || str_contains($name, '/') || str_contains($name, '\\'), 422, 'Nombre invalido.');
        $directory = $this->resolve($site, $requestedPath, true);
        abort_unless(is_dir($directory) && is_writable($directory), 422, 'La carpeta destino no es escribible.');
        $target = $this->resolve($site, rtrim($requestedPath, '/').'/'.$name);
        abort_if(file_exists($target), 422, 'Ya existe un archivo o carpeta con ese nombre.');

        if ($type === 'dir') {
            abort_unless(@mkdir($target, 0770), 500, 'No se pudo crear la carpeta.');
        } else {
            abort_if(@file_put_contents($target, '') === false, 500, 'No se pudo crear el archivo.');
        }

        return ['status' => 'created', 'path' => $this->normalizePath($requestedPath.'/'.$name)];
    }

    public function delete(Site $site, string $requestedPath): array
    {
        $path = $this->resolve($site, $requestedPath, true);
        abort_if($path === $this->root($site), 422, 'No se puede eliminar la raiz del sitio.');
        abort_unless(is_writable(dirname($path)), 422, 'El panel no tiene permiso para eliminar este elemento.');

        if (is_dir($path) && ! is_link($path)) {
            $this->deleteDirectory($path);
        } else {
            abort_unless(@unlink($path), 500, 'No se pudo eliminar el elemento.');
        }

        return ['status' => 'deleted'];
    }

    public function rename(Site $site, string $oldPath, string $newPath): array
    {
        $source = $this->resolve($site, $oldPath, true);
        $target = $this->resolve($site, $newPath);
        abort_if($source === $this->root($site), 422, 'No se puede mover la raiz del sitio.');
        abort_if(file_exists($target), 422, 'La ruta destino ya existe.');
        abort_unless(is_dir(dirname($target)) && is_writable(dirname($source)) && is_writable(dirname($target)), 422, 'El panel no tiene permiso para mover el elemento.');
        abort_unless(@rename($source, $target), 500, 'No se pudo mover el elemento.');

        return ['status' => 'renamed'];
    }

    public function upload(Site $site, string $requestedPath, UploadedFile $file): array
    {
        $directory = $this->resolve($site, $requestedPath, true);
        abort_unless(is_dir($directory) && is_writable($directory), 422, 'La carpeta destino no es escribible.');
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        abort_if($name === '' || $name === '.' || $name === '..', 422, 'Nombre de archivo invalido.');
        $file->move($directory, $name);

        return ['status' => 'uploaded', 'name' => $name];
    }

    public function search(Site $site, string $requestedPath, string $query, bool $includeContent, bool $caseSensitive): array
    {
        $directory = $this->resolve($site, $requestedPath, true);
        abort_unless(is_dir($directory), 422, 'La ruta no es una carpeta.');
        $root = $this->root($site);
        $needle = $caseSensitive ? $query : mb_strtolower($query);
        $results = [];
        $scanned = 0;
        $truncated = false;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            if (++$scanned > 10000 || count($results) >= 200) {
                $truncated = true;
                break;
            }
            if ($file->isLink()) {
                continue;
            }
            $full = str_replace('\\', '/', $file->getPathname());
            $relative = '/'.ltrim(substr($full, strlen($root)), '/');
            $name = $file->getFilename();
            $haystack = $caseSensitive ? $name : mb_strtolower($name);
            $result = str_contains($haystack, $needle) ? ['name' => $name, 'path' => $relative, 'is_dir' => $file->isDir(), 'kind' => 'name'] : null;

            if ($result === null && $includeContent && $file->isFile() && $file->getSize() <= 512000) {
                foreach (@file($file->getPathname()) ?: [] as $line => $text) {
                    $lineText = $caseSensitive ? $text : mb_strtolower($text);
                    if (str_contains($lineText, $needle)) {
                        $result = ['name' => $name, 'path' => $relative, 'is_dir' => false, 'kind' => 'content', 'line' => $line + 1, 'preview' => trim($text)];
                        break;
                    }
                }
            }
            if ($result !== null) {
                $results[] = $result;
            }
        }

        return ['query' => $query, 'path' => $this->normalizePath($requestedPath), 'results' => $results, 'truncated' => $truncated, 'scanned' => $scanned];
    }

    public function extract(Site $site, string $requestedPath, bool $overwrite = false): array
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'La extension PHP ZIP no esta instalada.');
        $path = $this->resolve($site, $requestedPath, true);
        abort_unless(in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['zip', 'jar'], true), 422, 'Solo se admiten ZIP o JAR.');
        $zip = new ZipArchive;
        abort_unless($zip->open($path) === true, 422, 'No se pudo abrir el archivo.');
        abort_if($zip->numFiles > 5000, 422, 'El archivo contiene demasiados elementos.');
        $bytes = 0;
        $conflicts = [];

        $count = $zip->numFiles;

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                abort_unless(is_array($stat) && isset($stat['name']), 422, 'Entrada ZIP invalida.');
                $entry = str_replace('\\', '/', (string) $stat['name']);
                $parts = array_values(array_filter(explode('/', $entry), fn (string $part) => $part !== ''));
                abort_if($entry === '' || str_starts_with($entry, '/') || preg_match('/^[A-Za-z]:\//', $entry) || in_array('..', $parts, true) || str_contains($entry, "\0"), 422, 'El archivo contiene una ruta insegura.');
                $bytes += (int) ($stat['size'] ?? 0);
                abort_if($bytes > 512 * 1024 * 1024, 422, 'El contenido supera 512 MB.');
                $attributes = 0;
                $operations = 0;
                if ($zip->getExternalAttributesIndex($i, $operations, $attributes)) {
                    abort_if((($attributes >> 16) & 0170000) === 0120000, 422, 'No se permiten enlaces simbolicos.');
                }
                if (! str_ends_with($entry, '/') && file_exists(dirname($path).'/'.$entry)) {
                    $conflicts[] = $entry;
                }
            }
            if ($conflicts !== [] && ! $overwrite) {
                return [
                    'status' => 'conflict',
                    'conflicts' => array_slice($conflicts, 0, 50),
                    'conflict_count' => count($conflicts),
                ];
            }
            abort_unless($zip->extractTo(dirname($path)), 422, 'No se pudo descomprimir el archivo.');
        } finally {
            $zip->close();
        }

        return ['status' => 'extracted', 'count' => $count];
    }

    public function downloadPath(Site $site, string $requestedPath): string
    {
        $path = $this->resolve($site, $requestedPath, true);
        abort_if(is_dir($path), 422, 'No se puede descargar una carpeta.');

        return $path;
    }

    private function resolve(Site $site, string $path, bool $mustExist = false): string
    {
        return $this->resolveWithinRoot($this->root($site), $path, $mustExist);
    }

    private function root(Site $site): string
    {
        return rtrim(str_replace('\\', '/', realpath($site->nativeDocumentRoot()) ?: $site->nativeDocumentRoot()), '/');
    }

    private function normalizePath(string $path): string
    {
        $clean = array_values(array_filter(explode('/', str_replace('\\', '/', $path)), fn (string $part) => $part !== '' && $part !== '.'));

        return $clean === [] ? '/' : '/'.implode('/', $clean);
    }
}
