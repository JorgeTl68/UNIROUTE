import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AdminTabs } from '@/components/admin-tabs';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { MobileBottomNav } from '@/components/mobile-bottom-nav';

export default function AppSidebarLayout({
    children,
}: {
    children: ReactNode;
}) {
    const isAdmin = usePage().url.startsWith('/admin');

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="min-w-0 overflow-x-clip">
                <AppSidebarHeader />
                {isAdmin && (
                    <div className="px-4 pt-6 sm:px-6 lg:px-8">
                        <AdminTabs />
                    </div>
                )}
                {children}
                <MobileBottomNav />
            </AppContent>
        </AppShell>
    );
}
