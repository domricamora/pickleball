<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourtRequest;
use App\Models\Branch;
use App\Models\Court;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourtController extends Controller
{
    /**
     * List every court in the tenant.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Court::class);

        $branchId = $request->integer('branch_id') ?: null;

        $courts = Court::query()
            ->when($branchId, fn ($query) => $query->inBranch($branchId))
            ->with('branch:id,name')
            ->orderBy('name')
            ->get(['id', 'organization_id', 'branch_id', 'name', 'number', 'surface', 'type', 'setting', 'status', 'capacity']);

        return Inertia::render('admin/courts/Index', [
            'courts' => $courts,
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'branch_id' => $branchId,
                'status' => $request->string('status')->toString() ?: null,
            ],
        ]);
    }

    /**
     * Show one court's schedule, blocks and pricing.
     */
    public function show(Court $court): Response
    {
        $this->authorize('view', $court);

        $court->load([
            'branch:id,name,address_city',
            'schedules' => fn ($query) => $query->orderBy('weekday'),
            'blocks' => fn ($query) => $query->orderByDesc('starts_on')->limit(10),
            'prices' => fn ($query) => $query->orderBy('type'),
        ]);

        return Inertia::render('admin/courts/Show', [
            'court' => $court,
            'weekdays' => config('platform.weekdays'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Court::class);

        return Inertia::render('admin/courts/Create', [
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'defaults' => [
                'branch_id' => $request->integer('branch_id') ?: null,
                'surface' => 'hard',
                'type' => 'standard',
                'setting' => 'indoor',
                'status' => 'available',
                'capacity' => 4,
            ],
        ]);
    }

    public function store(StoreCourtRequest $request): RedirectResponse
    {
        $this->authorize('create', Court::class);

        $validated = $request->validated();
        unset($validated['branch_id']);

        $court = Court::create([
            ...$validated,
            'branch_id' => $request->integer('branch_id'),
            // Derived from the branch, never from the session or the client.
            // This guarantees a court can never belong to a different tenant
            // than the branch it sits in, even for platform staff.
            'organization_id' => $this->resolveOrganizationId($request),
        ]);

        return to_route('admin.courts.show', $court)
            ->with('success', "{$court->name} was added.");
    }

    /**
     * The tenant a new court belongs to, taken from its branch.
     *
     * Tenant staff must target a branch in their own organisation; Super Admin
     * inherits whichever tenant the chosen branch belongs to.
     */
    protected function resolveOrganizationId(StoreCourtRequest $request): int
    {
        $branch = Branch::query()->findOrFail($request->integer('branch_id'));

        return $branch->organization_id;
    }

    public function edit(Court $court): Response
    {
        $this->authorize('update', $court);

        $court->load('branch:id,name');

        return Inertia::render('admin/courts/Edit', [
            'court' => $court,
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(StoreCourtRequest $request, Court $court): RedirectResponse
    {
        $this->authorize('update', $court);

        $court->update($request->validated());

        return to_route('admin.courts.show', $court)
            ->with('success', "{$court->name} was updated.");
    }

    public function destroy(Court $court): RedirectResponse
    {
        $this->authorize('delete', $court);

        // Soft delete: a court with historical bookings must never vanish.
        $court->delete();

        return to_route('admin.courts.index')->with('success', "{$court->name} was removed.");
    }
}
