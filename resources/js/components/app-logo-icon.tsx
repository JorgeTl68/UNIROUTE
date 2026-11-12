
import type { SVGAttributes } from 'react';

export default function AppLogoIcon({
    className,
    ...props
}: SVGAttributes<SVGSVGElement>) {
    return (
        <svg
            {...props}
            className={className}
            viewBox="0 0 40 40"
            aria-label="UniRoute"
            role="img"
        >
            <image
                href="/branding/isotipo-oscuro.svg"
                width="40"
                height="40"
                preserveAspectRatio="xMidYMid meet"
            />
        </svg>
    );
}
