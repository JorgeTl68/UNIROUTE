import { usePage } from '@inertiajs/react';
import AuthTransitLayout from '@/layouts/auth/auth-transit-layout';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

export default function AuthLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
    children: React.ReactNode;
}) {
    const { component } = usePage();
    if (component === 'auth/login' || component === 'auth/register') {
        return (
            <AuthTransitLayout title={title} description={description}>
                {children}
            </AuthTransitLayout>
        );
    }
    return (
        <AuthLayoutTemplate title={title} description={description}>
            {children}
        </AuthLayoutTemplate>
    );
}
