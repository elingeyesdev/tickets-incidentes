import React from 'react';
import { View, ScrollView } from 'react-native';
import { SkeletonBox } from '../atoms/SkeletonBox';
import { SkeletonText } from '../atoms/SkeletonText';
import { SkeletonCircle } from '../atoms/SkeletonCircle';
import { CardSkeleton } from '../molecules/CardSkeleton';

export const TicketDetailSkeleton = () => {
    return (
        <View className="flex-1 bg-gray-50">
            {/* Header */}
            <View className="bg-white p-4 border-b border-gray-200">
                <View className="flex-row justify-between items-center mb-4">
                    <SkeletonBox width={40} height={40} borderRadius={20} />
                    <SkeletonBox width={100} height={20} />
                    <SkeletonBox width={40} height={40} borderRadius={20} />
                </View>
                <SkeletonBox width={80} height={16} className="mb-2" />
                <SkeletonText lines={2} lineHeight={24} className="mb-2" />
            </View>

            <ScrollView className="flex-1 p-4">
                {/* Properties Card */}
                <View className="bg-white p-4 rounded-xl mb-4 border border-gray-100">
                    <View className="flex-row justify-between mb-4">
                        <View>
                            <SkeletonBox width={60} height={12} className="mb-1" />
                            <SkeletonBox width={80} height={16} />
                        </View>
                        <View>
                            <SkeletonBox width={60} height={12} className="mb-1" />
                            <SkeletonBox width={80} height={16} />
                        </View>
                    </View>
                    <View className="flex-row justify-between">
                        <View>
                            <SkeletonBox width={60} height={12} className="mb-1" />
                            <SkeletonBox width={100} height={16} />
                        </View>
                        <View>
                            <SkeletonBox width={60} height={12} className="mb-1" />
                            <SkeletonBox width={80} height={16} />
                        </View>
                    </View>
                </View>

                {/* Description */}
                <View className="bg-white p-4 rounded-xl mb-4 border border-gray-100">
                    <SkeletonBox width={100} height={18} className="mb-3" />
                    <SkeletonText lines={6} gap={8} />
                </View>

                {/* Chat/Activity Placeholder */}
                <CardSkeleton withHeader={true} withFooter={false} lines={2} />
                <CardSkeleton withHeader={true} withFooter={false} lines={1} />
            </ScrollView>
        </View>
    );
};
