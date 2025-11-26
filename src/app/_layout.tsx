import "../global.css";
import { Slot } from "expo-router";
import { View } from "react-native";
import { StatusBar } from "expo-status-bar";
import { SafeAreaProvider } from "react-native-safe-area-context";

import { PaperProvider } from 'react-native-paper';

export default function RootLayout() {
    return (
        <SafeAreaProvider>
            <PaperProvider>
                <StatusBar style="auto" />
                <Slot />
            </PaperProvider>
        </SafeAreaProvider>
    );
}
