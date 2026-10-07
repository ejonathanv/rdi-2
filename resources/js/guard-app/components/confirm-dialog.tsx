import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

export function ConfirmDialog({
    open,
    title,
    description,
    confirmLabel,
    processing,
    onCancel,
    onConfirm,
}: {
    open: boolean;
    title: string;
    description: string;
    confirmLabel: string;
    processing: boolean;
    onCancel: () => void;
    onConfirm: () => void;
}) {
    return (
        <Dialog
            open={open}
            onOpenChange={(value) => !value && !processing && onCancel()}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={processing}
                        onClick={onCancel}
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        disabled={processing}
                        onClick={onConfirm}
                    >
                        {processing && <Spinner />}
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
