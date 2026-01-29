import { cn } from '@/lib/utils';

interface SkeletonProps extends React.HTMLAttributes<HTMLDivElement> {}

function Skeleton({ className, ...props }: SkeletonProps) {
    return (
        <div
            className={cn(
                'animate-pulse rounded-md bg-gray-200',
                className
            )}
            {...props}
        />
    );
}

interface TableSkeletonProps {
    rows?: number;
    columns?: number;
}

function TableSkeleton({ rows = 5, columns = 5 }: TableSkeletonProps) {
    return (
        <div className="w-full">
            {/* Header skeleton */}
            <div className="flex gap-4 p-4 border-b bg-muted/30">
                {Array(columns)
                    .fill(0)
                    .map((_, i) => (
                        <Skeleton
                            key={`header-${i}`}
                            className="h-4"
                            style={{ width: `${Math.random() * 40 + 60}px` }}
                        />
                    ))}
            </div>
            {/* Row skeletons */}
            {Array(rows)
                .fill(0)
                .map((_, rowIndex) => (
                    <div
                        key={`row-${rowIndex}`}
                        className="flex items-center gap-4 p-4 border-b last:border-b-0"
                    >
                        {Array(columns)
                            .fill(0)
                            .map((_, colIndex) => (
                                <Skeleton
                                    key={`cell-${rowIndex}-${colIndex}`}
                                    className="h-4"
                                    style={{ width: `${Math.random() * 60 + 40}px` }}
                                />
                            ))}
                    </div>
                ))}
        </div>
    );
}

interface CardSkeletonProps {
    hasHeader?: boolean;
    headerHeight?: string;
    contentLines?: number;
}

function CardSkeleton({
    hasHeader = true,
    headerHeight = 'h-6',
    contentLines = 3,
}: CardSkeletonProps) {
    return (
        <div className="rounded-xl border bg-card p-6 space-y-4">
            {hasHeader && (
                <div className="space-y-2">
                    <Skeleton className={cn('w-32', headerHeight)} />
                    <Skeleton className="h-4 w-48" />
                </div>
            )}
            <div className="space-y-3">
                {Array(contentLines)
                    .fill(0)
                    .map((_, i) => (
                        <Skeleton
                            key={i}
                            className="h-4"
                            style={{ width: `${Math.random() * 30 + 70}%` }}
                        />
                    ))}
            </div>
        </div>
    );
}

interface StatCardSkeletonProps {
    className?: string;
}

function StatCardSkeleton({ className }: StatCardSkeletonProps) {
    return (
        <div
            className={cn(
                'relative overflow-hidden rounded-2xl bg-gray-100 p-6',
                className
            )}
        >
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <div className="space-y-2">
                        <Skeleton className="h-4 w-24 bg-gray-200" />
                        <Skeleton className="h-8 w-16 bg-gray-200" />
                    </div>
                    <Skeleton className="h-14 w-14 rounded-xl bg-gray-200" />
                </div>
                <Skeleton className="h-4 w-32 bg-gray-200" />
            </div>
        </div>
    );
}

export { Skeleton, TableSkeleton, CardSkeleton, StatCardSkeleton };
