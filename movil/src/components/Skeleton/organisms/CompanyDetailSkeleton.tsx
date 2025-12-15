import React from 'react';
import { View, ScrollView } from 'react-native';
import { SkeletonBox } from '../atoms/SkeletonBox';
import { SkeletonCircle } from '../atoms/SkeletonCircle';
import { SkeletonText } from '../atoms/SkeletonText';
import { ScreenHeader } from '@/components/layout/ScreenHeader';

export function CompanyDetailSkeleton() {
    return (
        <View className="flex-1 bg-white">
            <ScreenHeader title="Empresa" showBack={true} />

            <ScrollView className="flex-1" showsVerticalScrollIndicator={false}>
                {/* Header Hero */}
                <View className="items-center pt-8 pb-12 px-4 bg-gray-100">
                    <SkeletonBox width={80} height={80} className="rounded-2xl mb-4" />
                    <SkeletonBox width={200} height={32} className="mb-2" />
                    <SkeletonBox width={120} height={24} className="rounded-full" />
                </View>

                {/* Quick Info Bar */}
                <View className="flex-row justify-center items-center py-4 bg-white border-b border-gray-100 -mt-4 rounded-t-3xl shadow-sm mx-4">
                    <SkeletonBox width={100} height={16} className="mr-6" />
                    <View className="h-4 w-[1px] bg-gray-200 mr-6" />
                    <SkeletonBox width={100} height={16} />
                </View>

                <View className="h-[1px] bg-gray-100 my-4" />

                {/* Follow Action */}
                <View className="px-6 mb-6">
                    <SkeletonBox width="100%" height={48} className="rounded-full" />
                </View>

                {/* Description */}
                <View className="px-6 mb-6">
                    <SkeletonBox width={100} height={24} className="mb-2" />
                    <SkeletonText lines={3} />
                </View>

                <View className="h-2 bg-gray-50" />

                {/* Contact */}
                <View className="p-6">
                    <SkeletonBox width={100} height={24} className="mb-4" />
                    <View className="flex-row items-center mb-4">
                        <SkeletonCircle size={48} className="mr-4" />
                        <View className="flex-1">
                            <SkeletonBox width={150} height={20} className="mb-1" />
                            <SkeletonBox width={100} height={14} />
                        </View>
                    </View>
                    <View className="flex-row items-center">
                        <SkeletonCircle size={48} className="mr-4" />
                        <View className="flex-1">
                            <SkeletonBox width={150} height={20} className="mb-1" />
                            <SkeletonBox width={100} height={14} />
                        </View>
                    </View>
                </View>

                <View className="h-2 bg-gray-50" />

                {/* Business Hours */}
                <View className="p-6">
                    <SkeletonBox width={150} height={24} className="mb-4" />
                    <SkeletonBox width="100%" height={40} className="mb-4 rounded-lg" />
                    {Array.from({ length: 7 }).map((_, i) => (
                        <View key={i} className="flex-row justify-between py-2 border-b border-gray-50">
                            <SkeletonBox width={80} height={16} />
                            <SkeletonBox width={100} height={16} />
                        </View>
                    ))}
                </View>
            </ScrollView>
        </View>
    );
}
