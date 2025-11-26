import React from 'react';
import { View, ScrollView } from 'react-native';
import { SkeletonBox } from '../atoms/SkeletonBox';
import { SkeletonCircle } from '../atoms/SkeletonCircle';
import { TicketCardSkeleton } from './TicketCardSkeleton';

export const HomeSkeleton = () => {
    return (
        <View className="flex-1 bg-gray-50">
            <ScrollView contentContainerStyle={{ padding: 24 }}>
                {/* Header */}
                <View className="mb-8 flex-row items-end gap-3">
                    <View>
                        <SkeletonBox width={100} height={20} className="mb-2" />
                        <SkeletonBox width={200} height={36} />
                    </View>
                    <SkeletonCircle size={48} className="ml-auto" />
                </View>

                {/* Quick Actions */}
                <SkeletonBox width={150} height={24} className="mb-4" />
                <View className="flex-row flex-wrap justify-between">
                    {Array.from({ length: 4 }).map((_, index) => (
                        <View
                            key={index}
                            className="w-[48%] bg-white p-4 rounded-xl mb-4 shadow-sm border border-gray-100 items-center"
                        >
                            <SkeletonCircle size={48} className="mb-3" />
                            <SkeletonBox width={80} height={16} />
                        </View>
                    ))}
                </View>

                {/* Recent Activity */}
                <View className="mt-4">
                    <View className="flex-row justify-between items-center mb-4">
                        <SkeletonBox width={150} height={24} />
                        <SkeletonBox width={60} height={16} />
                    </View>

                    {/* Simulate a few ticket cards */}
                    <TicketCardSkeleton />
                    <TicketCardSkeleton />
                </View>
            </ScrollView>
        </View>
    );
};
