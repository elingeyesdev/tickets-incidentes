import React from 'react';
import { View } from 'react-native';
import { SkeletonBox } from '../atoms/SkeletonBox';
import { SkeletonCircle } from '../atoms/SkeletonCircle';

export const CategoryGridSkeleton = () => {
    return (
        <View className="flex-row flex-wrap justify-between">
            {Array.from({ length: 4 }).map((_, index) => (
                <View
                    key={index}
                    className="w-[48%] bg-white p-4 rounded-xl mb-4 shadow-sm border border-gray-100 items-center"
                >
                    <SkeletonCircle size={48} className="mb-3" />
                    <SkeletonBox width={80} height={16} className="mb-2" />
                    <SkeletonBox width={40} height={12} />
                </View>
            ))}
        </View>
    );
};
