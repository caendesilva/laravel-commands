<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Count the lines of PHP code in the project.
 *
 * Searches the app and src directories.
 */
class SourceCodeLinesCounterCommand extends Command
{
    protected $signature = 'count:lines {--json : Output as JSON }';

    protected $description = 'Count the lines of PHP code in the project.';

    /** @var array<string, int> Relative path => count */
    protected array $files = [];

    public function handle(): void
    {
        if ($this->option('json')) {
            $this->findFiles();
            $this->countLinesInFiles();
            $this->info(json_encode($this->files, JSON_PRETTY_PRINT));

            return;
        }

        $this->info('Counting lines...');

        $this->findFiles();
        $this->countLinesInFiles();

        if ($this->output->isVerbose()) {
            $this->output->newLine();
            $this->table(['File', 'Lines'], array_map(fn (int $count, string $path) => [$path, $count], $this->files, array_keys($this->files)));
            $this->output->newLine();
        }

        $this->info("Lines of PHP code: {$this->getTotalLineCount()}");
    }

    protected function findFiles(): void
    {
        $this->files = $this->getFiles();
    }

    protected function getFiles(): array
    {
        $files = [];

        foreach (['app', 'src'] as $dir) {
            $files = array_merge($files, $this->getFilesInDir($dir));
        }

        return $files;
    }

    protected function getFilesInDir(string $dir): array
    {
        $files = [];

        if (! is_dir($dir)) {
            return $files;
        }

        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = "{$dir}/{$file}";

            if (is_dir($path)) {
                $files = array_merge($files, $this->getFilesInDir($path));
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $files[$path] = 0;
            }
        }

        return $files;
    }

    protected function countLinesInFiles(): void
    {
        array_walk($this->files, function (int &$count, string $path): void {
            $count = $this->countLinesInFile($path);
        });
    }

    protected function countLinesInFile(string $path): int
    {
        $count = 0;
        $contents = explode("\n", file_get_contents($path));
        foreach ($contents as $line) {
            if ($this->canCountLine($line)) {
                $count++;
            }
        }

        return $count;
    }

    protected function canCountLine(string $line): bool
    {
        $trimmed = trim($line);

        if (blank($trimmed)) {
            return false;
        }

        if (Str::startsWith($trimmed, ['#', '//', '/*', '*'])) {
            return false;
        }

        return true;
    }

    protected function getTotalLineCount(): int
    {
        return array_sum(array_values($this->files));
    }
}
