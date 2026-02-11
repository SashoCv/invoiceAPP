interface FynvoLogoProps {
    size?: number;
    className?: string;
    showText?: boolean;
    textClassName?: string;
}

export function FynvoIcon({ size = 32, className }: { size?: number; className?: string }) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 40 40"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={className}
        >
            <rect width="40" height="40" rx="10" fill="currentColor" />
            {/* Stylized "F" with forward-leaning dynamic feel */}
            <path
                d="M13 9.5h15.5v5H19l-0.8 5H27v5H17.7L16.5 32H11l2.5-17.5H11z"
                fill="white"
                opacity="0.95"
            />
            {/* Accent dot — fintech precision */}
            <circle cx="30.5" cy="30.5" r="2.5" fill="white" opacity="0.5" />
        </svg>
    );
}

export default function FynvoLogo({ size = 32, className, showText = true, textClassName }: FynvoLogoProps) {
    return (
        <span className="flex items-center gap-2.5">
            <FynvoIcon size={size} className={className} />
            {showText && (
                <span className={textClassName}>
                    <span className="font-bold tracking-tight">Fyn</span>
                    <span className="font-light tracking-tight">vo</span>
                </span>
            )}
        </span>
    );
}
