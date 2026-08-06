import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

export default function UrgentReviewFields({
    isUrgent,
    urgentNotes,
    onUrgentChange,
    onNotesChange,
    errors = {},
    disabled = false,
}: {
    isUrgent: boolean;
    urgentNotes: string;
    onUrgentChange: (value: boolean) => void;
    onNotesChange: (value: string) => void;
    errors?: Record<string, string>;
    disabled?: boolean;
}) {
    return (
        <div className="space-y-3 rounded-xl border p-4">
            <div className="flex items-center justify-between gap-4">
                <div className="space-y-1">
                    <Label htmlFor="is_urgent" className="text-destructive">
                        Urgente de revisión
                    </Label>
                    <p className="text-sm text-muted-foreground">
                        Notifica al contacto asignado al recorrido.
                    </p>
                </div>
                <Checkbox
                    id="is_urgent"
                    checked={isUrgent}
                    disabled={disabled}
                    onCheckedChange={(checked) =>
                        onUrgentChange(checked === true)
                    }
                />
            </div>

            {isUrgent && (
                <div className="grid gap-2">
                    <Label htmlFor="urgent_notes">Notas adicionales</Label>
                    <textarea
                        id="urgent_notes"
                        rows={3}
                        value={urgentNotes}
                        disabled={disabled}
                        onChange={(event) =>
                            onNotesChange(event.target.value)
                        }
                        placeholder="Describe lo observado en el punto…"
                        className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-background px-3 py-2 text-base shadow-xs outline-none focus-visible:ring-[3px] md:text-sm"
                    />
                    <InputError message={errors.urgent_notes} />
                </div>
            )}

            <InputError message={errors.is_urgent} />
        </div>
    );
}
