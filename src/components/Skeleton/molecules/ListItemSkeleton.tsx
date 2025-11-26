import React from 'react';
import { View, StyleProp, ViewStyle } from 'react-native';
import { SkeletonCircle } from '../atoms/SkeletonCircle';
import { SkeletonText } from '../atoms/SkeletonText';
import { SkeletonBox } from '../atoms/SkeletonBox';

export interface ListItemSkeletonProps {
    withAvatar?: boolean;
    avatarSize?: number;
    lines?: number;
    withRightElement?: boolean;
    className?: string;
    style?: StyleProp<ViewStyle>;
}

export const ListItemSkeleton: React.FC<ListItemSkeletonProps> = ({
    withAvatar = true,
    avatarSize = 40,
    lines = 2,
    withRightElement = false,
    className,
    style,
}) => {
    return (
        <View
            className={`flex-row items-center p-4 bg-white border-b border-gray-100 ${className || ''}`}
            style={style}
        >
            {withAvatar && (
                <SkeletonCircle size={avatarSize} className="mr-3" />
            )}

            <View className="flex-1">
                <SkeletonText
                    lines={lines}
                    lineHeight={lines === 1 ? 16 : 14}
                    gap={6}
                    lastLineWidth="60%"
                />
            </View>

            {withRightElement && (
                <SkeletonBox width={24} height={24} borderRadius={12} className="ml-3" />
            )}
        </View>
    );
};
