import { Form, useForm } from '@inertiajs/react';
import { AdminPage } from '@/components/admin/AdminUi';
import { SelectField, SubmitRow, TextAreaField, TextField } from '@/components/admin/FormFields';
import { withBasePath } from '@/lib/format';

export interface FacilityFormProps {
    regions: Record<string, string>;
    /** Present when editing an existing facility. */
    facility?: {
        id: number;
        name: string;
        code: string | null;
        status: string;
        description: string | null;
        address_street: string | null;
        address_barangay: string | null;
        address_city: string | null;
        address_province: string | null;
        address_region: string | null;
        address_postal_code: string | null;
        email: string | null;
        phone: string | null;
        /** '' until filled in, then a number, then null when cleared. */
        latitude: string | number | null;
        longitude: string | number | null;
    };
}

const STATUSES = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];
export default function FacilityForm({ regions, facility }: FacilityFormProps) {
    const editing = Boolean(facility);
    const cancelHref = '/admin/facilities';

    /** Optional text fields are sent as null when left blank. */
    interface FacilityFormData {
        name: string;
        code: string | null;
        status: string;
        description: string | null;
        address_street: string | null;
        address_barangay: string | null;
        address_city: string | null;
        address_province: string | null;
        address_region: string | null;
        address_postal_code: string | null;
        email: string | null;
        phone: string | null;
        /** '' until filled in, then a number, then null when cleared. */
        latitude: string | number | null;
        longitude: string | number | null;
    }

    const form = useForm<FacilityFormData>({
        name: facility?.name ?? '',
        code: facility?.code ?? '',
        status: facility?.status ?? 'active',
        description: facility?.description ?? '',
        address_street: facility?.address_street ?? '',
        address_barangay: facility?.address_barangay ?? '',
        address_city: facility?.address_city ?? '',
        address_province: facility?.address_province ?? '',
        address_region: facility?.address_region ?? '',
        address_postal_code: facility?.address_postal_code ?? '',
        email: facility?.email ?? '',
        phone: facility?.phone ?? '',
        latitude: facility?.latitude ?? '',
        longitude: facility?.longitude ?? '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        // Blank optional strings and empty coordinates are sent as null.
        const nullable = [
            'code',
            'description',
            'address_street',
            'address_barangay',
            'address_city',
            'address_province',
            'address_region',
            'address_postal_code',
            'email',
            'phone',
        ] as const;

        nullable.forEach((field) => {
            const value = form.data[field];
            if (value === '') form.setData(field, null);
        });

        if (form.data.latitude === '') {
            form.setData('latitude', null);
        }
        if (form.data.longitude === '') {
            form.setData('longitude', null);
        }

        if (editing && facility) {
            form.put(withBasePath(`/admin/facilities/${facility.id}`));
        } else {
            form.post(withBasePath('/admin/facilities'));
        }
    };
    return (
        <AdminPage
            title={editing ? `Edit ${facility?.name}` : 'Add a facility'}
            subtitle="Address, contact and map coordinates for this court location."
        >
            <Form onSubmit={submit} className="border-hairline max-w-3xl rounded-panel border bg-white p-7">
                <div className="space-y-5">
                    <div className="grid gap-5 sm:grid-cols-3">
                        <div className="sm:col-span-2">
                            <TextField
                                id="name"
                                label="Facility name"
                                value={form.data.name}
                                onChange={(v) => form.setData('name', v)}
                                error={form.errors.name}
                                required
                                placeholder="Makati Pickleball Club"
                            />
                        </div>
                        <TextField
                            id="code"
                            label="Code (optional)"
                            value={form.data.code}
                            onChange={(v) => form.setData('code', v)}
                            error={form.errors.code}
                            placeholder="MKT-01"
                        />
                    </div>

                    <SelectField
                        id="status"
                        label="Status"
                        value={form.data.status}
                        onChange={(v) => form.setData('status', v)}
                        error={form.errors.status}
                        options={STATUSES}
                    />

                    <TextAreaField
                        id="description"
                        label="Description (optional)"
                        value={form.data.description}
                        onChange={(v) => form.setData('description', v)}
                        error={form.errors.description}
                    />

                    <fieldset className="border-hairline rounded-card border p-5">
                        <legend className="text-pickle-900 px-2 text-sm font-semibold">Address</legend>
                        <div className="mt-3 space-y-5">
                            <TextField
                                id="address_street"
                                label="Street"
                                value={form.data.address_street}
                                onChange={(v) => form.setData('address_street', v)}
                                error={form.errors.address_street}
                            />
                            <div className="grid gap-5 sm:grid-cols-2">
                                <TextField
                                    id="address_barangay"
                                    label="Barangay"
                                    value={form.data.address_barangay}
                                    onChange={(v) => form.setData('address_barangay', v)}
                                    error={form.errors.address_barangay}
                                />
                                <TextField
                                    id="address_city"
                                    label="City / Municipality"
                                    value={form.data.address_city}
                                    onChange={(v) => form.setData('address_city', v)}
                                    error={form.errors.address_city}
                                />
                            </div>
                            <div className="grid gap-5 sm:grid-cols-3">
                                <TextField
                                    id="address_province"
                                    label="Province"
                                    value={form.data.address_province}
                                    onChange={(v) => form.setData('address_province', v)}
                                    error={form.errors.address_province}
                                />
                                <SelectField
                                    id="address_region"
                                    label="Region"
                                    value={form.data.address_region ?? ''}
                                    onChange={(v) => form.setData('address_region', v)}
                                    error={form.errors.address_region}
                                    options={[
                                        { value: '', label: 'Not set' },
                                        ...Object.entries(regions).map(([code, name]) => ({
                                            value: code,
                                            label: name,
                                        })),
                                    ]}
                                />
                                <TextField
                                    id="address_postal_code"
                                    label="Postal code"
                                    value={form.data.address_postal_code}
                                    onChange={(v) => form.setData('address_postal_code', v)}
                                    error={form.errors.address_postal_code}
                                />
                            </div>
                        </div>
                    </fieldset>

                    <fieldset className="border-hairline rounded-card border p-5">
                        <legend className="text-pickle-900 px-2 text-sm font-semibold">Contact</legend>
                        <div className="mt-3 grid gap-5 sm:grid-cols-2">
                            <TextField
                                id="email"
                                label="Email"
                                type="email"
                                value={form.data.email}
                                onChange={(v) => form.setData('email', v)}
                                error={form.errors.email}
                            />
                            <TextField
                                id="phone"
                                label="Phone"
                                value={form.data.phone}
                                onChange={(v) => form.setData('phone', v)}
                                error={form.errors.phone}
                                placeholder="+63 2 8123 4567"
                            />
                        </div>
                    </fieldset>

                    <fieldset className="border-hairline rounded-card border p-5">
                        <legend className="text-pickle-900 px-2 text-sm font-semibold">Map coordinates</legend>
                        <div className="mt-3 grid gap-5 sm:grid-cols-2">
                            <TextField
                                id="latitude"
                                label="Latitude"
                                value={form.data.latitude}
                                onChange={(v) => form.setData('latitude', v)}
                                error={form.errors.latitude}
                                hint="Used for distance search."
                            />
                            <TextField
                                id="longitude"
                                label="Longitude"
                                value={form.data.longitude}
                                onChange={(v) => form.setData('longitude', v)}
                                error={form.errors.longitude}
                            />
                        </div>
                    </fieldset>
                </div>

                <SubmitRow
                    processing={form.processing}
                    submitLabel={editing ? 'Save changes' : 'Create facility'}
                    cancelHref={cancelHref}
                />
            </Form>
        </AdminPage>
    );
}
