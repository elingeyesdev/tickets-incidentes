import React from 'react';
import { View, ScrollView } from 'react-native';
import { SkeletonBox } from '../atoms/SkeletonBox';
import { SkeletonCircle } from '../atoms/SkeletonCircle';
import { ListItemSkeleton } from '../molecules/ListItemSkeleton';

export const ProfileSkeleton = () => {
    return (
        <View className="flex-1 bg-gray-50">
            <ScrollView>
                {/* Header Profile Info */}
                <View className="bg-white p-6 items-center border-b border-gray-200 mb-6">
                    <SkeletonCircle size={80} className="mb-4" />
                    <SkeletonBox width={150} height={24} className="mb-2" />
                    <SkeletonBox width={200} height={16} />
                </View>

                {/* Settings Groups */}
                <View className="bg-white border-y border-gray-200 mb-6">
                    <ListItemSkeleton withAvatar={true} withRightElement={true} />
                    <ListItemSkeleton withAvatar={true} withRightElement={true} />
                    <ListItemSkeleton withAvatar={true} withRightElement={true} />
                </View>

                <View className="bg-white border-y border-gray-200">
                    <ListItemSkeleton withAvatar={true} withRightElement={true} />
                    <ListItemSkeleton withAvatar={true} withRightElement={true} />
                </View>
            </ScrollView>
        </View>
    );
};
