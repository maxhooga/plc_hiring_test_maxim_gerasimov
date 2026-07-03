<script setup>
import { onMounted, ref } from 'vue';

const emit = defineEmits(['vehicle-viewed']);

const vehicles = ref([]);
const loading = ref(false);
const error = ref(null);
const selectedVehicle = ref(null);
const viewingId = ref(null);
const actionMessage = ref(null);

async function loadVehicles() {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch('/api/vehicles');

        if (!response.ok) {
            throw new Error(`Failed to load vehicles (${response.status})`);
        }

        vehicles.value = await response.json();
    } catch (loadError) {
        error.value = loadError instanceof Error
            ? loadError.message
            : 'Failed to load vehicles';
        vehicles.value = [];
    } finally {
        loading.value = false;
    }
}

async function viewVehicle(vehicle) {
    viewingId.value = vehicle.id;
    actionMessage.value = null;

    try {
        const response = await fetch(`/api/vehicles/${vehicle.id}`);

        if (!response.ok) {
            throw new Error(`Failed to load vehicle detail (${response.status})`);
        }

        selectedVehicle.value = await response.json();
        actionMessage.value = `View recorded for ${selectedVehicle.value.make} ${selectedVehicle.value.model}.`;
        emit('vehicle-viewed');
    } catch (viewError) {
        actionMessage.value = viewError instanceof Error
            ? viewError.message
            : 'Failed to record vehicle view';
    } finally {
        viewingId.value = null;
    }
}

onMounted(loadVehicles);
</script>

<template>
    <section class="panel">
        <div class="panel__header">
            <h2>Browse vehicles</h2>
            <p>Each click calls <code>GET /api/vehicles/{id}</code> and increments the view counter.</p>
        </div>

        <p v-if="loading" class="state state--loading">Loading vehicles…</p>
        <p v-else-if="error" class="state state--error" role="alert">{{ error }}</p>

        <div v-else class="vehicle-grid">
            <button
                v-for="vehicle in vehicles"
                :key="vehicle.id"
                type="button"
                class="vehicle-card"
                :class="{ 'vehicle-card--active': selectedVehicle?.id === vehicle.id }"
                :disabled="viewingId === vehicle.id"
                @click="viewVehicle(vehicle)"
            >
                <span class="vehicle-card__title">{{ vehicle.make }} {{ vehicle.model }}</span>
                <span class="vehicle-card__meta">{{ vehicle.year }} · €{{ vehicle.price.toLocaleString() }}</span>
                <span class="vehicle-card__action">
                    {{ viewingId === vehicle.id ? 'Recording view…' : 'Open detail' }}
                </span>
            </button>
        </div>

        <div v-if="selectedVehicle" class="vehicle-detail">
            <p class="vehicle-detail__label">Last opened vehicle</p>
            <h3>{{ selectedVehicle.make }} {{ selectedVehicle.model }}</h3>
            <dl>
                <div>
                    <dt>Year</dt>
                    <dd>{{ selectedVehicle.year }}</dd>
                </div>
                <div>
                    <dt>Price</dt>
                    <dd>€{{ selectedVehicle.price.toLocaleString() }}</dd>
                </div>
                <div>
                    <dt>ID</dt>
                    <dd>#{{ selectedVehicle.id }}</dd>
                </div>
            </dl>
        </div>

        <p v-if="actionMessage" class="action-message">{{ actionMessage }}</p>
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
}

.state--error {
    color: #b91c1c;
}

.vehicle-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    margin-top: 1rem;
}

.vehicle-card {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    padding: 0.9rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    background: #f9fafb;
    color: inherit;
    cursor: pointer;
    text-align: left;
    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
}

.vehicle-card:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: #2563eb;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.12);
}

.vehicle-card--active {
    border-color: #2563eb;
    background: #eff6ff;
}

.vehicle-card:disabled {
    cursor: wait;
    opacity: 0.75;
}

.vehicle-card__title {
    font-weight: 700;
}

.vehicle-card__meta,
.vehicle-card__action {
    font-size: 0.875rem;
    color: #4b5563;
}

.vehicle-card__action {
    color: #2563eb;
    font-weight: 600;
}

.vehicle-detail {
    margin-top: 1.25rem;
    padding: 1rem;
    border-radius: 12px;
    background: #f3f4f6;
}

.vehicle-detail__label {
    margin: 0;
    color: #6b7280;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.vehicle-detail h3 {
    margin: 0.35rem 0 0.75rem;
}

.vehicle-detail dl {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin: 0;
}

.vehicle-detail dt {
    margin: 0;
    color: #6b7280;
    font-size: 0.8rem;
}

.vehicle-detail dd {
    margin: 0.15rem 0 0;
    font-weight: 600;
}

.action-message {
    margin: 1rem 0 0;
    color: #047857;
    font-weight: 600;
}
</style>
