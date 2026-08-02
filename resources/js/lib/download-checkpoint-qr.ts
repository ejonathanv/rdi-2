import QRCode from 'qrcode';

function slugify(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 60);
}

export async function downloadCheckpointQr(
    scanUrl: string,
    checkpointName: string,
): Promise<void> {
    const dataUrl = await QRCode.toDataURL(scanUrl, {
        width: 512,
        margin: 2,
        errorCorrectionLevel: 'M',
    });

    const link = document.createElement('a');
    const slug = slugify(checkpointName) || 'punto';
    link.href = dataUrl;
    link.download = `qr-${slug}.png`;
    link.click();
}
