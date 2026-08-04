const DEFAULT_MAX_SIDE = 1920;
const DEFAULT_QUALITY = 0.72;

/**
 * Redimensiona y comprime una imagen a JPEG para subirla por la red.
 * Si falla (p. ej. HEIC no soportado), devuelve el archivo original.
 */
export async function compressImageForUpload(
    file: File,
    maxSide = DEFAULT_MAX_SIDE,
    quality = DEFAULT_QUALITY,
): Promise<File> {
    if (!file.type.startsWith('image/') && file.type !== '') {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(
            1,
            maxSide / Math.max(bitmap.width, bitmap.height),
        );
        const width = Math.max(1, Math.round(bitmap.width * scale));
        const height = Math.max(1, Math.round(bitmap.height * scale));

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const context = canvas.getContext('2d');

        if (!context) {
            bitmap.close();

            return file;
        }

        context.drawImage(bitmap, 0, 0, width, height);
        bitmap.close();

        const blob = await new Promise<Blob | null>((resolve) => {
            canvas.toBlob(resolve, 'image/jpeg', quality);
        });

        if (!blob) {
            return file;
        }

        const baseName = file.name.replace(/\.[^.]+$/, '') || 'evidencia';

        return new File([blob], `${baseName}.jpg`, {
            type: 'image/jpeg',
            lastModified: Date.now(),
        });
    } catch {
        return file;
    }
}

export async function compressImagesForUpload(files: File[]): Promise<File[]> {
    return Promise.all(files.map((file) => compressImageForUpload(file)));
}
