import { useEffect, useId, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { compressImagesForUpload } from '@/lib/compress-image';

const MAX_PHOTOS = 3;

type Preview = {
    id: string;
    file: File;
    url: string;
};

export default function CheckpointPhotoPicker({
    photos,
    onChange,
    errors = {},
    disabled = false,
}: {
    photos: File[];
    onChange: (photos: File[]) => void;
    errors?: Record<string, string>;
    disabled?: boolean;
}) {
    const inputId = useId();
    const [previews, setPreviews] = useState<Preview[]>([]);
    const [compressing, setCompressing] = useState(false);

    useEffect(() => {
        const next = photos.map((file) => ({
            id: `${file.name}-${file.size}-${file.lastModified}`,
            file,
            url: URL.createObjectURL(file),
        }));

        setPreviews(next);

        return () => {
            next.forEach((preview) => URL.revokeObjectURL(preview.url));
        };
    }, [photos]);

    return (
        <div className="space-y-3">
            <div className="space-y-1">
                <Label htmlFor={inputId}>Evidencia fotográfica (opcional)</Label>
                <p className="text-sm text-muted-foreground">
                    Hasta {MAX_PHOTOS} fotos. Se comprimen antes de enviar.
                </p>
            </div>

            {previews.length > 0 && (
                <ul className="grid grid-cols-3 gap-2">
                    {previews.map((preview, index) => (
                        <li key={preview.id} className="relative">
                            <img
                                src={preview.url}
                                alt={`Evidencia ${index + 1}`}
                                className="aspect-square w-full rounded-lg object-cover"
                            />
                            <button
                                type="button"
                                className="absolute top-1 right-1 rounded bg-black/70 px-1.5 py-0.5 text-xs text-white"
                                disabled={disabled || compressing}
                                onClick={() =>
                                    onChange(
                                        photos.filter((_, i) => i !== index),
                                    )
                                }
                            >
                                Quitar
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {photos.length < MAX_PHOTOS && (
                <Button type="button" variant="outline" className="w-full" asChild>
                    <label
                        htmlFor={inputId}
                        className={
                            disabled || compressing
                                ? 'pointer-events-none opacity-50'
                                : 'cursor-pointer'
                        }
                    >
                        {compressing ? (
                            <>
                                <Spinner />
                                Comprimiendo…
                            </>
                        ) : photos.length === 0 ? (
                            'Agregar fotos'
                        ) : (
                            `Agregar otra foto (${photos.length}/${MAX_PHOTOS})`
                        )}
                    </label>
                </Button>
            )}

            <input
                id={inputId}
                type="file"
                accept="image/jpeg,image/png,image/webp,image/*"
                capture="environment"
                multiple
                className="sr-only"
                disabled={
                    disabled || compressing || photos.length >= MAX_PHOTOS
                }
                onChange={(event) => {
                    const selected = Array.from(event.target.files ?? []);
                    const remaining = MAX_PHOTOS - photos.length;
                    const batch = selected.slice(0, remaining);

                    event.target.value = '';

                    if (batch.length === 0) {
                        return;
                    }

                    setCompressing(true);

                    void compressImagesForUpload(batch)
                        .then((compressed) => {
                            onChange([...photos, ...compressed]);
                        })
                        .finally(() => {
                            setCompressing(false);
                        });
                }}
            />

            <InputError message={errors.photos} />
            {Object.entries(errors)
                .filter(([key]) => key.startsWith('photos.'))
                .map(([key, message]) => (
                    <InputError key={key} message={message} />
                ))}
        </div>
    );
}
