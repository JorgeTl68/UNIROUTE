import { cn } from '@/lib/utils';

/**
 * Logo horizontal compacto. El "claro" (letras azules) va sobre fondos claros
 * y el "oscuro" (letras blancas) sobre fondos oscuros; se cambia con el tema.
 */
export default function AppLogo({
    alt = 'UniRoute · Campus Mobility',
    className = 'h-10 max-w-full object-contain object-left',
}: {
    alt?: string;
    className?: string;
}) {
    return (
        <>
            <img
                src="/branding/logo-horizontal-compacto-claro.svg"
                alt={alt}
                className={cn(className, 'dark:hidden')}
            />
            <img
                src="/branding/logo-horizontal-compacto-oscuro.svg"
                alt={alt}
                className={cn(className, 'hidden dark:block')}
            />
        </>
    );
}
