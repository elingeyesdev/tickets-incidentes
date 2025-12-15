import React from 'react';
import { View, StyleProp, ViewStyle } from 'react-native';
import { SkeletonBox } from '../atoms/SkeletonBox';

export interface FormSkeletonProps {
    fields?: number;
    withButton?: boolean;
    className?: string;
    style?: StyleProp<ViewStyle>;
}

export const FormSkeleton: React.FC<FormSkeletonProps> = ({
    fields = 3,
    withButton = true,
    className,
    style,
}) => {
    return (
        <View className={`p-4 ${className || ''}`} style={style}>
            {Array.from({ length: fields }).map((_, index) => (
                <View key={index} className="mb-4">
                    {/* Label */}
                    <SkeletonBox width={100} height={14} className="mb-2" />
                    {/* Input */}
                    <SkeletonBox width="100%" height={48} borderRadius={8} />
                </View>
            ))}

            {withButton && (
                <SkeletonBox width="100%" height={48} borderRadius={8} className="mt-4" />
            )}
        </View>
    );
};
