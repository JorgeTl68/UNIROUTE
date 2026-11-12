import type { Auth } from '@/types/auth';
import type { AppNotification, Campus } from '@/types/transit';

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
            [key: string]: unknown;
        };
    }
}
