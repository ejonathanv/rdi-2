import { Form, Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BrandingController from '@/actions/App/Http/Controllers/BrandingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { edit as brandingEdit } from '@/routes/branding';

type BrandingColors = {
    primary_color: string;
    light_color: string;
    dark_color: string;
};

type ColorField = {
    name: keyof BrandingColors;
    label: string;
    description: string;
};

const colorFields: ColorField[] = [
    {
        name: 'primary_color',
        label: 'Color principal',
        description: 'Botones, enlaces y elementos destacados.',
    },
    {
        name: 'light_color',
        label: 'Color claro',
        description: 'Fondos suaves y estados de selección.',
    },
    {
        name: 'dark_color',
        label: 'Color oscuro',
        description: 'Barra lateral y encabezados de correos.',
    },
];

export default function BrandingEdit({
    defaults,
}: {
    defaults: BrandingColors;
}) {
    const { branding } = usePage().props;

    const [colors, setColors] = useState<BrandingColors>({
        primary_color: branding.primary_color,
        light_color: branding.light_color,
        dark_color: branding.dark_color,
    });
    const [logoPreview, setLogoPreview] = useState<string | null>(null);
    const [faviconPreview, setFaviconPreview] = useState<string | null>(null);

    const updateColor = (name: keyof BrandingColors, value: string) => {
        setColors((current) => ({ ...current, [name]: value }));
    };

    const previewFile = (
        file: File | undefined,
        setPreview: (url: string | null) => void,
    ) => {
        setPreview(file ? URL.createObjectURL(file) : null);
    };

    return (
        <>
            <Head title="Imagen corporativa" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Imagen corporativa"
                    description="Personaliza los colores, el logotipo y el favicon del sistema."
                />

                <Form
                    {...BrandingController.update.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => {
                        setLogoPreview(null);
                        setFaviconPreview(null);
                    }}
                    resetOnSuccess={['logo', 'favicon']}
                    className="grid max-w-4xl gap-6 lg:grid-cols-[1fr_280px]"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="space-y-6">
                                <div className="space-y-4 rounded-xl border p-4">
                                    <Heading
                                        variant="small"
                                        title="Colores"
                                        description="Usa colores con buen contraste frente al texto blanco."
                                    />

                                    {colorFields.map((field) => (
                                        <div
                                            key={field.name}
                                            className="grid gap-2"
                                        >
                                            <Label htmlFor={field.name}>
                                                {field.label}
                                            </Label>
                                            <div className="flex items-center gap-3">
                                                <input
                                                    type="color"
                                                    id={field.name}
                                                    name={field.name}
                                                    value={colors[field.name]}
                                                    onChange={(event) =>
                                                        updateColor(
                                                            field.name,
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="h-9 w-14 cursor-pointer rounded-md border bg-transparent p-1"
                                                />
                                                <Input
                                                    value={colors[field.name]}
                                                    onChange={(event) =>
                                                        updateColor(
                                                            field.name,
                                                            event.target.value,
                                                        )
                                                    }
                                                    maxLength={7}
                                                    className="w-32 font-mono uppercase"
                                                    aria-label={field.label}
                                                />
                                            </div>
                                            <p className="text-sm text-muted-foreground">
                                                {field.description}
                                            </p>
                                            <InputError
                                                message={errors[field.name]}
                                            />
                                        </div>
                                    ))}

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setColors(defaults)}
                                    >
                                        Restaurar colores predeterminados
                                    </Button>
                                </div>

                                <div className="space-y-4 rounded-xl border p-4">
                                    <Heading
                                        variant="small"
                                        title="Logotipo"
                                        description="PNG, JPG o WEBP de hasta 2 MB. Se muestra en la barra lateral, el inicio de sesión, los correos y el PDF de rondines."
                                    />

                                    <Input
                                        type="file"
                                        name="logo"
                                        accept="image/png,image/jpeg,image/webp"
                                        onChange={(event) =>
                                            previewFile(
                                                event.target.files?.[0],
                                                setLogoPreview,
                                            )
                                        }
                                    />
                                    <InputError message={errors.logo} />

                                    {branding.has_custom_logo && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                router.delete(
                                                    BrandingController.destroyLogo.url(),
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Restaurar logotipo de RDI
                                        </Button>
                                    )}
                                </div>

                                <div className="space-y-4 rounded-xl border p-4">
                                    <Heading
                                        variant="small"
                                        title="Favicon"
                                        description="PNG o ICO cuadrado de hasta 512 KB. Se muestra en la pestaña del navegador y en las notificaciones."
                                    />

                                    <div className="flex items-center gap-3">
                                        <img
                                            src={
                                                faviconPreview ??
                                                branding.favicon_url
                                            }
                                            alt="Favicon"
                                            className="size-10 rounded-md border object-contain p-1"
                                        />
                                        <Input
                                            type="file"
                                            name="favicon"
                                            accept="image/png,image/x-icon,.ico"
                                            onChange={(event) =>
                                                previewFile(
                                                    event.target.files?.[0],
                                                    setFaviconPreview,
                                                )
                                            }
                                        />
                                    </div>
                                    <InputError message={errors.favicon} />

                                    {branding.has_custom_favicon && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                router.delete(
                                                    BrandingController.destroyFavicon.url(),
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Restaurar favicon de RDI
                                        </Button>
                                    )}
                                </div>

                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Guardar imagen corporativa
                                </Button>
                            </div>

                            <div className="space-y-3 lg:sticky lg:top-4 lg:self-start">
                                <p className="text-sm font-medium">
                                    Vista previa
                                </p>
                                <div className="overflow-hidden rounded-xl border">
                                    <div
                                        className="p-4"
                                        style={{
                                            backgroundColor: colors.dark_color,
                                        }}
                                    >
                                        <img
                                            src={
                                                logoPreview ??
                                                branding.logo_url
                                            }
                                            alt="Logotipo"
                                            className="h-12 w-auto max-w-full object-contain"
                                        />
                                    </div>
                                    <div
                                        className="space-y-3 p-4"
                                        style={{
                                            backgroundColor: colors.light_color,
                                        }}
                                    >
                                        <p className="text-sm text-neutral-800">
                                            Así se verán los elementos
                                            principales.
                                        </p>
                                        <span
                                            className="inline-flex rounded-md px-3 py-2 text-sm font-medium text-white"
                                            style={{
                                                backgroundColor:
                                                    colors.primary_color,
                                            }}
                                        >
                                            Botón principal
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

BrandingEdit.layout = {
    breadcrumbs: [
        {
            title: 'Imagen corporativa',
            href: brandingEdit(),
        },
    ],
};
