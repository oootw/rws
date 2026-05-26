<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\EnvMasker;
use App\Services\EnvReaderService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Read-only просмотр .env-файла. Чувствительные значения
 * (KEY/SECRET/TOKEN/PASSWORD/HASH/SALT/DSN) маскируются;
 * длина оригинала показывается для верификации «нечто записано».
 */
final class EnvViewerPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = '.env';

    protected static ?string $title = 'Просмотр .env';

    protected static string|\UnitEnum|null $navigationGroup = 'Операционная';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'env-viewer';

    protected string $view = 'filament.pages.env-viewer';

    /** @var array<string, string> */
    public array $env = [];

    public string $filePath = '';

    public bool $fileExists = false;

    public function mount(): void
    {
        $this->filePath = base_path('.env');
        $this->fileExists = is_file($this->filePath);

        $reader = app(EnvReaderService::class);
        $masker = app(EnvMasker::class);

        $this->env = $masker->mask($reader->read($this->filePath));
    }
}
