import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-react';
import { cn } from '@/lib/utils';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationProps {
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
    className?: string;
}

export default function Pagination({ links, from, to, total, className }: PaginationProps) {
    if (links.length <= 3) return null;

    return (
        <div className={cn('flex items-center justify-between', className)}>
            <p className="text-sm text-muted-foreground">
                Showing <span className="font-medium">{from}</span> to{' '}
                <span className="font-medium">{to}</span> of{' '}
                <span className="font-medium">{total}</span> results
            </p>

            <nav className="flex items-center gap-1">
                {links.map((link, index) => {
                    if (index === 0) {
                        return (
                            <Button
                                key="prev"
                                variant="outline"
                                size="icon"
                                className="h-8 w-8"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
                                {link.url ? (
                                    <Link href={link.url} preserveScroll>
                                        <ChevronLeft className="h-4 w-4" />
                                    </Link>
                                ) : (
                                    <span>
                                        <ChevronLeft className="h-4 w-4" />
                                    </span>
                                )}
                            </Button>
                        );
                    }

                    if (index === links.length - 1) {
                        return (
                            <Button
                                key="next"
                                variant="outline"
                                size="icon"
                                className="h-8 w-8"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
                                {link.url ? (
                                    <Link href={link.url} preserveScroll>
                                        <ChevronRight className="h-4 w-4" />
                                    </Link>
                                ) : (
                                    <span>
                                        <ChevronRight className="h-4 w-4" />
                                    </span>
                                )}
                            </Button>
                        );
                    }

                    if (link.label === '...') {
                        return (
                            <span key={`ellipsis-${index}`} className="px-2">
                                <MoreHorizontal className="h-4 w-4 text-muted-foreground" />
                            </span>
                        );
                    }

                    return (
                        <Button
                            key={link.label}
                            variant={link.active ? 'default' : 'outline'}
                            size="icon"
                            className="h-8 w-8"
                            asChild={!!link.url && !link.active}
                        >
                            {link.url && !link.active ? (
                                <Link href={link.url} preserveScroll>
                                    {link.label}
                                </Link>
                            ) : (
                                <span>{link.label}</span>
                            )}
                        </Button>
                    );
                })}
            </nav>
        </div>
    );
}
