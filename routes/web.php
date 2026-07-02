<?php

use Illuminate\Support\Facades\Route;
use Statamic\Facades\Site;
use Statamic\Facades\Term;
use Statamic\Http\Responses\DataResponse;

Route::statamic('/blog', 'blog.index', [
    'title' => 'Blog',
]);

Route::statamic('/color-preview', 'color-preview', [
    'title' => 'Color Preview',
]);

Route::get('/blog/category/{slug}', function (string $slug) {
    $term = Term::find("blog_categories::{$slug}");

    abort_unless($term, 404);

    $term = $term->in(Site::current()->handle())
        ->template('blog.category')
        ->layout('layout');

    return (new DataResponse($term))
        ->with([
            'term' => $term,
        ])
        ->toResponse(request());
});
