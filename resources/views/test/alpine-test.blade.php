<!DOCTYPE html>
<html>
<head>
    <title>Alpine Test</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
        .box { margin-top: 20px; padding: 20px; background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Alpine.js Test</h1>

    <div class="box" x-data="{ count: 0 }">
        <h2>Test 1: Simple Counter</h2>
        <button @click="count = count + 1">Click me!</button>
        <p>Count: <span x-text="count"></span></p>
    </div>

    <div class="box" x-data="{ open: false }">
        <h2>Test 2: Toggle</h2>
        <button @click="open = !open">Toggle: <span x-text="open ? 'ON' : 'OFF'"></span></button>
    </div>

    <div class="box">
        <h2>Test 3: Plain JavaScript</h2>
        <button onclick="document.getElementById('result').textContent = 'JavaScript works!'">Click me</button>
        <p id="result">Result: (waiting...)</p>
    </div>

    <script>
        console.log('=== ALPINE TEST START ===');
        console.log('Alpine loaded:', !!window.Alpine);
        console.log('Alpine version:', window.Alpine?.version);

        // Wait for Alpine to initialize
        if (window.Alpine) {
            window.Alpine.onElsewhereMutate = () => console.log('[Alpine] DOM mutation detected');
            console.log('[Alpine] Alpine is ready');
        }

        // Test if DOM elements are found
        setTimeout(() => {
            const xDataElements = document.querySelectorAll('[x-data]');
            console.log('Elements with x-data found:', xDataElements.length);
            xDataElements.forEach((el, i) => {
                console.log(`  [${i}] x-data="${el.getAttribute('x-data')}"`);
            });
        }, 500);
    </script>

    <!-- Load Alpine LAST, after HTML is ready -->
    <script>
        console.log('About to load Alpine from CDN...');
    </script>
    <script src="https://unpkg.com/alpinejs@3.15.1/dist/cdn.min.js"></script>
    <script>
        console.log('After Alpine script tag');
        console.log('Alpine in window:', !!window.Alpine);
        setTimeout(() => {
            console.log('Alpine after timeout:', !!window.Alpine);
            if (window.Alpine) {
                console.log('Alpine.start() called');
                window.Alpine.start();
            }
        }, 100);
    </script>
</body>
</html>
