<?php

namespace App\Scopes;

use Illuminate\Support\Arr;
use Statamic\Facades\Entry;
use Statamic\Query\Scopes\Scope;

class RelatedBlogPosts extends Scope
{
    public static function handle()
    {
        return 'related_blog_posts';
    }

    public function apply($query, $values)
    {
        $currentId = $values['current_id'] ?? null;

        if ($currentId) {
            $query->where('id', '!=', $currentId);
        }

        $currentEntry = $currentId ? Entry::find($currentId) : null;

        if (! $currentEntry) {
            return;
        }

        $categorySlugs = collect(Arr::wrap($currentEntry->get('blog_categories')))
            ->filter()
            ->values();

        if ($categorySlugs->isEmpty()) {
            return;
        }

        $query->where(function ($subquery) use ($categorySlugs) {
            $categorySlugs->each(function ($slug, $index) use ($subquery) {
                $method = $index === 0 ? 'whereJsonContains' : 'orWhereJsonContains';

                $subquery->{$method}('blog_categories', $slug);
            });
        });
    }
}
