<?php

namespace Tests\Unit;

use App\Services\ResourcePolicy;
use App\Services\VFS\Ssh2Adapter;
use CodeIgniter\Test\CIUnitTestCase;
use phpseclib4\Exception\ConnectionClosedException;
use phpseclib4\Exception\FileSystemException;
use phpseclib4\Exception\ServiceUnavailableException;
use phpseclib4\Net\SFTP;
use phpseclib4\Net\SFTP\StatusCode;
use PHPUnit\Framework\Attributes\DataProvider;

final class Ssh2AdapterTest extends CIUnitTestCase
{
    public static function missingStatuses(): iterable
    {
        yield 'file' => [StatusCode::NO_SUCH_FILE];
        yield 'parent' => [StatusCode::NO_SUCH_PATH];
    }

    #[DataProvider('missingStatuses')]
    public function testMissingMetadataAndRepeatedDeleteRemainIdempotent(int $status): void
    {
        $sftp = $this->createMock(SFTP::class);
        $sftp->method('lstat')->with('/root/missing')->willThrowException(new FileSystemException('Missing', $status));
        $sftp->expects($this->never())->method('delete');
        $adapter = $this->adapter($sftp);
        $this->assertNull($adapter->getMetadata('missing'));
        $this->assertTrue($adapter->delete('missing'));
    }

    public static function metadataErrors(): iterable
    {
        yield 'permission' => [new FileSystemException('Permission denied', StatusCode::PERMISSION_DENIED)];
        yield 'server failure' => [new FileSystemException('Failure', StatusCode::FAILURE)];
        yield 'connection' => [new ConnectionClosedException('Connection closed')];
    }

    #[DataProvider('metadataErrors')]
    public function testMetadataDoesNotHideAccessOrTransportErrors(\Throwable $error): void
    {
        $sftp = $this->createMock(SFTP::class);
        $sftp->method('lstat')->willThrowException($error);
        $this->expectExceptionObject($error);
        $this->adapter($sftp)->getMetadata('unavailable');
    }

    public function testStreamDownloadAcceptsNullSuccessAndPreservesBinaryContent(): void
    {
        $payload = "binary\0payload";
        $sftp = $this->createMock(SFTP::class);
        $sftp->method('lstat')->willReturn(['mode' => 0100644, 'size' => strlen($payload)]);
        $sftp->expects($this->once())->method('get')->with('/root/file', $this->isType('resource'), 0, $this->greaterThan(strlen($payload)))
            ->willReturnCallback(static function (string $path, $stream) use ($payload): ?string {
                fwrite($stream, $payload);
                return null;
            });
        $this->assertSame($payload, $this->adapter($sftp)->readFile('file'));
    }

    public function testSuccessfulActivationDoesNotDeleteTheMovedStagingFile(): void
    {
        $sftp = $this->createMock(SFTP::class);
        $staging = null;
        $sftp->expects($this->once())->method('put')->willReturnCallback(function (string $path, $stream) use (&$staging): void {
            $this->assertMatchesRegularExpression('#^/root/dir/\.extplorer-write-[a-f0-9]{32}$#', $path);
            $this->assertSame("replacement\0data", stream_get_contents($stream));
            $staging = $path;
        });
        $sftp->expects($this->once())->method('posix_rename')->willReturnCallback(function (string $from, string $to) use (&$staging): void {
            $this->assertSame($staging, $from);
            $this->assertSame('/root/dir/target', $to);
        });
        $sftp->expects($this->never())->method('delete');
        $this->assertTrue($this->adapter($sftp)->writeFile('dir/target', "replacement\0data"));
    }

    public function testFailedActivationOnlyRemovesStagingAndRetainsOriginalError(): void
    {
        $sftp = $this->createMock(SFTP::class);
        $staging = null;
        $error = new ServiceUnavailableException('Atomic rename unsupported');
        $sftp->method('put')->willReturnCallback(static function (string $path) use (&$staging): void { $staging = $path; });
        $sftp->method('posix_rename')->willThrowException($error);
        $sftp->expects($this->once())->method('delete')->with($this->callback(static function (string $path) use (&$staging): bool {
            return $path === $staging;
        }), false);
        $sftp->expects($this->never())->method('rename');
        $this->expectExceptionObject($error);
        $this->adapter($sftp)->writeFile('target', 'replacement');
    }

    public function testPartialUploadCleanupFailureDoesNotMaskTheUploadError(): void
    {
        $sftp = $this->createMock(SFTP::class);
        $error = new ConnectionClosedException('Upload interrupted');
        $sftp->method('put')->willThrowException($error);
        $sftp->expects($this->never())->method('posix_rename');
        $sftp->expects($this->once())->method('delete')->with($this->stringStartsWith('/root/.extplorer-write-'), false)
            ->willThrowException(new ConnectionClosedException('Cleanup connection closed'));
        $this->expectExceptionObject($error);
        $this->adapter($sftp)->writeFile('target', 'replacement');
    }

    public function testVoidMutationsStillFulfilTheBooleanFilesystemContract(): void
    {
        $sftp = $this->createMock(SFTP::class);
        $sftp->expects($this->once())->method('mkdir')->with('/root/directory', 0755, true);
        $sftp->expects($this->once())->method('rename')->with('/root/old', '/root/new');
        $sftp->expects($this->once())->method('chmod')->with('/root/new', 0600);
        $sftp->method('lstat')->willReturn(['mode' => 0100600]);
        $sftp->expects($this->once())->method('delete')->with('/root/new', false);
        $adapter = $this->adapter($sftp);
        $this->assertTrue($adapter->createDirectory('directory'));
        $this->assertTrue($adapter->rename('old', 'new'));
        $this->assertTrue($adapter->chmod('new', 0600));
        $this->assertTrue($adapter->delete('new'));
    }

    private function adapter(SFTP $sftp): Ssh2Adapter
    {
        // Authentication is covered against real servers; isolate transport failures here.
        $reflection = new \ReflectionClass(Ssh2Adapter::class);
        $adapter = $reflection->newInstanceWithoutConstructor();
        foreach (['sftp' => $sftp, 'root' => '/root', 'policy' => new ResourcePolicy()] as $name => $value) {
            $reflection->getProperty($name)->setValue($adapter, $value);
        }
        return $adapter;
    }
}
