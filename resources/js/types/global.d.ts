import type { Auth } from '@/types/auth';
import type { AppNotification, Campus, TransitRoute } from '@/types/transit';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            campuses: Pick<Campus, 'id' | 'name' | 'slug'>[];
            currentCampus: Pick<Campus, 'id' | 'name' | 'slug'> | null;
            liveUnits: number;
            notifications: {
                unread: number;
                latest: AppNotification[];
            } | null;
            /** Solo llega cuando se abre el modal "Reportar emergencia". */
            emergency?: {
                routes: Pick<TransitRoute, 'id' | 'number' | 'name'>[];
                categories: { value: string; label: string }[];
                driver_route_id: number | null;
            };
            [key: string]: unknown;
        };
    }
}
