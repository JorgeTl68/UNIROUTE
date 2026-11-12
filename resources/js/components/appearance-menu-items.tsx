import { Monitor, Moon, Sun } from 'lucide-react';
import {
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
} from '@/components/ui/dropdown-menu';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';

const options: { value: Appearance; label: string; icon: typeof Moon }[] = [
    { value: 'dark', label: 'Oscuro', icon: Moon },
    { value: 'light', label: 'Claro', icon: Sun },
    { value: 'system', label: 'Como el sistema', icon: Monitor },
];

/** Selector de tema para los menús de cuenta (header y Modo conductor). */
export function AppearanceMenuItems() {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <>
            <DropdownMenuLabel className="text-xs font-semibold text-muted-foreground">
                Tema
            </DropdownMenuLabel>
            <DropdownMenuRadioGroup
                value={appearance}
                onValueChange={(value) => {
                    const option = options.find((item) => item.value === value);

                    if (option) updateAppearance(option.value);
                }}
            >
                {options.map(({ value, label, icon: Icon }) => (
                    <DropdownMenuRadioItem
                        key={value}
                        value={value}
                        // Cambiar de tema no cierra el menú: se ve el resultado.
                        onSelect={(event) => event.preventDefault()}
                    >
                        <Icon className="size-4" />
                        {label}
                    </DropdownMenuRadioItem>
                ))}
            </DropdownMenuRadioGroup>
        </>
    );
}
