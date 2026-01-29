import * as React from 'react';
import { router } from '@inertiajs/react';
import { TableHead } from '@/Components/ui/table';
import { cn } from '@/lib/utils';
import { ChevronUp, ChevronDown, ChevronsUpDown } from 'lucide-react';

interface SortableTableHeadProps
    extends React.ThHTMLAttributes<HTMLTableCellElement> {
    column: string;
    currentSort?: string | null;
    currentDirection?: 'asc' | 'desc' | null;
    baseUrl?: string;
    preserveState?: boolean;
    preserveScroll?: boolean;
    onSort?: (column: string, direction: 'asc' | 'desc') => void;
}

export default function SortableTableHead({
    column,
    currentSort,
    currentDirection,
    baseUrl,
    preserveState = false,
    preserveScroll = true,
    onSort,
    children,
    className,
    ...props
}: SortableTableHeadProps) {
    const isActive = currentSort === column;
    const nextDirection: 'asc' | 'desc' =
        isActive && currentDirection === 'asc' ? 'desc' : 'asc';

    const handleSort = () => {
        if (onSort) {
            onSort(column, nextDirection);
        } else if (baseUrl) {
            const searchParams = new URLSearchParams(window.location.search);
            searchParams.set('sort', column);
            searchParams.set('dir', nextDirection);
            router.get(
                `${baseUrl}?${searchParams.toString()}`,
                {},
                { preserveState, preserveScroll }
            );
        }
    };

    const isRightAligned = className?.includes('text-right');

    return (
        <TableHead
            className={cn(
                'cursor-pointer select-none hover:bg-muted/50 transition-colors',
                isActive && 'bg-muted/30',
                className
            )}
            onClick={handleSort}
            {...props}
        >
            <div className={cn(
                'flex items-center gap-1.5',
                isRightAligned && 'justify-end'
            )}>
                {children}
                <span className="text-muted-foreground">
                    {isActive ? (
                        currentDirection === 'asc' ? (
                            <ChevronUp className="h-4 w-4" />
                        ) : (
                            <ChevronDown className="h-4 w-4" />
                        )
                    ) : (
                        <ChevronsUpDown className="h-3.5 w-3.5 opacity-50" />
                    )}
                </span>
            </div>
        </TableHead>
    );
}
