@extends('statamic::layout')

@php
    $statusMeta = [
        'ready' => ['label' => 'Ready', 'classes' => 'bg-green-100 text-green-800'],
        'not_initialized' => ['label' => 'Not initialized', 'classes' => 'bg-yellow-100 text-yellow-800'],
        'attention_required' => ['label' => 'Attention required', 'classes' => 'bg-red-100 text-red-800'],
    ][$status];
@endphp

@section('title', 'Section Library')

@section('content')
    <div class="p-6">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold">Section Library</h1>
                    <p class="text-sm text-gray-600">
                        Developer-only utility for initializing, installing, and managing reusable section bundles in this starter kit.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusMeta['classes'] }}">
                        {{ $statusMeta['label'] }}
                    </span>

                    <form method="POST" action="{{ cp_route('utilities.section-library.refresh') }}">
                        @csrf
                        <button class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50" type="submit">
                            Refresh Library
                        </button>
                    </form>
                </div>
            </div>

            @foreach (['success', 'error'] as $flashType)
                @if (session($flashType))
                    <div class="rounded-lg border px-4 py-3 {{ $flashType === 'success' ? 'border-green-200 bg-green-50 text-green-900' : 'border-red-200 bg-red-50 text-red-900' }}">
                        {{ session($flashType) }}
                    </div>
                @endif
            @endforeach

            <div class="grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
                <section class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold">Project readiness</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                This checks the starter-kit structure, config readiness, and whether the section library has been initialized in this project.
                            </p>
                        </div>

                        @if ($status !== 'attention_required' && ! $isInitialized)
                            <form method="POST" action="{{ cp_route('utilities.section-library.initialize') }}">
                                @csrf
                                <button class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" type="submit">
                                    Initialize Section Library
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="mt-5 space-y-5">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Blocking issues</h3>
                            @if (count($compatibility['blocking_issues']))
                                <ul class="mt-2 space-y-2 text-sm text-red-800">
                                    @foreach ($compatibility['blocking_issues'] as $issue)
                                        <li class="rounded-md border border-red-200 bg-red-50 px-3 py-2">{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-2 text-sm text-green-700">No blocking issues. This project is structurally ready for the section library workflow.</p>
                            @endif
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Managed integration points</h3>
                            <ul class="mt-2 divide-y rounded-lg border">
                                @foreach ($managedFiles as $managedFile)
                                    <li class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ str($managedFile['handle'])->replace('_', ' ')->headline() }}</p>
                                            <p class="text-gray-500">{{ $managedFile['path'] }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $managedFile['ready'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $managedFile['ready'] ? 'Managed' : 'Pending init' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="text-base font-semibold">Available bundles</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            The GitHub-backed browser lands next. This scaffold already reserves the UI structure, cache action, and compatibility gates we agreed on.
                        </p>
                    </div>

                    <div class="mt-5 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5 text-sm text-gray-600">
                        {{ $availableMessage }}
                    </div>
                </section>
            </div>

            <section class="rounded-xl border bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold">Installed bundles</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Once initialized, this list is driven by <code>resources/section-library-installed.yaml</code> so install and removal logic can stay explicit and safe.
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                        {{ count($installedBundles) }} installed
                    </span>
                </div>

                @if (count($installedBundles))
                    <div class="mt-5 space-y-3">
                        @foreach ($installedBundles as $bundle)
                            <details class="rounded-lg border">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $bundle['title'] }}</p>
                                        <p class="text-sm text-gray-500">{{ str($bundle['type'])->replace('_', ' ')->headline() }} · {{ $bundle['handle'] }}</p>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-500">{{ $bundle['source_ref'] ?? 'local' }}</span>
                                </summary>
                                <div class="border-t px-4 py-4 text-sm text-gray-700 space-y-4">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">Files</h3>
                                        <ul class="mt-2 space-y-1">
                                            @foreach ($bundle['files'] as $file)
                                                <li><code>{{ $file['path'] }}</code> <span class="text-gray-500">({{ $file['kind'] }})</span></li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    <div>
                                        <h3 class="font-semibold text-gray-900">Registrations</h3>
                                        <pre class="mt-2 overflow-x-auto rounded-md bg-gray-50 p-3 text-xs">{{ json_encode($bundle['registrations'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>

                                    <div>
                                        <h3 class="font-semibold text-gray-900">Dependencies</h3>
                                        <pre class="mt-2 overflow-x-auto rounded-md bg-gray-50 p-3 text-xs">{{ json_encode($bundle['dependencies'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5 text-sm text-gray-600">
                        No installed bundle manifest yet. Run initialization after the project passes compatibility preflight.
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
