import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { MoreHorizontal } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export interface ActionItem {
    label: string;
    icon?: LucideIcon;
    href?: string;
    external?: boolean;
    target?: string;
    onClick?: () => void;
    variant?: 'default' | 'destructive';
    disabled?: boolean;
    hidden?: boolean;
}

interface ActionDropdownProps {
    actions: ActionItem[];
    triggerClassName?: string;
}

export default function ActionDropdown({
    actions,
    triggerClassName,
}: ActionDropdownProps) {
    const visibleActions = actions.filter((action) => !action.hidden);

    if (visibleActions.length === 0) {
        return null;
    }

    // Group actions: regular actions first, then destructive actions
    const regularActions = visibleActions.filter(
        (a) => a.variant !== 'destructive'
    );
    const destructiveActions = visibleActions.filter(
        (a) => a.variant === 'destructive'
    );

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className={triggerClassName}
                >
                    <MoreHorizontal className="h-4 w-4" />
                    <span className="sr-only">Open menu</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
                {regularActions.map((action, index) => (
                    <ActionMenuItem key={index} action={action} />
                ))}
                {destructiveActions.length > 0 && regularActions.length > 0 && (
                    <DropdownMenuSeparator />
                )}
                {destructiveActions.map((action, index) => (
                    <ActionMenuItem key={`destructive-${index}`} action={action} />
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function ActionMenuItem({ action }: { action: ActionItem }) {
    const Icon = action.icon;

    const content = (
        <>
            {Icon && <Icon className="mr-2 h-4 w-4" />}
            {action.label}
        </>
    );

    const className =
        action.variant === 'destructive'
            ? 'text-destructive focus:text-destructive cursor-pointer'
            : 'cursor-pointer';

    if (action.href && action.external) {
        return (
            <DropdownMenuItem asChild disabled={action.disabled}>
                <a href={action.href} className={className} target={action.target} rel={action.target === '_blank' ? 'noopener noreferrer' : undefined}>
                    {content}
                </a>
            </DropdownMenuItem>
        );
    }

    if (action.href) {
        return (
            <DropdownMenuItem asChild disabled={action.disabled}>
                <Link href={action.href} className={className}>
                    {content}
                </Link>
            </DropdownMenuItem>
        );
    }

    return (
        <DropdownMenuItem
            onClick={action.onClick}
            disabled={action.disabled}
            className={className}
        >
            {content}
        </DropdownMenuItem>
    );
}
