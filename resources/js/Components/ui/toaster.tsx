import {
    Toast,
    ToastClose,
    ToastDescription,
    ToastProvider,
    ToastTitle,
    ToastViewport,
} from '@/Components/ui/toast';
import { useToast } from '@/hooks/use-toast';
import { CheckCircle2, XCircle, Info } from 'lucide-react';

export function Toaster() {
    const { toasts } = useToast();

    const getIcon = (variant?: string) => {
        switch (variant) {
            case 'success':
                return <CheckCircle2 className="h-5 w-5 text-green-600" />;
            case 'destructive':
                return <XCircle className="h-5 w-5 text-red-600" />;
            default:
                return <Info className="h-5 w-5 text-blue-600" />;
        }
    };

    return (
        <ToastProvider>
            {toasts.map(function ({ id, title, description, action, variant, ...props }) {
                return (
                    <Toast key={id} variant={variant} {...props}>
                        <div className="flex items-start gap-3">
                            {getIcon(variant)}
                            <div className="grid gap-1">
                                {title && <ToastTitle>{title}</ToastTitle>}
                                {description && (
                                    <ToastDescription>{description}</ToastDescription>
                                )}
                            </div>
                        </div>
                        {action}
                        <ToastClose />
                    </Toast>
                );
            })}
            <ToastViewport />
        </ToastProvider>
    );
}
