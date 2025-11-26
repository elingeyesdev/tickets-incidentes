import { View, Text } from 'react-native';
import { Controller, Control, FieldValues, Path } from 'react-hook-form';
import { TextInput, TextInputProps } from 'react-native-paper';

interface ControlledInputProps<T extends FieldValues> extends TextInputProps {
    control: Control<T>;
    name: Path<T>;
    label: string;
}

export function ControlledInput<T extends FieldValues>({
    control,
    name,
    label,
    ...props
}: ControlledInputProps<T>) {
    return (
        <Controller
            control={control}
            name={name}
            render={({ field: { onChange, onBlur, value }, fieldState: { error } }) => (
                <View className="mb-4">
                    <TextInput
                        label={label}
                        value={value}
                        onBlur={onBlur}
                        onChangeText={onChange}
                        mode="outlined"
                        error={!!error}
                        theme={{ roundness: 10 }}
                        className="bg-white"
                        {...props}
                    />
                    {error && (
                        <Text className="text-red-500 text-sm mt-1 ml-1">
                            {error.message}
                        </Text>
                    )}
                </View>
            )}
        />
    );
}
