import React from 'react';
import { View } from 'react-native';
import { SkeletonBox } from '../atoms/SkeletonBox';
import { SkeletonCircle } from '../atoms/SkeletonCircle';
import { SkeletonText } from '../atoms/SkeletonText';

export function CompanyCardSkeleton() {
    return (
        <View className="bg-white p-4 rounded-xl mb-3 shadow-sm border border-gray-100">
            <View className="flex-row">
                {/* Avatar */}
                <View className="mr-4">
                    <SkeletonBox width={56} height={56} className="rounded-xl" />
                </View>

                {/* Content */}
                <View className="flex-1 justify-center">
                    <View className="flex-row justify-between items-start mb-1">
                        <SkeletonBox width="70%" height={20} className="mb-1" />
                    </View>

                    <SkeletonBox width="40%" height={14} className="mb-2" />

                    <View className="flex-row items-center">
                        <SkeletonBox width={14} height={14} className="mr-1" />
                        <SkeletonBox width="30%" height={12} className="mr-3" />

                        <SkeletonBox width={14} height={14} className="mr-1" />
                        <SkeletonBox width="25%" height={12} />
                    </View>
                </View>
            </View>
        </View>
    );
}
