import React from 'react';
import { DimensionValue, StyleProp, ViewStyle } from 'react-native';
import { BaseSkeleton } from '../base/BaseSkeleton';

export interface SkeletonBoxProps {
    width?: DimensionValue;
    height?: DimensionValue;
    className?: string;
    style?: StyleProp<ViewStyle>;
    borderRadius?: number;
}

export const SkeletonBox: React.FC<SkeletonBoxProps> = ({
    width,
    height,
    className,
    style,
    borderRadius = 4,
}) => {
    return (
        <BaseSkeleton
            style={[
                { width, height, borderRadius },
                style
            ]}
            className={className}
        />
    );
};
