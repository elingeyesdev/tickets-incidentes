import React from 'react';
import { useAuth } from '@/contexts';
import { Button } from '@/Components/ui/Button';
import { SimpleDropdown, SimpleDropdownItem } from '@/Components/ui/SimpleDropdown';
import { ChevronsUpDown, Check } from 'lucide-react';

/**
 * RoleSwitcher Component
 * 
 * A professional dropdown component that allows users with multiple roles
 * to switch between them seamlessly.
 */
export const RoleSwitcher: React.FC = () => {
    const { user, lastSelectedRole, selectRole } = useAuth();

    if (!user || !user.roleContexts || user.roleContexts.length <= 1) {
        return null; // Do not render if user has 1 or 0 roles
    }

    const currentRole = user.roleContexts.find(rc => rc.roleCode === lastSelectedRole);

    const handleSelectRole = (roleCode: string) => {
        if (roleCode !== lastSelectedRole) {
            selectRole(roleCode);
        }
    };

    const trigger = (
        <Button
            variant="outline"
            className="flex items-center justify-between w-full sm:w-auto md:min-w-[180px]"
        >
            <span className="truncate">
                {currentRole ? `Rol: ${currentRole.roleName}` : 'Seleccionar rol'}
            </span>
            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
    );

    return (
        <SimpleDropdown trigger={trigger}>
            <div className="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">Cambiar de rol</div>
            <hr className="border-gray-200 dark:border-gray-700"/>
            {user.roleContexts.map(roleContext => (
                <SimpleDropdownItem key={roleContext.roleCode} onClick={() => handleSelectRole(roleContext.roleCode)}>
                    <Check
                        className={`mr-2 h-4 w-4 ${lastSelectedRole === roleContext.roleCode ? 'opacity-100' : 'opacity-0'}`}
                    />
                    <span>{roleContext.roleName}</span>
                </SimpleDropdownItem>
            ))}
        </SimpleDropdown>
    );
};