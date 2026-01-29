import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import type { LucideIcon } from 'lucide-react';

interface EmptyStateAction {
    label: string;
    href?: string;
    onClick?: () => void;
}

interface EmptyStateProps {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: EmptyStateAction;
    secondaryAction?: EmptyStateAction;
    className?: string;
}

export default function EmptyState({
    icon: Icon,
    title,
    description,
    action,
    secondaryAction,
    className,
}: EmptyStateProps) {
    return (
        <div
            className={cn(
                'py-16 text-center bg-gray-50/50 rounded-xl',
                className
            )}
        >
            <div className="mx-auto w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center mb-6">
                <Icon className="h-10 w-10 text-gray-400" />
            </div>
            <h3 className="text-lg font-semibold text-gray-900 mb-2">{title}</h3>
            {description && (
                <p className="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                    {description}
                </p>
            )}
            {(action || secondaryAction) && (
                <div className="flex items-center justify-center gap-3">
                    {action && (
                        action.href ? (
                            <Button asChild>
                                <Link href={action.href}>{action.label}</Link>
                            </Button>
                        ) : (
                            <Button onClick={action.onClick}>{action.label}</Button>
                        )
                    )}
                    {secondaryAction && (
                        secondaryAction.href ? (
                            <Button variant="outline" asChild>
                                <Link href={secondaryAction.href}>
                                    {secondaryAction.label}
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="outline" onClick={secondaryAction.onClick}>
                                {secondaryAction.label}
                            </Button>
                        )
                    )}
                </div>
            )}
        </div>
    );
}
