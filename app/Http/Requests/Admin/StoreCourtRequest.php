<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourtStatus;
use App\Enums\CourtSurface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreCourtRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
                // For tenant staff the branch must belong to their own
                // organisation. Super Admin administers every tenant, so no
                // restriction applies — the court still inherits the branch's
                // organization_id, which is derived server-side in the
                // controller and can never be supplied by the client.
                Rule::exists('branches', 'id')->when(
                    $this->tenantId() !== null,
                    // Fully qualified: `Rule` here is the facade, not the
                    // Illuminate\Validation\Rule interface.
                    fn (Exists $rule) => $rule
                        ->where('organization_id', $this->tenantId()),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'number' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('courts', 'number')
                    ->where('branch_id', $this->input('branch_id'))
                    ->whereNull('deleted_at'),
            ],
            'surface' => ['required', Rule::in(CourtSurface::values())],
            'type' => ['required', Rule::in(['standard', 'dedicated', 'tournament'])],
            'setting' => ['required', Rule::in(['indoor', 'outdoor'])],
            'status' => ['required', Rule::in(CourtStatus::values())],
            'capacity' => ['required', 'integer', 'min:2', 'max:12'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch_id.exists' => 'Choose a facility from your organisation.',
            'number.unique' => 'That court number is already used at this facility.',
        ];
    }

    protected function tenantId(): ?int
    {
        return $this->user()?->organization_id;
    }
}
