import React from 'react';

interface FullscreenLoaderProps {
    message?: string;
}

const FullscreenLoader: React.FC<FullscreenLoaderProps> = ({ message = 'Cargando...' }) => {
    return (
        <div className="fixed inset-0 flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 z-50">
            <div className="text-center">
                <div className="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mb-4"></div>
                <p className="text-lg text-gray-700 dark:text-gray-300 font-medium">
                    {message}
                </p>
            </div>
        </div>
    );
};

export default FullscreenLoader;
