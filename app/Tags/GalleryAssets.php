<?php

namespace App\Tags;

use Illuminate\Support\Arr;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Term;
use Statamic\Tags\Tags;

class GalleryAssets extends Tags
{
    public function index()
    {
        $containerHandle = $this->params->get('container', 'gallery');
        $container = AssetContainer::find($containerHandle);

        if (! $container) {
            return $this->parseNoResults();
        }

        $assets = $container->assets('/', true);
        $category = $this->params->get('category');
        $limit = $this->params->int('limit');

        if ($category) {
            $assets = $assets->filter(fn ($asset) => $this->assetHasCategory($asset, $category));
        }

        $sort = $this->params->get('sort');

        if ($sort) {
            $assets = $assets->multisort($sort);
        } else {
            $direction = $container->sortDirection();
            $field = $container->sortField();

            $assets = $direction === 'desc'
                ? $assets->sortByDesc(fn ($asset) => $this->sortValue($asset, $field))
                : $assets->sortBy(fn ($asset) => $this->sortValue($asset, $field));
        }

        if ($limit > 0) {
            $assets = $assets->take($limit);
        }

        if ($assets->isEmpty()) {
            return $this->parseNoResults();
        }

        return $this->parseLoop($assets->values());
    }

    protected function assetHasCategory($asset, string $category): bool
    {
        $values = collect(Arr::wrap($asset->get('gallery_categories')))
            ->map(function ($value) {
                if (is_string($value)) {
                    return str_contains($value, '::') ? $value : "gallery_categories::{$value}";
                }

                return $value;
            })
            ->filter();

        if ($values->isEmpty()) {
            return false;
        }

        $needle = str_contains($category, '::') ? $category : "gallery_categories::{$category}";

        if ($values->contains($needle)) {
            return true;
        }

        return $values
            ->map(fn ($value) => Term::find($value)?->slug())
            ->filter()
            ->contains($category);
    }

    protected function sortValue($asset, string $field): mixed
    {
        return match ($field) {
            'last_modified' => $asset->lastModified(),
            'basename' => $asset->basename(),
            default => $asset->get($field),
        };
    }
}
