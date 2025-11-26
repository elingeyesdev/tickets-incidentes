import React from 'react';
import { View, ScrollView } from 'react-native';
import { SkeletonBox } from '../atoms/SkeletonBox';
import { SkeletonText } from '../atoms/SkeletonText';
import { SkeletonCircle } from '../atoms/SkeletonCircle';

export const AnnouncementDetailSkeleton = () => {
    return (
        <View className="flex-1 bg-white">
            {/* Header */}
            <View className="p-4 border-b border-gray-100">
                <View className="flex-row justify-between items-center mb-4">
                    <SkeletonCircle size={40} />
                    <SkeletonBox width={150} height={20} />
                    <View style={{ width: 40 }} />
                </View>
            </View>

            <ScrollView className="flex-1 p-5">
                {/* Meta Header */}
                <View className="flex-row justify-between items-center mb-4">
                    <View className="flex-row items-center gap-2">
                        <SkeletonCircle size={20} />
                        <SkeletonBox width={80} height={16} />
                    </View>
                    <SkeletonBox width={100} height={14} />
                </View>

                {/* Title */}
                <SkeletonText lines={2} lineHeight={28} gap={8} className="mb-6" />

                {/* Company */}
                <View className="flex-row items-center mb-6">
                    <SkeletonCircle size={16} className="mr-2" />
                    <SkeletonBox width={120} height={16} />
                </View>

                {/* Content */}
                <SkeletonText lines={4} gap={10} className="mb-6" />
                <SkeletonBox width="100%" height={200} borderRadius={12} className="mb-6" />
                <SkeletonText lines={6} gap={10} />
            </ScrollView>
        </View>
    );
};
