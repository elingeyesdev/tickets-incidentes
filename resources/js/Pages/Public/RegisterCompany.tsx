/**
 * RegisterCompany Page - Multi-Step Form REFINADO
 * 4 pasos: Básica → Negocio → Contacto → Confirmar
 * Header compacto, mejor UX
 */

import React, { FormEvent, useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { PublicRoute } from '@/Components';
import { PublicLayout } from '@/Layouts/Public/PublicLayout';
import { Card, Button, Input } from '@/Components/ui';
import { useNotification } from '@/contexts';
import {
    Building2, Mail, FileText, Globe, Users, MapPin,
    Hash, AlertCircle, CheckCircle2, ArrowRight, ArrowLeft, Check,
    Briefcase, Contact, ShieldCheck, Zap, BarChart3
} from 'lucide-react';

interface CompanyRequestInput {
    companyName: string;
    legalName?: string;
    adminEmail: string;
    businessDescription: string;
    website?: string;
    industryType: string;
    estimatedUsers?: number;
    contactAddress?: string;
    contactCity?: string;
    contactCountry?: string;
    contactPostalCode?: string;
    taxId?: string;
}

const validateCompanyName = (name: string): { valid: boolean; message: string } => {
    if (!name) return { valid: false, message: '' };
    if (name.length < 2) return { valid: false, message: 'Mínimo 2 caracteres' };
    if (name.length > 200) return { valid: false, message: 'Máximo 200 caracteres' };
    return { valid: true, message: '' };
};

const validateEmail = (email: string): { valid: boolean; message: string } => {
    if (!email) return { valid: false, message: '' };
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) return { valid: false, message: 'Email inválido' };
    return { valid: true, message: 'Email válido' };
};

const validateDescription = (desc: string): { valid: boolean; message: string; count: number } => {
    const count = desc.length;
    if (!desc) return { valid: false, message: '', count: 0 };
    if (count < 50) return { valid: false, message: `Faltan ${50 - count} caracteres`, count };
    if (count > 1000) return { valid: false, message: `Excede por ${count - 1000} caracteres`, count };
    return { valid: true, message: `${count}/1000 caracteres`, count };
};

const validateWebsite = (url: string): { valid: boolean; message: string } => {
    if (!url) return { valid: true, message: '' };
    try {
        new URL(url);
        return { valid: true, message: 'URL válida' };
    } catch {
        return { valid: false, message: 'URL inválida (ej: https://example.com)' };
    }
};

const validateIndustry = (industry: string): { valid: boolean; message: string } => {
    if (!industry) return { valid: false, message: '' };
    if (industry.length > 100) return { valid: false, message: 'Máximo 100 caracteres' };
    return { valid: true, message: '' };
};

const INDUSTRIES = [
    'Technology', 'Healthcare', 'Finance', 'Education', 'Retail',
    'Manufacturing', 'Consulting', 'Real Estate', 'Transportation',
    'Hospitality', 'Media', 'Other',
];

const STORAGE_KEY = 'helpdesk_company_request_draft';

const STEPS = [
    { number: 1, title: 'Básica', icon: Building2 },
    { number: 2, title: 'Negocio', icon: Briefcase },
    { number: 3, title: 'Contacto', icon: Contact },
    { number: 4, title: 'Confirmar', icon: ShieldCheck },
];

function RegisterCompanyContent() {
    const { error: showError } = useNotification();

    const [currentStep, setCurrentStep] = useState(1);
    const [isLoadingStep, setIsLoadingStep] = useState(false);
    const totalSteps = 4;
    const loading = false; // No usamos mutation por ahora

    const [formData, setFormData] = useState<CompanyRequestInput>(() => {
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                try {
                    return JSON.parse(saved);
                } catch {
                    return {
                        companyName: '', legalName: '', adminEmail: '',
                        businessDescription: '', website: '', industryType: '',
                        estimatedUsers: undefined, contactAddress: '', contactCity: '',
                        contactCountry: '', contactPostalCode: '', taxId: '',
                    };
                }
            }
        }
        return {
            companyName: '', legalName: '', adminEmail: '',
            businessDescription: '', website: '', industryType: '',
            estimatedUsers: undefined, contactAddress: '', contactCity: '',
            contactCountry: '', contactPostalCode: '', taxId: '',
        };
    });

    const [touched, setTouched] = useState({
        companyName: false, adminEmail: false, businessDescription: false,
        industryType: false, website: false,
    });

    useEffect(() => {
        if (typeof window !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(formData));
        }
    }, [formData]);

    const [validation, setValidation] = useState({
        companyName: { valid: false, message: '' },
        adminEmail: { valid: false, message: '' },
        businessDescription: { valid: false, message: '', count: 0 },
        website: { valid: true, message: '' },
        industryType: { valid: false, message: '' },
    });

    useEffect(() => {
        setValidation({
            companyName: validateCompanyName(formData.companyName),
            adminEmail: validateEmail(formData.adminEmail),
            businessDescription: validateDescription(formData.businessDescription),
            website: validateWebsite(formData.website || ''),
            industryType: validateIndustry(formData.industryType),
        });
    }, [formData]);

    const canGoToNextStep = () => {
        switch (currentStep) {
            case 1: return validation.companyName.valid && validation.adminEmail.valid;
            case 2: return validation.businessDescription.valid && validation.industryType.valid && validation.website.valid;
            case 3: return true;
            case 4: return true;
            default: return false;
        }
    };

    const handleNext = () => {
        if (currentStep === 1) {
            setTouched({ ...touched, companyName: true, adminEmail: true });
        } else if (currentStep === 2) {
            setTouched({ ...touched, businessDescription: true, industryType: true, website: true });
        }

        if (canGoToNextStep()) {
            setIsLoadingStep(true);
            setTimeout(() => {
                setCurrentStep(currentStep + 1);
                setIsLoadingStep(false);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 600); // Animación de pulsos eléctricos
        } else {
            showError('Por favor, completa correctamente los campos requeridos');
        }
    };

    const handlePrevious = () => {
        setIsLoadingStep(true);
        setTimeout(() => {
            setCurrentStep(currentStep - 1);
            setIsLoadingStep(false);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 400);
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        
        // TODO: Implementar envío de solicitud al backend
        // PRÓXIMAMENTE: Esta funcionalidad estará disponible cuando el backend esté listo
        
        try {
            // Guardar en localStorage para debugging
            console.log('📝 Datos del formulario (guardados localmente):', formData);
            
            // Mostrar mensaje informativo tipo "error" (amarillo/warning)
            if (showError) {
                showError('⚠️ Esta funcionalidad estará disponible próximamente. El backend aún está en desarrollo.');
            }
        } catch (error) {
            console.error('Error en handleSubmit:', error);
        }
    };

    return (
        <div className="max-w-4xl mx-auto mt-6 mb-12 px-4">
            {/* Steps Card - Centrados perfectamente */}
            <Card padding="lg" className="mb-6 shadow-lg">
                <div className="flex justify-center">
                    <div className="flex items-center justify-between gap-4" style={{ width: '600px', maxWidth: '100%' }}>
                    {STEPS.map((step, index) => {
                        const Icon = step.icon;
                        const isCompleted = step.number < currentStep;
                        const isCurrent = step.number === currentStep;
                        
                        return (
                            <React.Fragment key={step.number}>
                                <div className="flex flex-col items-center" style={{ minWidth: '80px' }}>
                                    <div
                                        className={`
                                            w-12 h-12 rounded-full flex items-center justify-center 
                                            font-semibold transition-all duration-300 shadow-lg
                                            ${isCompleted 
                                                ? 'bg-green-500 text-white' 
                                                : isCurrent
                                                ? 'bg-blue-600 text-white scale-110'
                                                : 'bg-gray-200 dark:bg-gray-700 text-gray-400'
                                            }
                                        `}
                                    >
                                        {isCompleted ? (
                                            <Check className="w-5 h-5" strokeWidth={3} />
                                        ) : (
                                            <Icon className="w-5 h-5" />
                                        )}
                                    </div>
                                    <span className={`text-xs font-semibold mt-1.5 ${
                                        isCurrent ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'
                                    }`}>
                                        {step.title}
                                    </span>
                                </div>

                                {index < STEPS.length - 1 && (
                                    <div className="flex-1 h-1 -mt-6">
                                        <div className={`h-full rounded-full transition-all duration-500 ${
                                            step.number < currentStep
                                                ? 'bg-green-500'
                                                : 'bg-gray-200 dark:bg-gray-700'
                                        }`} />
                                    </div>
                                )}
                            </React.Fragment>
                        );
                    })}
                    </div>
                </div>
            </Card>

            {/* Form Card - Mismo ancho */}
            <Card padding="lg" className="shadow-xl">
                <form onSubmit={handleSubmit}>
                    {/* Loading Skeleton - Pulsos Eléctricos */}
                    {isLoadingStep && (
                        <div className="space-y-5 animate-pulse">
                            <div className="h-8 bg-gradient-to-r from-blue-200 via-blue-300 to-blue-200 dark:from-blue-800 dark:via-blue-700 dark:to-blue-800 rounded-lg animate-gradient-pulse"></div>
                            <div className="h-12 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 dark:from-gray-700 dark:via-gray-600 dark:to-gray-700 rounded-lg animate-gradient-pulse"></div>
                            <div className="h-12 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 dark:from-gray-700 dark:via-gray-600 dark:to-gray-700 rounded-lg animate-gradient-pulse"></div>
                            <div className="h-12 bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 dark:from-gray-700 dark:via-gray-600 dark:to-gray-700 rounded-lg animate-gradient-pulse"></div>
                        </div>
                    )}

                    {/* STEP 1 */}
                    {!isLoadingStep && currentStep === 1 && (
                        <div className="space-y-5 animate-fadeIn">
                            <div className="mb-5">
                                <h2 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <Building2 className="w-6 h-6 text-blue-600" />
                                    Información Básica
                                </h2>
                                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Datos principales de tu empresa
                                </p>
                            </div>

                            <Input
                                label="Nombre de la Empresa"
                                value={formData.companyName}
                                onChange={(e) => setFormData({ ...formData, companyName: e.target.value })}
                                onBlur={() => setTouched({ ...touched, companyName: true })}
                                required
                                placeholder="Ej: Innovación Digital SRL"
                                leftIcon={<Building2 className="h-5 w-5" />}
                                rightIcon={
                                    touched.companyName && formData.companyName ? (
                                        validation.companyName.valid ? (
                                            <CheckCircle2 className="h-5 w-5 text-green-500" />
                                        ) : (
                                            <AlertCircle className="h-5 w-5 text-red-500" />
                                        )
                                    ) : null
                                }
                                error={touched.companyName && !validation.companyName.valid ? validation.companyName.message : undefined}
                            />

                            <Input
                                label="Email del Administrador"
                                type="email"
                                value={formData.adminEmail}
                                onChange={(e) => setFormData({ ...formData, adminEmail: e.target.value })}
                                onBlur={() => setTouched({ ...touched, adminEmail: true })}
                                required
                                placeholder="admin@tuempresa.com"
                                leftIcon={<Mail className="h-5 w-5" />}
                                rightIcon={
                                    touched.adminEmail && formData.adminEmail ? (
                                        validation.adminEmail.valid ? (
                                            <CheckCircle2 className="h-5 w-5 text-green-500" />
                                        ) : (
                                            <AlertCircle className="h-5 w-5 text-red-500" />
                                        )
                                    ) : null
                                }
                                error={touched.adminEmail && !validation.adminEmail.valid ? validation.adminEmail.message : undefined}
                                helperText="Email del administrador principal"
                            />

                            <Input
                                label="Razón Social (Opcional)"
                                value={formData.legalName}
                                onChange={(e) => setFormData({ ...formData, legalName: e.target.value })}
                                placeholder="Nombre legal completo de la empresa"
                                leftIcon={<FileText className="h-5 w-5" />}
                            />
                        </div>
                    )}

                    {/* STEP 2 */}
                    {!isLoadingStep && currentStep === 2 && (
                        <div className="space-y-5 animate-fadeIn">
                            <div className="mb-5">
                                <h2 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <Briefcase className="w-6 h-6 text-blue-600" />
                                    Descripción del Negocio
                                </h2>
                                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Cuéntanos sobre tu empresa
                                </p>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Descripción del Negocio <span className="text-red-500">*</span>
                                </label>
                                <textarea
                                    value={formData.businessDescription}
                                    onChange={(e) => setFormData({ ...formData, businessDescription: e.target.value })}
                                    onBlur={() => setTouched({ ...touched, businessDescription: true })}
                                    required
                                    rows={5}
                                    placeholder="Describe tu empresa, servicios, experiencia... (Mínimo 50 caracteres)"
                                    className="block w-full rounded-lg border shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 px-4 py-3 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400"
                                />
                                <div className="mt-2 flex justify-between items-center text-sm">
                                    <span className={touched.businessDescription && !validation.businessDescription.valid ? 'text-red-600' : 'text-gray-500'}>
                                        {validation.businessDescription.message || 'Mínimo 50, máximo 1000'}
                                    </span>
                                    <span className={`font-medium ${validation.businessDescription.count >= 50 ? 'text-green-600' : 'text-gray-500'}`}>
                                        {validation.businessDescription.count}/1000
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Tipo de Industria <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={formData.industryType}
                                    onChange={(e) => setFormData({ ...formData, industryType: e.target.value })}
                                    onBlur={() => setTouched({ ...touched, industryType: true })}
                                    required
                                    className="block w-full rounded-lg border shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 px-4 py-3 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                                >
                                    <option value="">Selecciona una industria</option>
                                    {INDUSTRIES.map((industry) => (
                                        <option key={industry} value={industry}>{industry}</option>
                                    ))}
                                </select>
                            </div>

                            <Input
                                label="Sitio Web (Opcional)"
                                type="url"
                                value={formData.website}
                                onChange={(e) => setFormData({ ...formData, website: e.target.value })}
                                onBlur={() => setTouched({ ...touched, website: true })}
                                placeholder="https://tuempresa.com"
                                leftIcon={<Globe className="h-5 w-5" />}
                                rightIcon={
                                    formData.website && touched.website ? (
                                        validation.website.valid ? (
                                            <CheckCircle2 className="h-5 w-5 text-green-500" />
                                        ) : (
                                            <AlertCircle className="h-5 w-5 text-red-500" />
                                        )
                                    ) : null
                                }
                                error={touched.website && !validation.website.valid ? validation.website.message : undefined}
                            />

                            <Input
                                label="Usuarios Estimados (Opcional)"
                                type="number"
                                value={formData.estimatedUsers || ''}
                                onChange={(e) => setFormData({ ...formData, estimatedUsers: e.target.value ? parseInt(e.target.value) : undefined })}
                                placeholder="50"
                                min="1"
                                max="10000"
                                leftIcon={<Users className="h-5 w-5" />}
                            />
                        </div>
                    )}

                    {/* STEP 3 */}
                    {!isLoadingStep && currentStep === 3 && (
                        <div className="space-y-5 animate-fadeIn">
                            <div className="mb-5">
                                <h2 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <Contact className="w-6 h-6 text-blue-600" />
                                    Información de Contacto
                                </h2>
                                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Campos opcionales
                                </p>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Dirección
                                </label>
                                <textarea
                                    value={formData.contactAddress}
                                    onChange={(e) => setFormData({ ...formData, contactAddress: e.target.value })}
                                    rows={2}
                                    placeholder="Calle, número, piso..."
                                    className="block w-full rounded-lg border shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 px-4 py-3 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <Input label="Ciudad" value={formData.contactCity} onChange={(e) => setFormData({ ...formData, contactCity: e.target.value })} placeholder="Cochabamba" leftIcon={<MapPin className="h-5 w-5" />} />
                                <Input label="País" value={formData.contactCountry} onChange={(e) => setFormData({ ...formData, contactCountry: e.target.value })} placeholder="Bolivia" leftIcon={<Globe className="h-5 w-5" />} />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <Input label="Código Postal" value={formData.contactPostalCode} onChange={(e) => setFormData({ ...formData, contactPostalCode: e.target.value })} placeholder="0000" leftIcon={<Hash className="h-5 w-5" />} />
                                <Input label="RUT/NIT" value={formData.taxId} onChange={(e) => setFormData({ ...formData, taxId: e.target.value })} placeholder="987654321" leftIcon={<Hash className="h-5 w-5" />} />
                            </div>
                        </div>
                    )}

                    {/* STEP 4: CONFIRMAR */}
                    {!isLoadingStep && currentStep === 4 && (
                        <div className="space-y-6 animate-fadeIn">
                            <div className="mb-5">
                                <h2 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <ShieldCheck className="w-6 h-6 text-blue-600" />
                                    Confirmar y Enviar
                                </h2>
                                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Revisa tu información antes de enviar
                                </p>
                            </div>

                            {/* Resumen de Datos */}
                            <div className="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                                <h3 className="font-semibold text-gray-900 dark:text-white mb-4">
                                    Resumen de tu Solicitud
                                </h3>
                                <div className="space-y-3 text-sm">
                                    <div className="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                        <span className="text-gray-600 dark:text-gray-400">Empresa:</span>
                                        <span className="font-medium text-gray-900 dark:text-white">{formData.companyName}</span>
                                    </div>
                                    {formData.legalName && (
                                        <div className="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                            <span className="text-gray-600 dark:text-gray-400">Razón Social:</span>
                                            <span className="font-medium text-gray-900 dark:text-white">{formData.legalName}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                        <span className="text-gray-600 dark:text-gray-400">Email:</span>
                                        <span className="font-medium text-gray-900 dark:text-white">{formData.adminEmail}</span>
                                    </div>
                                    <div className="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                        <span className="text-gray-600 dark:text-gray-400">Industria:</span>
                                        <span className="font-medium text-gray-900 dark:text-white">{formData.industryType}</span>
                                    </div>
                                    {formData.website && (
                                        <div className="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                            <span className="text-gray-600 dark:text-gray-400">Sitio Web:</span>
                                            <span className="font-medium text-gray-900 dark:text-white">{formData.website}</span>
                                        </div>
                                    )}
                                    {formData.estimatedUsers && (
                                        <div className="flex justify-between py-2">
                                            <span className="text-gray-600 dark:text-gray-400">Usuarios Estimados:</span>
                                            <span className="font-medium text-gray-900 dark:text-white">{formData.estimatedUsers}</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Beneficios */}
                            <div className="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border-2 border-blue-200 dark:border-blue-800">
                                <h3 className="font-bold text-lg text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <ShieldCheck className="w-6 h-6 text-blue-600" />
                                    Al registrarte obtienes:
                                </h3>
                                <div className="space-y-3">
                                    <div className="flex items-start gap-3">
                                        <CheckCircle2 className="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" />
                                        <div>
                                            <p className="font-semibold text-gray-900 dark:text-white">Acceso completo al sistema de tickets</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Gestiona todas las incidencias de tu empresa en un solo lugar</p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <BarChart3 className="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
                                        <div>
                                            <p className="font-semibold text-gray-900 dark:text-white">Dashboard personalizado</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Métricas y reportes en tiempo real de tu empresa</p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <Zap className="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" />
                                        <div>
                                            <p className="font-semibold text-gray-900 dark:text-white">Respuesta rápida y eficiente</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Clasificación automática por categorías y prioridades</p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3">
                                        <Users className="w-5 h-5 text-purple-600 mt-0.5 flex-shrink-0" />
                                        <div>
                                            <p className="font-semibold text-gray-900 dark:text-white">Gestión de equipo</p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">Invita agentes y administra permisos fácilmente</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Nota legal */}
                            <p className="text-xs text-center text-gray-500 dark:text-gray-400">
                                Al enviar esta solicitud, aceptas que un administrador revisará tu información. 
                                Recibirás una respuesta en tu email en las próximas 24-48 horas.
                            </p>
                        </div>
                    )}

                    {/* Buttons */}
                    <div className="mt-8 flex items-center justify-between gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                        {currentStep > 1 ? (
                            <Button type="button" variant="outline" onClick={handlePrevious} disabled={loading}>
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                Anterior
                            </Button>
                        ) : (
                            <Link href="/" className="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900">
                                ← Cancelar
                            </Link>
                        )}

                        {currentStep < totalSteps ? (
                            <Button type="button" onClick={handleNext} disabled={!canGoToNextStep()} className="ml-auto">
                                Siguiente
                                <ArrowRight className="w-4 h-4 ml-2" />
                            </Button>
                        ) : (
                            <Button type="submit" isLoading={loading} disabled={loading} className="ml-auto bg-green-600 hover:bg-green-700">
                                {loading ? 'Enviando...' : 'Enviar Solicitud'}
                            </Button>
                        )}
                    </div>
                </form>
            </Card>
        </div>
    );
}

export default function RegisterCompany() {
    return (
        <PublicRoute>
            <PublicLayout title="Registrar Mi Empresa">
                <RegisterCompanyContent />
            </PublicLayout>
        </PublicRoute>
    );
}
