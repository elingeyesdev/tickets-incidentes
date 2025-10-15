/**
 * Welcome Page - Página de Bienvenida Pública
 * Diseño profesional migrado desde mockup con mejoras arquitectónicas
 */

import { Link } from '@inertiajs/react';
import { PublicLayout } from '@/Layouts/Public/PublicLayout';
import { Card, Button, Badge } from '@/Components/ui';
import { Headphones, Shield, Zap, Users, ArrowRight, CheckCircle } from 'lucide-react';
import { useLocale } from '@/contexts';

function WelcomeContent() {
    const { t } = useLocale();

    return (
        <>
            {/* Hero Section */}
            <section className="py-20">
                <div className="container mx-auto px-4 text-center">
                    <Badge className="mb-4 bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50">
                        {t('welcome.badge')}
                    </Badge>
                    <h2 className="text-5xl font-bold text-gray-900 dark:text-white mb-6 text-balance">
                        {t('welcome.hero.title')}{' '}
                        <span className="text-blue-600 dark:text-blue-400">{t('welcome.hero.title_highlight')}</span>
                    </h2>
                    <p className="text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto text-pretty">
                        {t('welcome.hero.subtitle')}
                    </p>
                    <div className="flex items-center justify-center gap-4 flex-wrap">
                        <Link href="/solicitud-empresa">
                            <Button size="lg" className="bg-blue-600 hover:bg-blue-700">
                                {t('welcome.hero.btn_company')}
                                <ArrowRight className="w-4 h-4 ml-2" />
                            </Button>
                        </Link>
                        <Link href="/login">
                            <Button size="lg" variant="outline">
                                {t('welcome.hero.btn_login')}
                            </Button>
                        </Link>
                        <Link href="/register-user">
                            <Button size="lg" variant="secondary">
                                {t('welcome.hero.btn_user')}
                            </Button>
                        </Link>
                    </div>
                </div>
            </section>

            {/* Features Section */}
            <section className="py-16 bg-white dark:bg-gray-800">
                <div className="container mx-auto px-4">
                    <div className="text-center mb-12">
                        <h3 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                            {t('welcome.features.title')}
                        </h3>
                        <p className="text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                            {t('welcome.features.subtitle')}
                        </p>
                    </div>

                    <div className="grid md:grid-cols-3 gap-8">
                        {/* Feature 1: Gestión Segura */}
                        <Card className="border-0 shadow-lg dark:bg-gray-700">
                            <div className="p-6">
                                <div className="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mb-4">
                                    <Shield className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <h4 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    {t('welcome.features.security.title')}
                                </h4>
                                <p className="text-gray-600 dark:text-gray-300">
                                    {t('welcome.features.security.description')}
                                </p>
                            </div>
                        </Card>

                        {/* Feature 2: Respuesta Rápida */}
                        <Card className="border-0 shadow-lg dark:bg-gray-700">
                            <div className="p-6">
                                <div className="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mb-4">
                                    <Zap className="w-6 h-6 text-green-600 dark:text-green-400" />
                                </div>
                                <h4 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    {t('welcome.features.speed.title')}
                                </h4>
                                <p className="text-gray-600 dark:text-gray-300">
                                    {t('welcome.features.speed.description')}
                                </p>
                            </div>
                        </Card>

                        {/* Feature 3: Multi-empresa */}
                        <Card className="border-0 shadow-lg dark:bg-gray-700">
                            <div className="p-6">
                                <div className="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mb-4">
                                    <Users className="w-6 h-6 text-purple-600 dark:text-purple-400" />
                                </div>
                                <h4 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    {t('welcome.features.multi.title')}
                                </h4>
                                <p className="text-gray-600 dark:text-gray-300">
                                    {t('welcome.features.multi.description')}
                                </p>
                            </div>
                        </Card>
                    </div>
                </div>
            </section>

            {/* Benefits Section */}
            <section className="py-16 bg-gray-50 dark:bg-gray-900">
                <div className="container mx-auto px-4">
                    <div className="grid lg:grid-cols-2 gap-12 items-center">
                        <div>
                            <h3 className="text-3xl font-bold text-gray-900 dark:text-white mb-6">
                                {t('welcome.benefits.title')}
                            </h3>
                            <div className="space-y-4">
                                <div className="flex items-start gap-3">
                                    <CheckCircle className="w-5 h-5 text-green-500 dark:text-green-400 mt-1 flex-shrink-0" />
                                    <div>
                                        <h4 className="font-semibold text-gray-900 dark:text-white">
                                            {t('welcome.benefits.tickets.title')}
                                        </h4>
                                        <p className="text-gray-600 dark:text-gray-300">
                                            {t('welcome.benefits.tickets.description')}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <CheckCircle className="w-5 h-5 text-green-500 dark:text-green-400 mt-1 flex-shrink-0" />
                                    <div>
                                        <h4 className="font-semibold text-gray-900 dark:text-white">
                                            {t('welcome.benefits.tracking.title')}
                                        </h4>
                                        <p className="text-gray-600 dark:text-gray-300">
                                            {t('welcome.benefits.tracking.description')}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <CheckCircle className="w-5 h-5 text-green-500 dark:text-green-400 mt-1 flex-shrink-0" />
                                    <div>
                                        <h4 className="font-semibold text-gray-900 dark:text-white">
                                            {t('welcome.benefits.scalability.title')}
                                        </h4>
                                        <p className="text-gray-600 dark:text-gray-300">
                                            {t('welcome.benefits.scalability.description')}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl">
                            <div className="text-center">
                                <div className="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <Headphones className="w-8 h-8 text-white" />
                                </div>
                                <h4 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                    {t('welcome.cta.title')}
                                </h4>
                                <p className="text-gray-600 dark:text-gray-300 mb-6">
                                    {t('welcome.cta.subtitle')}
                                </p>
                                <div className="space-y-3">
                                    <Link href="/solicitud-empresa">
                                        <Button className="w-full bg-blue-600 hover:bg-blue-700">
                                            {t('welcome.cta.btn_register')}
                                        </Button>
                                    </Link>
                                    <Link href="/login">
                                        <Button variant="outline" className="w-full bg-transparent">
                                            {t('welcome.cta.btn_login')}
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}

export default function Welcome() {
    return (
        <PublicLayout title="Bienvenido">
            <WelcomeContent />
        </PublicLayout>
    );
}
