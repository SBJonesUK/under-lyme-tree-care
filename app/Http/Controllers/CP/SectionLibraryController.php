<?php

namespace App\Http\Controllers\CP;

use App\SectionLibrary\SectionLibraryManager;
use Illuminate\Http\RedirectResponse;
use Statamic\Http\Controllers\CP\CpController;

class SectionLibraryController extends CpController
{
    public function __construct(private readonly SectionLibraryManager $sectionLibrary)
    {
    }

    public function index()
    {
        return view('cp.section-library.index', $this->sectionLibrary->dashboardData());
    }

    public function refresh(): RedirectResponse
    {
        $result = $this->sectionLibrary->refresh();

        return redirect()
            ->to(cp_route('utilities.section-library'))
            ->with($result['flash'], $result['message']);
    }

    public function initialize(): RedirectResponse
    {
        try {
            $result = $this->sectionLibrary->initialize();
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->to(cp_route('utilities.section-library'))
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->to(cp_route('utilities.section-library'))
            ->with($result['flash'], $result['message']);
    }
}
