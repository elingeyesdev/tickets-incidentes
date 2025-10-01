import './bootstrap';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import Home from './Pages/Home.tsx';

const pages = {
    'Home': Home
};

createInertiaApp({
    resolve: (name) => pages[name],
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
});
