import { useForm } from '@inertiajs/react';
import { AdminPage } from '@/components/admin/AdminUi';
import { SelectField, SubmitRow, TextAreaField, TextField } from '@/components/admin/FormFields';
import { withBasePath } from '@/lib/format';

export interface CourtFormProps {
    branches: Array<{ id: number; name: string }>;
    defaults: {
        branch_id: number | null;
        surface: string;
        type: string;
        setting: string;
        status: string;
        capacity: number;
    };
    /** Present when editing an existing court. */
    court?: {
        id: number;
        branch_id: number;
        name: string;
        number: string | null;
        surface: string;
        type: string;
        setting: string;
        status: string;
        capacity: number;
        notes: string | null;
    };
}
const SURFACES = [
    { value: 'hard', label: 'Hard court' },
    { value: 'soft', label: 'Soft court' },
    { value: 'acrylic', label: 'Acrylic' },
    { value: 'cushioned', label: 'Cushioned' },
    { value: 'other', label: 'Other' },
];

const TYPES = [
    { value: 'standard', label: 'Standard' },
    { value: 'dedicated', label: 'Dedicated' },
    { value: 'tournament', label: 'Tournament grade' },
];

const SETTINGS = [
    { value: 'indoor', label: 'Indoor' },
    { value: 'outdoor', label: 'Outdoor' },
];

const STATUSES = [
    { value: 'available', label: 'Available' },
    { value: 'maintenance', label: 'Maintenance' },
    { value: 'blocked', label: 'Blocked' },
    { value: 'retired', label: 'Retired' },
];

export default function CourtForm({ branches, defaults, court }: CourtFormProps) {
    const editing = Boolean(court);
    const cancelHref = editing && court ? `/admin/courts/${court.id}` : '/admin/courts';

    interface CourtFormData {
        branch_id: string | number;
        name: string;
        /** Optional, sent as null when blank. */
        number: string | null;
        surface: string;
        type: string;
        setting: string;
        status: string;
        capacity: number;
        /** Optional, sent as null when blank. */
        notes: string | null;
    }

    const form = useForm<CourtFormData>({
        branch_id: court?.branch_id ?? defaults.branch_id ?? '',
        name: court?.name ?? '',
        number: court?.number ?? '',
        surface: court?.surface ?? defaults.surface,
        type: court?.type ?? defaults.type,
        setting: court?.setting ?? defaults.setting,
        status: court?.status ?? defaults.status,
        capacity: court?.capacity ?? defaults.capacity,
        notes: court?.notes ?? '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        // Normalise before submitting: empty optional strings become null and
        // numeric fields become real numbers. This Inertia version sends
        // form.data, so the values are coerced in place rather than passed as
        // a second argument.
        form.setData('branch_id', Number(form.data.branch_id));
        form.setData('capacity', Number(form.data.capacity));
        form.setData('number', form.data.number === '' ? null : form.data.number);
        form.setData('notes', form.data.notes === '' ? null : form.data.notes);

        if (editing && court) {
            form.put(withBasePath(`/admin/courts/${court.id}`));
        } else {
            form.post(withBasePath('/admin/courts'));
        }
    };
    return (
        <AdminPage
            title={editing ? `Edit ${court?.name}` : 'Add a court'}
            subtitle="Surface, capacity and status control what players can book."
        >
            <form onSubmit={submit} className="border-hairline max-w-2xl rounded-panel border bg-white p-7">
                <div className="space-y-5">
                    <SelectField
                        id="branch_id"
                        label="Facility"
                        value={form.data.branch_id}
                        onChange={(value) => form.setData('branch_id', value)}
                        error={form.errors.branch_id}
                        options={branches.map((branch) => ({ value: branch.id, label: branch.name }))}
                        required
                    />

                    <TextField
                        id="name"
                        label="Court name"
                        value={form.data.name}
                        onChange={(value) => form.setData('name', value)}
                        error={form.errors.name}
                        required
                        placeholder="Court A"
                    />

                    <TextField
                        id="number"
                        label="Court number (optional)"
                        value={form.data.number}
                        onChange={(value) => form.setData('number', value)}
                        error={form.errors.number}
                        hint="Must be unique within the facility."
                        placeholder="A1"
                    />

                    <div className="grid gap-5 sm:grid-cols-2">
                        <SelectField
                            id="surface"
                            label="Surface"
                            value={form.data.surface}
                            onChange={(value) => form.setData('surface', value)}
                            error={form.errors.surface}
                            options={SURFACES}
                        />
                        <SelectField
                            id="type"
                            label="Type"
                            value={form.data.type}
                            onChange={(value) => form.setData('type', value)}
                            error={form.errors.type}
                            options={TYPES}
                        />
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <SelectField
                            id="setting"
                            label="Setting"
                            value={form.data.setting}
                            onChange={(value) => form.setData('setting', value)}
                            error={form.errors.setting}
                            options={SETTINGS}
                        />
                        <SelectField
                            id="status"
                            label="Status"
                            value={form.data.status}
                            onChange={(value) => form.setData('status', value)}
                            error={form.errors.status}
                            options={STATUSES}
                        />
                    </div>

                    <TextField
                        id="capacity"
                        label="Capacity (players)"
                        type="number"
                        min={2}
                        max={12}
                        value={form.data.capacity}
                        onChange={(value) => form.setData('capacity', Number(value))}
                        error={form.errors.capacity}
                        required
                    />

                    <TextAreaField
                        id="notes"
                        label="Notes (optional)"
                        value={form.data.notes}
                        onChange={(value) => form.setData('notes', value)}
                        error={form.errors.notes}
                    />
                </div>

                <SubmitRow
                    processing={form.processing}
                    submitLabel={editing ? 'Save changes' : 'Create court'}
                    cancelHref={cancelHref}
                />
            </form>
        </AdminPage>
    );
}
