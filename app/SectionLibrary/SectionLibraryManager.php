<?php

namespace App\SectionLibrary;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use Statamic\Facades\YAML;

class SectionLibraryManager
{
    private const SCHEMA_VERSION = 1;
    private const SOURCE_REF = 'local-bootstrap';

    public function __construct(private readonly Filesystem $files)
    {
    }

    public function dashboardData(): array
    {
        $compatibility = $this->compatibilityReport();
        $initialized = $this->isInitialized();
        $status = $this->status($compatibility['blocking_issues'], $initialized);
        $installed = $this->installedBundles();

        return [
            'status' => $status,
            'isInitialized' => $initialized,
            'compatibility' => $compatibility,
            'availableBundles' => [],
            'availableMessage' => 'GitHub catalog fetching is the next build step. This scaffold already tracks project readiness, initialization, and installed bundle state.',
            'installedBundles' => $installed,
            'managedFiles' => $this->managedFilesOverview(),
        ];
    }

    public function refresh(): array
    {
        cache()->forget($this->catalogCacheKey());

        return [
            'flash' => 'success',
            'message' => 'Section library cache cleared. The GitHub catalog fetch layer is scaffolded next.',
        ];
    }

    public function initialize(): array
    {
        $compatibility = $this->compatibilityReport();

        if ($compatibility['blocking_issues'] !== []) {
            throw new RuntimeException('Resolve the blocking compatibility issues before initializing the section library.');
        }

        $writes = $this->plannedInitializationWrites();
        $backups = [];

        foreach ($writes as $path => $contents) {
            $backups[$path] = $this->files->exists($path) ? $this->files->get($path) : null;
        }

        try {
            foreach ($writes as $path => $contents) {
                $this->files->put($path, $contents);
            }
        } catch (\Throwable $e) {
            foreach ($backups as $path => $contents) {
                if ($contents === null) {
                    if ($this->files->exists($path)) {
                        $this->files->delete($path);
                    }

                    continue;
                }

                $this->files->put($path, $contents);
            }

            throw $e;
        }

        return [
            'flash' => 'success',
            'message' => 'Section library initialized. Managed blocks and the installed bundle manifest are now in place for this project.',
        ];
    }

    public function compatibilityReport(): array
    {
        $configIssues = collect([
            blank(config('section-library.github.repo')) ? 'Set `SECTION_LIBRARY_GITHUB_REPO` so the addon knows which library repo to query.' : null,
            blank(config('section-library.github.token')) ? 'Set `SECTION_LIBRARY_GITHUB_TOKEN` before using the library browser or installer.' : null,
            blank(config('section-library.github.catalog_path')) ? 'Set `SECTION_LIBRARY_CATALOG_PATH` so the addon can locate the central catalog.' : null,
        ])->filter()->values()->all();

        $requiredFiles = collect(config('section-library.managed_files'))
            ->pluck('path')
            ->unique()
            ->merge([config('section-library.paths.registry')])
            ->values();

        $missingFiles = $requiredFiles
            ->filter(fn ($path) => ! $this->files->exists($path))
            ->map(fn ($path) => "Required project file missing: {$path}")
            ->values()
            ->all();

        $managedBlocks = $this->managedFilesOverview();

        return [
            'blocking_issues' => [...$configIssues, ...$missingFiles],
            'warnings' => [],
            'managed_blocks' => $managedBlocks,
        ];
    }

    public function installedBundles(): array
    {
        $manifestPath = config('section-library.paths.installed_manifest');

        if (! $this->files->exists($manifestPath)) {
            return [];
        }

        $manifest = YAML::file($manifestPath)->parse() ?? [];

        return collect($manifest['bundles'] ?? [])
            ->map(function (array $bundle) {
                return [
                    'handle' => $bundle['handle'],
                    'title' => $bundle['title'] ?? Str::headline($bundle['handle']),
                    'type' => $bundle['type'] ?? 'section',
                    'family' => $bundle['family'] ?? null,
                    'source_ref' => $bundle['source_ref'] ?? null,
                    'files' => $bundle['files'] ?? [],
                    'registrations' => $bundle['registrations'] ?? [],
                    'dependencies' => $bundle['dependencies'] ?? [],
                ];
            })
            ->sortBy('title')
            ->values()
            ->all();
    }

    private function status(array $blockingIssues, bool $initialized): string
    {
        if ($blockingIssues !== []) {
            return 'attention_required';
        }

        if (! $initialized) {
            return 'not_initialized';
        }

        return 'ready';
    }

    private function isInitialized(): bool
    {
        if (! $this->files->exists(config('section-library.paths.installed_manifest'))) {
            return false;
        }

        return collect(config('section-library.managed_files'))->every(function (array $definition) {
            $contents = $this->files->exists($definition['path']) ? $this->files->get($definition['path']) : '';

            return str_contains($contents, $definition['start']) && str_contains($contents, $definition['end']);
        });
    }

    private function managedFilesOverview(): array
    {
        return collect(config('section-library.managed_files'))
            ->map(function (array $definition, string $handle) {
                $contents = $this->files->exists($definition['path']) ? $this->files->get($definition['path']) : '';

                return [
                    'handle' => $handle,
                    'path' => $definition['path'],
                    'ready' => str_contains($contents, $definition['start']) && str_contains($contents, $definition['end']),
                    'start' => $definition['start'],
                    'end' => $definition['end'],
                ];
            })
            ->values()
            ->all();
    }

    private function plannedInitializationWrites(): array
    {
        $writes = [];
        $managedFiles = config('section-library.managed_files');

        $writes[$managedFiles['site_scss']['path']] = $this->initializeSiteScss(
            $managedFiles['site_scss'],
            $this->files->get($managedFiles['site_scss']['path'])
        );

        $siteJs = $this->files->get($managedFiles['site_js_imports']['path']);
        $siteJs = $this->initializeSiteJsImports($managedFiles['site_js_imports'], $siteJs);
        $siteJs = $this->initializeSiteJsInitializers($managedFiles['site_js_initializers'], $siteJs);
        $writes[$managedFiles['site_js_imports']['path']] = $siteJs;

        $writes[$managedFiles['page_builder']['path']] = $this->initializePageBuilder(
            $managedFiles['page_builder'],
            $this->files->get($managedFiles['page_builder']['path'])
        );

        $writes[$managedFiles['blog_bard_blocks']['path']] = $this->initializeBardBlocks(
            $managedFiles['blog_bard_blocks'],
            $this->files->get($managedFiles['blog_bard_blocks']['path'])
        );

        $writes[$managedFiles['case_studies_bard_blocks']['path']] = $this->initializeBardBlocks(
            $managedFiles['case_studies_bard_blocks'],
            $this->files->get($managedFiles['case_studies_bard_blocks']['path'])
        );

        $writes[config('section-library.paths.installed_manifest')] = YAML::dump($this->buildInstalledManifest());

        return $writes;
    }

    private function initializeSiteScss(array $definition, string $contents): string
    {
        if ($this->hasManagedBlock($definition, $contents)) {
            return $contents;
        }

        preg_match_all("/^@use 'sets\\/[^']+';$/m", $contents, $matches);
        $lines = $matches[0] ?? [];

        if ($lines === []) {
            throw new RuntimeException('Could not find the current set import block in resources/css/site.scss.');
        }

        $block = implode("\n", $lines);
        $managed = implode("\n", [$definition['start'], $block, $definition['end']]);

        return Str::replaceFirst($block, $managed, $contents);
    }

    private function initializeSiteJsImports(array $definition, string $contents): string
    {
        if ($this->hasManagedBlock($definition, $contents)) {
            return $contents;
        }

        preg_match_all("/^import .* from '\\.\\/sets\\/[^']+';$/m", $contents, $matches);
        $lines = $matches[0] ?? [];

        if ($lines === []) {
            throw new RuntimeException('Could not find the current set import block in resources/js/site.js.');
        }

        $block = implode("\n", $lines);
        $managed = implode("\n", [$definition['start'], $block, $definition['end']]);

        return Str::replaceFirst($block, $managed, $contents);
    }

    private function initializeSiteJsInitializers(array $definition, string $contents): string
    {
        if ($this->hasManagedBlock($definition, $contents)) {
            return $contents;
        }

        preg_match('/\[\s*[\s\S]*?\]\.forEach\(\(init\) => init\(\)\);/m', $contents, $match);
        $block = $match[0] ?? null;

        if (! $block) {
            throw new RuntimeException('Could not find the current initializer block in resources/js/site.js.');
        }

        $managed = implode("\n", [$definition['start'], $block, $definition['end']]);

        return Str::replaceFirst($block, $managed, $contents);
    }

    private function initializePageBuilder(array $definition, string $contents): string
    {
        if ($this->hasManagedBlock($definition, $contents)) {
            return $contents;
        }

        $lines = preg_split("/\r\n|\n|\r/", $contents);
        [$start, $end] = $this->findRange($lines, 'hero_001:', 'import: testimonials_001');

        return $this->wrapLineRange($lines, $start, $end, $definition['start'], $definition['end'], '        ');
    }

    private function initializeBardBlocks(array $definition, string $contents): string
    {
        if ($this->hasManagedBlock($definition, $contents)) {
            return $contents;
        }

        $lines = preg_split("/\r\n|\n|\r/", $contents);
        [$start, $end] = $this->findRange($lines, 'media_001:', 'import: blocks/quote_001');

        return $this->wrapLineRange($lines, $start, $end, $definition['start'], $definition['end'], '                ');
    }

    private function buildInstalledManifest(): array
    {
        $registry = YAML::file(config('section-library.paths.registry'))->parse() ?? [];
        $layoutPatterns = $registry['layout_patterns'] ?? [];

        $bundles = collect($registry['sections'] ?? [])
            ->map(fn (array $section) => $this->installedManifestBundle($section, 'section'))
            ->merge(
                collect($registry['blocks'] ?? [])
                    ->map(fn (array $block) => $this->installedManifestBundle($block, 'block'))
            )
            ->merge(
                collect($layoutPatterns['headers'] ?? [])
                    ->map(fn (array $pattern) => $this->installedManifestBundle($pattern, 'layout_pattern'))
            )
            ->merge(
                collect($layoutPatterns['footers'] ?? [])
                    ->map(fn (array $pattern) => $this->installedManifestBundle($pattern, 'layout_pattern'))
            )
            ->values()
            ->all();

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'library' => [
                'repo' => config('section-library.github.repo'),
                'catalog_path' => config('section-library.github.catalog_path'),
                'installed_at' => Carbon::now()->toIso8601String(),
            ],
            'bundles' => $bundles,
        ];
    }

    private function installedManifestBundle(array $entry, string $type): array
    {
        $handle = $entry['handle'];
        $files = collect($entry['files'])
            ->filter()
            ->map(function (string $path, string $kind) {
                return [
                    'path' => $path,
                    'kind' => $kind,
                ];
            })
            ->values()
            ->all();

        $registrations = [
            'page_builder' => $type === 'section' ? [[
                'blueprint' => resource_path('blueprints/collections/pages/page.yaml'),
                'handle' => $handle,
                'import' => $handle,
            ]] : [],
            'bard_block' => $type === 'block'
                ? collect($entry['dependencies']['blueprints'] ?? [])->map(fn (string $blueprint) => [
                    'blueprint' => $blueprint,
                    'handle' => preg_replace('/_\d+$/', '', Str::after($handle, 'block_')),
                    'import' => $handle,
                ])->values()->all()
                : [],
            'scss' => filled($entry['files']['styles'] ?? null) ? [[
                'file' => resource_path('css/site.scss'),
                'import' => $this->scssImportFor($entry['files']['styles']),
            ]] : [],
            'js' => filled($entry['files']['script'] ?? null) ? [[
                'file' => resource_path('js/site.js'),
                'import' => "import {$this->initializerName($handle)} from './{$this->jsImportFor($entry['files']['script'])}';",
                'initializer' => $this->initializerName($handle),
            ]] : [],
        ];

        return [
            'handle' => $handle,
            'title' => $entry['title'],
            'type' => $type,
            'family' => $entry['family'],
            'source_ref' => self::SOURCE_REF,
            'root_class' => $entry['root_class'] ?? null,
            'files' => $files,
            'registrations' => $registrations,
            'dependencies' => [
                'forms' => $entry['dependencies']['forms'] ?? [],
                'blueprints' => $entry['dependencies']['blueprints'] ?? [],
                'content' => $entry['dependencies']['content'] ?? [],
                'navigation' => $entry['dependencies']['navigation'] ?? [],
                'templates' => $entry['dependencies']['templates'] ?? [],
            ],
        ];
    }

    private function initializerName(string $handle): string
    {
        return 'init'.Str::studly($handle);
    }

    private function scssImportFor(string $path): string
    {
        $relativePath = Str::after($path, resource_path('css/'));
        $importPath = Str::of($relativePath)
            ->replace('\\', '/')
            ->replace('.scss', '')
            ->replace('/_', '/')
            ->replaceMatches('/^_/', '')
            ->value();

        return "@use '{$importPath}';";
    }

    private function jsImportFor(string $path): string
    {
        return Str::of(Str::after($path, resource_path('js/')))
            ->replace('\\', '/')
            ->replace('.js', '')
            ->value();
    }

    private function hasManagedBlock(array $definition, string $contents): bool
    {
        return str_contains($contents, $definition['start']) && str_contains($contents, $definition['end']);
    }

    private function findRange(array $lines, string $startNeedle, string $endNeedle): array
    {
        $start = null;
        $end = null;

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);

            if ($start === null && Str::startsWith($trimmed, $startNeedle)) {
                $start = $index;
            }

            if ($start !== null && Str::contains($trimmed, $endNeedle)) {
                $end = $index;
            }
        }

        if ($start === null || $end === null || $end < $start) {
            throw new RuntimeException("Could not find the expected managed range between `{$startNeedle}` and `{$endNeedle}`.");
        }

        return [$start, $end];
    }

    private function wrapLineRange(array $lines, int $start, int $end, string $startMarker, string $endMarker, string $indent): string
    {
        $wrapped = [
            ...array_slice($lines, 0, $start),
            $indent.$startMarker,
            ...array_slice($lines, $start, ($end - $start) + 1),
            $indent.$endMarker,
            ...array_slice($lines, $end + 1),
        ];

        return implode("\n", $wrapped)."\n";
    }

    private function catalogCacheKey(): string
    {
        return 'section-library.catalog';
    }
}
