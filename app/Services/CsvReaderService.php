<?php

namespace App\Services;

use App\Exceptions\CsvLoadException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CsvReaderService
{
    private const STORAGE_DISK = 'commission-csv';
    private const FILE_EXTENSION = 'csv';

    /**
     * Storage disk
     *
     * @var Filesystem
     */
    private Filesystem $disk;

    /**
     * CSV Resource
     *
     * @var resource $stream
     */
    private $stream;

    /**
     * Service constructor
     */
    public function __construct()
    {
        $this->disk = Storage::disk(self::STORAGE_DISK);
    }

    /**
     * Sets stream
     *
     * @param string $filename
     * @return void
     * @throws CsvLoadException
     */
    public function loadFile(string $filename): void
    {
        $filename = Str::finish($filename, '.' . self::FILE_EXTENSION);
        $stream = $this->disk->readStream($filename);
        if ($stream === null) {
            throw new CsvLoadException('File "' . $filename . '" does not exist');
        }
        $this->stream = $stream;
    }

    /**
     * Get single line
     *
     * @return array|false
     */
    public function getLine(): array|false
    {
        return fgetcsv($this->stream);
    }
}
