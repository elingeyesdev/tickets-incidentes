/**
 * Sidebar - Componente genérico reutilizable para navegación
 * Usado por todos los roles con diferentes configuraciones
 */

import React from 'react';
import { Link } from '@inertiajs/react';

export interface SidebarItem {
    icon: React.ComponentType<{ className?: string }>;
    href: string;
    label: string;
    badge?: string | number;
}

export interface SidebarSection {
    title?: string;
    items: SidebarItem[];
}

interface SidebarProps {
    sections: SidebarSection[];
    currentPath: string;
}

export const Sidebar: React.FC<SidebarProps> = ({ sections, currentPath }) => {
    const isActive = (href: string) => {
        if (href === '/') return currentPath === '/';
        return currentPath.startsWith(href);
    };

    return (
        <div className="flex flex-col h-full bg-white dark:bg-gray-800">
            {sections.map((section, sectionIndex) => (
                <div key={sectionIndex} className={sectionIndex > 0 ? 'border-t border-gray-200 dark:border-gray-700 pt-4' : ''}>
                    {/* Section Title */}
                    {section.title && (
                        <div className="px-6 mb-3">
                            <h3 className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {section.title}
                            </h3>
                        </div>
                    )}

                    {/* Section Items */}
                    <nav className="px-3 space-y-1">
                        {section.items.map((item) => {
                            const Icon = item.icon;
                            const active = isActive(item.href);

                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={`
                                        flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg 
                                        transition-all duration-200 group
                                        ${active
                                            ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-medium shadow-sm'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        }
                                    `}
                                >
                                    <div className="flex items-center gap-3">
                                        <Icon className={`w-5 h-5 flex-shrink-0 ${active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400'}`} />
                                        <span className="text-sm">{item.label}</span>
                                    </div>

                                    {/* Badge */}
                                    {item.badge && (
                                        <span className={`
                                            text-xs px-2 py-0.5 rounded-full font-medium
                                            ${active
                                                ? 'bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-300'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                                            }
                                        `}>
                                            {item.badge}
                                        </span>
                                    )}
                                </Link>
                            );
                        })}
                    </nav>
                </div>
            ))}
        </div>
    );
};

