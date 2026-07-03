<script setup>
import { ref } from 'vue';
import TrendingVehicles from './components/TrendingVehicles.vue';
import VehicleBrowser from './components/VehicleBrowser.vue';

const trendingRef = ref(null);

function handleVehicleViewed() {
    trendingRef.value?.refresh();
}
</script>

<template>
    <div class="app-shell">
        <header class="app-header">
            <p class="app-eyebrow">PLC hiring test</p>
            <h1>Trending Vehicles</h1>
            <p class="app-lead">
                Click a vehicle to register a page view, then watch the trending list update.
            </p>
        </header>

        <div class="app-grid">
            <VehicleBrowser @vehicle-viewed="handleVehicleViewed" />
            <TrendingVehicles ref="trendingRef" />
        </div>
    </div>
</template>

<style scoped>
.app-shell {
    max-width: 1080px;
    margin: 0 auto;
    padding: 2rem 1.25rem 3rem;
}

.app-header {
    margin-bottom: 2rem;
}

.app-eyebrow {
    margin: 0 0 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.app-header h1 {
    margin: 0;
    font-size: 2rem;
    line-height: 1.2;
}

.app-lead {
    margin: 0.75rem 0 0;
    max-width: 42rem;
    color: #4b5563;
}

.app-grid {
    display: grid;
    gap: 1.5rem;
}

@media (min-width: 900px) {
    .app-grid {
        grid-template-columns: 1.2fr 0.8fr;
        align-items: start;
    }
}
</style>
