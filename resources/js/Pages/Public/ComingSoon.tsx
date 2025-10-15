/**
 * ComingSoon Page - Página "Próximamente"
 * Para rutas no implementadas aún
 */

import { Link } from '@inertiajs/react';
import { PublicLayout } from '@/Layouts/Public/PublicLayout';
import { Card, Button } from '@/Components/ui';
import { Construction, Home, ArrowLeft } from 'lucide-react';
import { useLocale } from '@/contexts';

function ComingSoonContent() {
    const { t } = useLocale();

    return (
        <div className="min-h-[calc(100vh-20rem)] flex items-center justify-center p-4">
            <Card padding="lg" className="max-w-2xl w-full text-center">
                {/* Icon */}
                <div className="w-24 h-24 mx-auto mb-6 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <Construction className="w-full h-full" />
                </div>

                {/* Title */}
                <h1 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    {t('comingsoon.title')}
                </h1>

                {/* Subtitle */}
                <p className="text-xl text-gray-600 dark:text-gray-300 mb-8">
                    {t('comingsoon.subtitle')}
                </p>

                {/* Description */}
                <p className="text-gray-600 dark:text-gray-400 mb-8 max-w-lg mx-auto">
                    {t('comingsoon.description')}
                </p>

                {/* Actions */}
                <div className="flex items-center justify-center gap-4 flex-wrap">
                    <Link href="/">
                        <Button size="lg" className="bg-blue-600 hover:bg-blue-700">
                            <Home className="w-4 h-4 mr-2" />
                            {t('comingsoon.btn_home')}
                        </Button>
                    </Link>
                    <Button
                        size="lg"
                        variant="outline"
                        onClick={() => window.history.back()}
                    >
                        <ArrowLeft className="w-4 h-4 mr-2" />
                        {t('comingsoon.btn_back')}
                    </Button>
                </div>

                {/* Features Coming */}
                <div className="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                    <p className="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                        {t('comingsoon.features_title')}
                    </p>
                    <div className="grid md:grid-cols-3 gap-4 text-sm">
                        <div className="text-gray-600 dark:text-gray-400">
                            <span className="font-medium">✓</span> {t('comingsoon.feature1')}
                        </div>
                        <div className="text-gray-600 dark:text-gray-400">
                            <span className="font-medium">✓</span> {t('comingsoon.feature2')}
                        </div>
                        <div className="text-gray-600 dark:text-gray-400">
                            <span className="font-medium">✓</span> {t('comingsoon.feature3')}
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    );
}

export default function ComingSoon() {
    return (
        <PublicLayout title="Próximamente">
            <ComingSoonContent />
        </PublicLayout>
    );
}

