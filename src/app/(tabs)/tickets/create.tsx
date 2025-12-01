import { View, ScrollView, Text, Alert, TouchableOpacity, Image, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import { Button, ProgressBar, Card, Avatar, IconButton, TextInput, HelperText, Chip, ActivityIndicator } from 'react-native-paper';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useState, useEffect, useRef } from 'react';
import { useCompanyStore } from '@/stores/companyStore';
import { useTicketStore } from '@/stores/ticketStore';
import { ControlledInput } from '@/components/ui/ControlledInput';
import * as ImagePicker from 'expo-image-picker';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { CompanyExploreItem } from '@/types/company';
import { useDebounceCallback } from '@/hooks/useDebounceCallback';
import { ScreenContainer } from '@/components/layout/ScreenContainer';
import { ListItemSkeleton, SkeletonBox, SkeletonText } from '@/components/Skeleton';

const createTicketSchema = z.object({
    title: z.string().min(5, 'El título debe tener al menos 5 caracteres'),
    description: z.string().min(20, 'La descripción debe tener al menos 20 caracteres'),
    categoryId: z.string().min(1, 'Debes seleccionar una categoría'),
    areaId: z.string().optional().nullable(),
    priority: z.enum(['low', 'medium', 'high']),
});

type CreateTicketData = z.infer<typeof createTicketSchema>;

type ClassificationStep = 'area' | 'category' | 'priority';

export default function CreateTicketScreen() {
    const router = useRouter();
    const { companies, fetchCompanies, companiesLoading, setFilter, clearFilters } = useCompanyStore();
    const { createTicket, isLoading, categories, fetchCategories, creationStatus, checkCompanyAreasEnabled, fetchAreas } = useTicketStore();

    // Main Phases: 1=Company, 2=Classification, 3=Details
    const [mainPhase, setMainPhase] = useState(1);

    // Classification Sub-steps
    const [classificationQueue, setClassificationQueue] = useState<ClassificationStep[]>([]);
    const [currentClassStepIndex, setCurrentClassStepIndex] = useState(0);

    const [selectedCompanyId, setSelectedCompanyId] = useState<string | null>(null);
    const [attachments, setAttachments] = useState<ImagePicker.ImagePickerAsset[]>([]);

    // Data State
    const [areasEnabled, setAreasEnabled] = useState(false);
    const [areas, setAreas] = useState<any[]>([]);
    const [loadingData, setLoadingData] = useState(false);

    // Submission State
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submissionProgress, setSubmissionProgress] = useState(0);
    const [submissionStatus, setSubmissionStatus] = useState<'creating' | 'uploading' | 'success' | 'error'>('creating');

    const { control, handleSubmit, formState: { errors, isValid }, setValue, watch, trigger } = useForm<CreateTicketData>({
        resolver: zodResolver(createTicketSchema),
        defaultValues: {
            priority: 'medium',
        },
        mode: 'onChange',
    });

    useEffect(() => {
        setFilter('followedByMe', true);
        fetchCompanies();
        return () => clearFilters();
    }, []);

    useEffect(() => {
        const loadCompanyData = async () => {
            if (selectedCompanyId) {
                setLoadingData(true);
                // Parallel fetching
                const [enabled, _] = await Promise.all([
                    checkCompanyAreasEnabled(selectedCompanyId),
                    fetchCategories(selectedCompanyId)
                ]);

                setAreasEnabled(enabled);
                let queue: ClassificationStep[] = [];

                if (enabled) {
                    const companyAreas = await fetchAreas(selectedCompanyId);
                    setAreas(companyAreas);
                    queue = ['area', 'category', 'priority'];
                } else {
                    setAreas([]);
                    setValue('areaId', null);
                    queue = ['category', 'priority'];
                }

                setClassificationQueue(queue);
                setCurrentClassStepIndex(0);
                setLoadingData(false);
            }
        };
        loadCompanyData();
    }, [selectedCompanyId]);

    const handleSelectCompany = useDebounceCallback((companyId: string) => {
        setSelectedCompanyId(companyId);
        setMainPhase(2);
    }, 200);

    const handleNext = async () => {
        if (mainPhase === 2) {
            const currentSubStep = classificationQueue[currentClassStepIndex];
            let valid = false;

            if (currentSubStep === 'area') valid = await trigger('areaId');
            if (currentSubStep === 'category') valid = await trigger('categoryId');
            if (currentSubStep === 'priority') valid = await trigger('priority');

            if (valid) {
                if (currentClassStepIndex < classificationQueue.length - 1) {
                    setCurrentClassStepIndex(prev => prev + 1);
                } else {
                    setMainPhase(3);
                }
            }
        }
    };

    const handleBack = () => {
        if (mainPhase === 3) {
            setMainPhase(2);
        } else if (mainPhase === 2) {
            if (currentClassStepIndex > 0) {
                setCurrentClassStepIndex(prev => prev - 1);
            } else {
                setMainPhase(1);
                setSelectedCompanyId(null);
            }
        } else {
            router.back();
        }
    };

    const pickImage = async () => {
        const result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ImagePicker.MediaTypeOptions.Images,
            allowsMultipleSelection: true,
            quality: 0.8,
        });
        if (!result.canceled) {
            setAttachments([...attachments, ...result.assets]);
        }
    };

    const removeAttachment = (index: number) => {
        const newAttachments = [...attachments];
        newAttachments.splice(index, 1);
        setAttachments(newAttachments);
    };

    // Real Submit Handler
    const handleRealSubmit = async (data: CreateTicketData) => {
        if (!selectedCompanyId) return;
        setIsSubmitting(true);
        setSubmissionStatus('creating');
        setSubmissionProgress(10);

        try {
            // We'll use a timer to simulate progress since we can't get exact upload events from the store easily
            const timer = setInterval(() => {
                setSubmissionProgress(prev => {
                    if (prev >= 90) return 90;
                    return prev + (Math.random() * 10);
                });
            }, 500);

            await createTicket({
                title: data.title,
                description: data.description,
                category_id: data.categoryId,
                area_id: data.areaId || undefined,
                priority: data.priority,
                company_id: selectedCompanyId
            }, attachments);

            clearInterval(timer);
            setSubmissionProgress(100);
            setSubmissionStatus('success');

            setTimeout(() => {
                router.replace('/(tabs)/tickets');
            }, 1500);
        } catch (error) {
            console.error(error);
            setSubmissionStatus('error');
            setTimeout(() => {
                setIsSubmitting(false);
            }, 2000);
        }
    };

    const renderStepCompany = () => (
        <View>
            <Text className="text-xl font-bold text-gray-900 mb-4">Selecciona una Empresa</Text>
            <ScrollView className="max-h-[75vh]" showsVerticalScrollIndicator={false}>
                {companiesLoading ? (
                    <View>
                        {[1, 2, 3, 4].map(i => (
                            <ListItemSkeleton key={i} withAvatar lines={2} className="mb-3" />
                        ))}
                    </View>
                ) : companies.length === 0 ? (
                    <View className="items-center py-8">
                        <Text className="text-gray-500 text-center">No sigues a ninguna empresa aún.</Text>
                        <Button mode="text" onPress={() => router.push('/(tabs)/companies')}>Explorar Empresas</Button>
                    </View>
                ) : (
                    companies.map((company: CompanyExploreItem) => (
                        <TouchableOpacity
                            key={company.id}
                            onPress={() => handleSelectCompany(company.id)}
                            className={`p-4 mb-3 rounded-2xl border-2 flex-row items-center bg-white border-gray-100 shadow-sm active:border-blue-500 active:bg-blue-50`}
                        >
                            {company.logoUrl ? (
                                <Avatar.Image size={48} source={{ uri: company.logoUrl }} />
                            ) : (
                                <Avatar.Text size={48} label={company.name.substring(0, 2)} />
                            )}
                            <View className="ml-4 flex-1">
                                <Text className="font-bold text-gray-900 text-lg">{company.name}</Text>
                                <Text className="text-gray-500 text-sm">{typeof company.industry === 'object' ? company.industry?.name : company.industry}</Text>
                            </View>
                            <MaterialCommunityIcons name="chevron-right" size={24} color="#9ca3af" />
                        </TouchableOpacity>
                    ))
                )}
            </ScrollView>
        </View>
    );

    const renderClassificationStep = () => {
        if (loadingData) {
            return (
                <View>
                    <SkeletonText lines={1} className="w-1/2 mb-2" />
                    <SkeletonText lines={2} className="mb-6" />
                    {[1, 2, 3].map(i => (
                        <SkeletonBox key={i} height={80} width="100%" className="mb-3" borderRadius={12} />
                    ))}
                </View>
            );
        }

        const subStep = classificationQueue[currentClassStepIndex];

        if (subStep === 'area') {
            return (
                <View>
                    <Text className="text-xl font-bold text-gray-900 mb-2">Selecciona el Área</Text>
                    <Text className="text-gray-500 mb-6">¿A qué departamento va dirigido este ticket?</Text>
                    <Controller
                        control={control}
                        name="areaId"
                        render={({ field: { onChange, value } }) => (
                            <View>
                                {areas.map((area: any) => (
                                    <TouchableOpacity
                                        key={area.id}
                                        onPress={() => onChange(area.id)}
                                        className={`p-4 mb-3 rounded-xl border-2 ${value === area.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white'}`}
                                    >
                                        <View className="flex-row justify-between items-center mb-1">
                                            <Text className={`font-bold text-base ${value === area.id ? 'text-blue-900' : 'text-gray-900'}`}>{area.name}</Text>
                                            {value === area.id && <MaterialCommunityIcons name="check-circle" size={20} color="#2563eb" />}
                                        </View>
                                        {area.description && (
                                            <Text className="text-gray-500 text-sm leading-snug">{area.description}</Text>
                                        )}
                                    </TouchableOpacity>
                                ))}
                            </View>
                        )}
                    />
                </View>
            );
        }

        if (subStep === 'category') {
            return (
                <View>
                    <Text className="text-xl font-bold text-gray-900 mb-2">Selecciona la Categoría</Text>
                    <Text className="text-gray-500 mb-6">¿Qué tipo de problema estás experimentando?</Text>
                    <Controller
                        control={control}
                        name="categoryId"
                        render={({ field: { onChange, value } }) => (
                            <View>
                                {categories.map((cat: any) => (
                                    <TouchableOpacity
                                        key={cat.id}
                                        onPress={() => onChange(cat.id)}
                                        className={`p-4 mb-3 rounded-xl border-2 ${value === cat.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white'}`}
                                    >
                                        <View className="flex-row justify-between items-center mb-1">
                                            <Text className={`font-bold text-base ${value === cat.id ? 'text-blue-900' : 'text-gray-900'}`}>{cat.name}</Text>
                                            {value === cat.id && <MaterialCommunityIcons name="check-circle" size={20} color="#2563eb" />}
                                        </View>
                                        {cat.description && (
                                            <Text className="text-gray-500 text-sm leading-snug">{cat.description}</Text>
                                        )}
                                    </TouchableOpacity>
                                ))}
                            </View>
                        )}
                    />
                </View>
            );
        }

        if (subStep === 'priority') {
            return (
                <View>
                    <Text className="text-xl font-bold text-gray-900 mb-2">Nivel de Prioridad</Text>
                    <Text className="text-gray-500 mb-6">¿Qué tan urgente es tu solicitud?</Text>
                    <Controller
                        control={control}
                        name="priority"
                        render={({ field: { onChange, value } }) => (
                            <View>
                                {[
                                    {
                                        value: 'low',
                                        label: 'Baja',
                                        desc: 'Consultas generales o problemas menores que no afectan el trabajo.',
                                        color: 'bg-green-50',
                                        border: 'border-green-200',
                                        activeBorder: 'border-green-500',
                                        icon: 'arrow-down',
                                        text: 'text-green-800'
                                    },
                                    {
                                        value: 'medium',
                                        label: 'Media',
                                        desc: 'Problemas que afectan parcialmente el trabajo pero permiten continuar.',
                                        color: 'bg-yellow-50',
                                        border: 'border-yellow-200',
                                        activeBorder: 'border-yellow-500',
                                        icon: 'minus',
                                        text: 'text-yellow-800'
                                    },
                                    {
                                        value: 'high',
                                        label: 'Alta',
                                        desc: 'Problemas críticos que impiden trabajar o afectan a muchos usuarios.',
                                        color: 'bg-red-50',
                                        border: 'border-red-200',
                                        activeBorder: 'border-red-500',
                                        icon: 'arrow-up',
                                        text: 'text-red-800'
                                    }
                                ].map((p) => (
                                    <TouchableOpacity
                                        key={p.value}
                                        onPress={() => onChange(p.value)}
                                        className={`p-4 mb-3 rounded-xl border-2 ${value === p.value ? p.activeBorder + ' ' + p.color : 'border-gray-200 bg-white'}`}
                                    >
                                        <View className="flex-row items-center mb-2">
                                            <View className={`p-2 rounded-full mr-3 ${value === p.value ? 'bg-white/50' : 'bg-gray-100'}`}>
                                                <MaterialCommunityIcons
                                                    name={p.icon as any}
                                                    size={24}
                                                    color={value === p.value ? (p.value === 'high' ? '#991b1b' : p.value === 'medium' ? '#854d0e' : '#166534') : '#6b7280'}
                                                />
                                            </View>
                                            <Text className={`font-bold text-lg ${value === p.value ? p.text : 'text-gray-900'}`}>
                                                {p.label}
                                            </Text>
                                            {value === p.value && <MaterialCommunityIcons name="check-circle" size={24} color={p.value === 'high' ? '#991b1b' : p.value === 'medium' ? '#854d0e' : '#166534'} className="ml-auto" />}
                                        </View>
                                        <Text className={`text-sm ${value === p.value ? p.text : 'text-gray-500'}`}>
                                            {p.desc}
                                        </Text>
                                    </TouchableOpacity>
                                ))}
                            </View>
                        )}
                    />
                </View>
            );
        }
        return null;
    };

    const renderStepDetails = () => (
        <View>
            <Text className="text-xl font-bold text-gray-900 mb-2">Detalles del Problema</Text>
            <Text className="text-gray-500 mb-6">Describe tu problema y adjunta evidencia si es necesario.</Text>

            <ControlledInput
                control={control}
                name="title"
                label="Asunto"
                placeholder="Ej: Error al iniciar sesión"
                className="mb-4"
            />

            <ControlledInput
                control={control}
                name="description"
                label="Descripción Detallada"
                placeholder="Explica qué estabas haciendo, qué esperabas que pasara y qué pasó realmente..."
                multiline
                numberOfLines={6}
                className="mb-6"
            />

            <Text className="text-gray-800 font-bold mb-3 text-base">Adjuntos (Opcional)</Text>
            <View className="flex-row flex-wrap gap-3 mb-8">
                {attachments.map((file, index) => (
                    <View key={index} className="relative w-24 h-24 rounded-xl overflow-hidden border border-gray-200 shadow-sm">
                        <Image source={{ uri: file.uri }} className="w-full h-full" resizeMode="cover" />
                        <TouchableOpacity
                            onPress={() => removeAttachment(index)}
                            className="absolute top-1 right-1 bg-black/60 rounded-full p-1"
                        >
                            <MaterialCommunityIcons name="close" size={14} color="white" />
                        </TouchableOpacity>
                    </View>
                ))}

                <TouchableOpacity
                    onPress={pickImage}
                    className="w-24 h-24 rounded-xl border-2 border-dashed border-gray-300 items-center justify-center bg-gray-50 active:bg-gray-100"
                >
                    <MaterialCommunityIcons name="camera-plus" size={28} color="#9ca3af" />
                    <Text className="text-xs text-gray-400 mt-1 font-medium">Añadir</Text>
                </TouchableOpacity>
            </View>
        </View>
    );

    const SubmissionOverlay = () => {
        if (!isSubmitting) return null;

        return (
            <View className="absolute inset-0 bg-white/95 z-50 items-center justify-center px-8">
                {submissionStatus === 'success' ? (
                    <View className="items-center">
                        <View className="bg-green-100 p-6 rounded-full mb-6">
                            <MaterialCommunityIcons name="check" size={48} color="#166534" />
                        </View>
                        <Text className="text-2xl font-bold text-gray-900 text-center mb-2">¡Ticket Creado!</Text>
                        <Text className="text-gray-500 text-center">Tu solicitud ha sido registrada correctamente.</Text>
                    </View>
                ) : submissionStatus === 'error' ? (
                    <View className="items-center">
                        <View className="bg-red-100 p-6 rounded-full mb-6">
                            <MaterialCommunityIcons name="alert" size={48} color="#991b1b" />
                        </View>
                        <Text className="text-2xl font-bold text-gray-900 text-center mb-2">Algo salió mal</Text>
                        <Text className="text-gray-500 text-center">No pudimos crear tu ticket. Por favor intenta de nuevo.</Text>
                    </View>
                ) : (
                    <View className="items-center w-full">
                        <ActivityIndicator size="large" color="#2563eb" className="mb-8" />
                        <Text className="text-xl font-bold text-gray-900 mb-2">
                            {submissionStatus === 'creating' ? 'Creando Ticket...' : 'Subiendo Archivos...'}
                        </Text>
                        <Text className="text-gray-500 text-center mb-6">
                            {creationStatus || `${Math.round(submissionProgress)}% Completado`}
                        </Text>
                        <ProgressBar
                            progress={submissionProgress / 100}
                            color="#2563eb"
                            className="h-2 rounded-full w-full bg-gray-100"
                        />
                    </View>
                )}
            </View>
        );
    };

    // Calculate progress for top bar
    const getTopBarProgress = () => {
        if (mainPhase === 1) return 0.33;
        if (mainPhase === 3) return 1.0;

        // Phase 2: Classification
        if (classificationQueue.length === 0) return 0.33;

        const subProgress = (currentClassStepIndex + 1) / classificationQueue.length;
        // Map subProgress (0-1) to the range (0.33 - 0.66)
        return 0.33 + (subProgress * 0.33);
    };

    return (
        <ScreenContainer backgroundColor="white">
            <SubmissionOverlay />

            <View className="p-4 border-b border-gray-100 flex-row items-center justify-between bg-white">
                <TouchableOpacity onPress={handleBack} className="p-2 -ml-2 rounded-full active:bg-gray-100">
                    <MaterialCommunityIcons name="arrow-left" size={24} color="#1f2937" />
                </TouchableOpacity>
                <View className="flex-row gap-1">
                    {/* Visual indicators for 3 main phases */}
                    <View className={`h-1.5 w-8 rounded-full ${mainPhase >= 1 ? 'bg-blue-600' : 'bg-gray-200'}`} />
                    <View className={`h-1.5 w-8 rounded-full ${mainPhase >= 2 ? 'bg-blue-600' : 'bg-gray-200'}`} />
                    <View className={`h-1.5 w-8 rounded-full ${mainPhase >= 3 ? 'bg-blue-600' : 'bg-gray-200'}`} />
                </View>
                <View className="w-8" />
            </View>

            <View className="px-6 py-2">
                <ProgressBar progress={getTopBarProgress()} color="#2563eb" className="h-1 rounded-full bg-gray-100" />
            </View>

            <ScrollView className="flex-1 px-6 py-6" showsVerticalScrollIndicator={false}>
                {mainPhase === 1 && renderStepCompany()}
                {mainPhase === 2 && renderClassificationStep()}
                {mainPhase === 3 && renderStepDetails()}
                <View className="h-20" />
            </ScrollView>

            {/* Footer Button */}
            {!isSubmitting && (
                <View className="p-4 border-t border-gray-100 bg-white shadow-lg">
                    <Button
                        mode="contained"
                        onPress={mainPhase === 3 ? handleSubmit(handleRealSubmit) : handleNext}
                        disabled={(mainPhase === 1 && !selectedCompanyId) || loadingData}
                        className="rounded-xl py-1"
                        labelStyle={{ fontSize: 16, fontWeight: 'bold' }}
                    >
                        {mainPhase === 3 ? 'Enviar Ticket' : 'Continuar'}
                    </Button>
                </View>
            )}
        </ScreenContainer>
    );
}
