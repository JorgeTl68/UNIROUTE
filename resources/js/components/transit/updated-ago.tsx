import { useEffect, useState } from 'react';

/** "Actualizado hace X s" (S3-02); se refresca solo cada segundo. */
export function UpdatedAgo({ since }: { since: number }) {
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        const timer = window.setInterval(() => setNow(Date.now()), 1000);

        return () => window.clearInterval(timer);
    }, []);

    const seconds = Math.max(0, Math.round((now - since) / 1000));

    return (
        <span className="text-muted-foreground" aria-live="off">
            Actualizado hace{' '}
            {seconds < 60 ? `${seconds} s` : `${Math.floor(seconds / 60)} min`}
        </span>
    );
}
