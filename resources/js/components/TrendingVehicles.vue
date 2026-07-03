<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const vehicles = ref([]);
const loading = ref(false);
const error = ref(null);

let refreshIntervalId = null;

async function load({ showLoading = false } = {}) {
    if (showLoading) {
        loading.value = true;
    }

    error.value = null;

    try {
        const response = await fetch('/api/vehicles/trending');

        if (!response.ok) {
            throw new Error(`Failed to load trending vehicles (${response.status})`);
        }

        vehicles.value = await response.json();
    } catch (loadError) {
        error.value = loadError instanceof Error
            ? loadError.message
            : 'Failed to load trending vehicles';
        vehicles.value = [];
    } finally {
        if (showLoading) {
            loading.value = false;
        }
    }
}

function refresh() {
    return load();
}

onMounted(() => {
    load({ showLoading: true });
    refreshIntervalId = window.setInterval(() => load(), 30_000);
});

onUnmounted(() => {
    if (refreshIntervalId !== null) {
        window.clearInterval(refreshIntervalId);
    }
});

defineExpose({ refresh });
</script>

<template>
    <section class="panel">
        <div class="panel__header">
            <h2>Trending vehicles</h2>
            <p>Top 10 in the last 24 hours. Auto-refreshes every 30 seconds.</p>
        </div>

        <p v-if="loading" class="state state--loading">Loading trending vehicles…</p>

        <p v-else-if="error" class="state state--error" role="alert">{{ error }}</p>

        <ol v-else-if="vehicles.length" class="trending-list">
            <li
                v-for="(vehicle, index) in vehicles"
                :key="vehicle.id"
                class="trending-item"
            >
                <span class="trending-item__rank">#{{ index + 1 }}</span>
                <div class="trending-item__body">
                    <strong>{{ vehicle.make }} {{ vehicle.model }}</strong>
                    <span>{{ vehicle.year }} · €{{ vehicle.price.toLocaleString() }}</span>
                </div>
                <span class="trending-item__views">{{ vehicle.view_count }} views</span>
            </li>
        </ol>

        <p v-else class="state">No trending vehicles yet. Click a few vehicles on the left.</p>
    </section>
</template>

<style scoped>
.panel {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    padding: 1.25rem;
}

.panel__header h2 {
    margin: 0;
    font-size: 1.25rem;
}

.panel__header p {
    margin: 0.5rem 0 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.state {
    margin: 1rem 0 0;
    color: #6b7280;
}

.state--error {
    color: #b91c1c;
}

.trending-list {
    list-style: none;
    margin: 1rem 0 0;
    padding: 0;
}

.trending-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 0.75rem;
    align-items: center;
    padding: 0.85rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.trending-item:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.trending-item__rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 700;
    font-size: 0.875rem;
}

.trending-item__body {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.trending-item__body span {
    color: #6b7280;
    font-size: 0.875rem;
}

.trending-item__views {
    color: #111827;
    font-weight: 700;
    white-space: nowrap;
}
</style>
