<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Branch::class);

        $branches = Branch::query()
            ->withCount('courts')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/facilities/Index', [
            'facilities' => $branches,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Branch::class);

        return Inertia::render('admin/facilities/Create', [
            'regions' => config('platform.regions'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Branch::class);

        $data = $request->validate($this->rules());

        $branch = Branch::create([
            ...$data,
            // The tenant always comes from the session, never the request.
            'organization_id' => $request->user()->organization_id,
        ]);

        return to_route('admin.facilities.index')
            ->with('success', "{$branch->name} was created.");
    }

    public function edit(Branch $branch): Response
    {
        $this->authorize('update', $branch);

        $branch->loadCount('courts');

        return Inertia::render('admin/facilities/Edit', [
            'facility' => $branch,
            'regions' => config('platform.regions'),
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);

        $data = $request->validate($this->rules($branch));

        $branch->update($data);

        return to_route('admin.facilities.index')
            ->with('success', "{$branch->name} was updated.");
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $name = $branch->name;
        $branch->delete();

        return to_route('admin.facilities.index')
            ->with('success', "{$name} was removed.");
    }

    /**
     * Philippine address fields (plan.md §11, §32).
     *
     * @return array<string, mixed>
     */
    protected function rules(?Branch $branch = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:32',
                // Scoped to the caller's tenant so the same short code can
                // exist at two different organisations.
                Rule::unique('branches', 'code')
                    ->where('organization_id', request()->user()?->organization_id)
                    ->whereNull('deleted_at'),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_barangay' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_province' => ['nullable', 'string', 'max:255'],
            'address_region' => ['nullable', 'string', 'max:255'],
            'address_postal_code' => ['nullable', 'string', 'max:16'],
            'address_country' => ['nullable', 'string', 'size:2'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
