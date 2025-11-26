import React from 'react';
import { View, StyleProp, ViewStyle } from 'react-native';
import { SkeletonBox } from './SkeletonBox';

export interface SkeletonTextProps {
    lines?: number;
    lineHeight?: number;
    lastLineWidth?: string | number; // e.g., '70%'
    className?: string;
    style?: StyleProp<ViewStyle>;
    gap?: number;
}

export const SkeletonText: React.FC<SkeletonTextProps> = ({
    lines = 3,
    lineHeight = 16,
    lastLineWidth = '70%',
    className,
    style,
    gap = 8,
}) => {
    return (
        <View style={[{ gap }, style]} className={className}>
            {Array.from({ length: lines }).map((_, index) => {
                const isLast = index === lines - 1;
                const width = isLast ? lastLineWidth : '100%';

                return (
                    <SkeletonBox
                        key={index}
                        width={width as any}
                        height={lineHeight}
                        borderRadius={4}
                    />
                );
            })}
        </View>
    );
};
