import React from 'react';
import { DimensionValue, StyleProp, ViewStyle } from 'react-native';
import { BaseSkeleton } from '../base/BaseSkeleton';

export interface SkeletonCircleProps {
    size?: number;
    className?: string;
    style?: StyleProp<ViewStyle>;
}

export const SkeletonCircle: React.FC<SkeletonCircleProps> = ({
    size = 40,
    className,
    style,
}) => {
    return (
        <BaseSkeleton
            style={[
                { width: size, height: size, borderRadius: size / 2 },
                style
            ]}
            className={className}
        />
    );
};
