<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim-Psr7/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Slim\Psr7\Factory\StreamFactory;

use function copy;
use function dirname;
use function is_array;
use function is_string;
use function is_uploaded_file;
use function is_writable;
use function move_uploaded_file;
use function rename;
use function sprintf;
use function strpos;
use function unlink;

use const UPLOAD_ERR_OK;

class UploadedFile implements UploadedFileInterface
{
    /**
     * The client-provided full path to the file
     */
    protected string $file;

    /**
     * The client-provided file name.
     */
    protected ?string $name;

    /**
     * The client-provided media type of the file.
     */
    protected ?string $type;

    protected ?int $size;

    /**
     * A valid PHP UPLOAD_ERR_xxx code for the file upload.
     */
    protected int $error = UPLOAD_ERR_OK;

    /**
     * Indicates if the upload is from a SAPI environment.
     */
    protected bool $sapi = false;

    /**
     * @var StreamInterface|null
     */
    protected $stream;

    /**
     * Indicates if the uploaded file has already been moved.
     */
    protected bool $moved = false;

    /**
     * @param string|StreamInterface $fileNameOrStream The full path to the uploaded file provided by the client,
     *                                                 or a StreamInterface instance.
     * @param string|null            $name             The file name.
     * @param string|null            $type             The file media type.
     * @param int|null               $size             The file size in bytes.
     * @param int                    $error            The UPLOAD_ERR_XXX code representing the status of the upload.
     * @param bool                   $sapi             Indicates if the upload is in a SAPI environment.
     */
    final public function __construct(
        $fileNameOrStream,
        ?string $name = null,
        ?string $type = null,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        bool $sapi = false
    ) {
        if ($fileNameOrStream instanceof StreamInterface) {
            $file = $fileNameOrStream->getMetadata('uri');
            if (!is_string($file)) {
                throw new InvalidArgumentException('No URI associated with the stream.');
            }
            $this->file = $file;
            $this->stream = $fileNameOrStream;
        } elseif (is_string($fileNameOrStream)) {
            $this->file = $fileNameOrStream;
        } else {
            throw new InvalidArgumentException(
                'Please provide a string (full path to the uploaded file) or an instance of StreamInterface.'
            );
        }
        $this->name = $name;
        $this->type = $type;
        $this->size = $size;
        $this->error = $error;
        $this->sapi = $sapi;
    }

    /**
     * {@inheritdoc}
     * @return StreamInterface
     */
    public function getStream()
    {
        if ($this->moved) {
            throw new RuntimeException(sprintf('Uploaded file %s has already been moved', $this->name));
        }

        if (!$this->stream) {
            $this->stream = (new StreamFactory())->createStreamFromFile($this->file);
        }

        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo($targetPath): void
    {
        if ($this->moved) {
            throw new RuntimeException('Uploaded file already moved');
        }

        $targetIsStream = strpos($targetPath, '://') > 0;
        if (!$targetIsStream && !is_writable(dirname($targetPath))) {
            throw new InvalidArgumentException('Upload target path is not writable');
        }

        if ($targetIsStream) {
            if (!copy($this->file, $targetPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %s to %s', $this->name, $targetPath));
            }

            if (!unlink($this->file)) {
                throw new RuntimeException(sprintf('Error removing uploaded file %s', $this->name));
            }
        } elseif ($this->sapi) {
            if (!is_uploaded_file($this->file)) {
                throw new RuntimeException(sprintf('%s is not a valid uploaded file', $this->file));
            }

            if (!move_uploaded_file($this->file, $targetPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %s to %s', $this->name, $targetPath));
            }
        } else {
            if (!rename($this->file, $targetPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %s to %s', $this->name, $targetPath));
            }
        }

        $this->moved = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientFilename(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType(): ?string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Returns the client-provided full path to the file
     *
     * @internal This method is not part of the PSR-7 standard
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->file;
    }

    /**
     * Create a normalized tree of UploadedFile instances from the Environment.
     *
     * @internal This method is not part of the PSR-7 standard.
     *
     * @param array $globals The global server variables.
     *
     * @return array A normalized tree of UploadedFile instances or null if none are provided.
     */
    public static function createFromGlobals(array $globals): array
    {
        if (isset($globals['slim.files']) && is_array($globals['slim.files'])) {
            return $globals['slim.files'];
        }

        if (!empty($_FILES)) {
            return self::parseUploadedFiles($_FILES);
        }

        return [];
    }

    /**
     * Parse a non-normalized, i.e. $_FILES superglobal, tree of uploaded file data.
     *
     * @internal This method is not part of the PSR-7 standard.
     *
     * @param array $uploadedFiles The non-normalized tree of uploaded file data.
     *
     * @return array A normalized tree of UploadedFile instances.
     */
    private static function parseUploadedFiles(array $uploadedFiles): array
    {
        $parsed = [];
        foreach ($uploadedFiles as $field => $uploadedFile) {
            if (!isset($uploadedFile['error'])) {
                if (is_array($uploadedFile)) {
                    $parsed[$field] = self::parseUploadedFiles($uploadedFile);
                }
                continue;
            }

            $parsed[$field] = [];
            if (!is_array($uploadedFile['error'])) {
                $parsed[$field] = new static(
                    $uploadedFile['tmp_name'],
                    $uploadedFile['name'] ?? null,
                    $uploadedFile['type'] ?? null,
                    $uploadedFile['size'] ?? null,
                    $uploadedFile['error'],
                    true
                );
            } else {
                $subArray = [];
                foreach ($uploadedFile['error'] as $fileIdx => $error) {
                    // Normalize sub array and re-parse to move the input's key name up a level
                    $subArray[$fileIdx]['name'] = $uploadedFile['name'][$fileIdx];
                    $subArray[$fileIdx]['type'] = $uploadedFile['type'][$fileIdx];
                    $subArray[$fileIdx]['tmp_name'] = $uploadedFile['tmp_name'][$fileIdx];
                    $subArray[$fileIdx]['error'] = $uploadedFile['error'][$fileIdx];
                    $subArray[$fileIdx]['size'] = $uploadedFile['size'][$fileIdx];

                    $parsed[$field] = self::parseUploadedFiles($subArray);
                }
            }
        }

        return $parsed;
    }
}
