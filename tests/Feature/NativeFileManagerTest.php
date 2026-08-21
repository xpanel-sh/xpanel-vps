<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Services\NativeFileManager;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use ZipArchive;

class NativeFileManagerTest extends TestCase
{
    private string $temporaryRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryRoot = storage_path('framework/testing/native-files-'.bin2hex(random_bytes(6)));
        mkdir($this->temporaryRoot.'/site-a', 0770, true);
        mkdir($this->temporaryRoot.'/site-b', 0770, true);
        file_put_contents($this->temporaryRoot.'/site-a/index.php', '<?php echo "A";');
        file_put_contents($this->temporaryRoot.'/site-b/secret.txt', 'tenant B');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryRoot);
        parent::tearDown();
    }

    public function test_files_are_read_and_written_only_inside_the_site_root(): void
    {
        $files = app(NativeFileManager::class);
        $site = $this->site('site-a');

        $files->write($site, '/notes.txt', 'tenant A');
        $files->create($site, '/', 'created.php', 'file');
        $listing = $files->list($site, '/');

        $this->assertSame('tenant A', $files->read($site, '/notes.txt')['content']);
        $this->assertContains('notes.txt', array_column($listing['entries'], 'name'));
        $this->assertContains('created.php', array_column($listing['entries'], 'name'));
        $this->assertSame('tenant B', file_get_contents($this->temporaryRoot.'/site-b/secret.txt'));
    }

    public function test_parent_traversal_and_root_deletion_are_rejected(): void
    {
        $files = app(NativeFileManager::class);
        $site = $this->site('site-a');

        try {
            $files->read($site, '../site-b/secret.txt');
            $this->fail('La ruta transversal debio ser rechazada.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        try {
            $files->delete($site, '/');
            $this->fail('La raiz del sitio no debe poder eliminarse.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_archive_conflicts_require_explicit_overwrite_confirmation(): void
    {
        $files = app(NativeFileManager::class);
        $site = $this->site('site-a');
        file_put_contents($this->temporaryRoot.'/site-a/replace.txt', 'original');
        $zip = new ZipArchive;
        $zip->open($this->temporaryRoot.'/site-a/update.zip', ZipArchive::CREATE);
        $zip->addFromString('replace.txt', 'updated');
        $zip->close();

        $conflict = $files->extract($site, '/update.zip');
        $this->assertSame('conflict', $conflict['status']);
        $this->assertSame('original', file_get_contents($this->temporaryRoot.'/site-a/replace.txt'));

        $files->extract($site, '/update.zip', true);
        $this->assertSame('updated', file_get_contents($this->temporaryRoot.'/site-a/replace.txt'));
    }

    private function site(string $directory): Site
    {
        return new Site([
            'domain' => $directory.'.test',
            'document_root' => $this->temporaryRoot.'/'.$directory,
            'provisioning_driver' => 'native',
        ]);
    }
}
