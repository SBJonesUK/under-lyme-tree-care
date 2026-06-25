<?php

use Illuminate\Support\Facades\Route;
use Statamic\Facades\Site;
use Statamic\Facades\Term;
use Statamic\Http\Responses\DataResponse;

Route::statamic('/blog', 'blog.index', [
    'title' => 'Blog',
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
Route::statamic('/case-studies', 'case_studies.index', [
    'title' => 'Case Studies',
]);
Route::get('/case-studies/category/{slug}', function (string $slug) {
    $term = Term::find("case_study_categories::{$slug}");

    abort_unless($term, 404);

    $term = $term->in(Site::current()->handle())
        ->template('case_studies.category')
        ->layout('layout');

    return (new DataResponse($term))
        ->with([
            'term' => $term,
            'entries' => $term->queryEntries()->where('site', Site::current()->handle())->get(),
        ])
        ->toResponse(request());
});
