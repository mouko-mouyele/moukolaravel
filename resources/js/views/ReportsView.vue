<template>
  <div>
    <header class="page-header">
      <h1>Rapports & exports</h1>
      <p>Flotte, alertes et maintenance — CSV et rapport imprimable PDF</p>
    </header>

    <div class="grid">
      <div class="card export-card">
        <h3>📊 Rapport flotte complet</h3>
        <p>HTML imprimable → « Enregistrer en PDF » dans le navigateur</p>
        <button class="btn btn-primary" @click="openReport">Ouvrir le rapport</button>
      </div>

      <div class="card export-card">
        <h3>🚗 Véhicules (CSV)</h3>
        <p>Excel / LibreOffice — séparateur point-virgule</p>
        <button class="btn btn-secondary" @click="download('vehicles.csv')">Télécharger CSV</button>
      </div>

      <div class="card export-card">
        <h3>⚠️ Alertes (CSV)</h3>
        <p>Toutes les alertes avec statut et échéances</p>
        <button class="btn btn-secondary" @click="download('alerts.csv')">Télécharger CSV</button>
      </div>

      <div class="card export-card">
        <h3>🔧 Maintenances (CSV)</h3>
        <p>Interventions, coûts, certification blockchain</p>
        <button class="btn btn-secondary" @click="download('maintenances.csv')">Télécharger CSV</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import api from '../api';

function authHeaders() {
  const token = localStorage.getItem('autochain_token');
  return token ? { Authorization: `Bearer ${token}` } : {};
}

async function download(file) {
  const map = {
    'vehicles.csv': '/exports/vehicles.csv',
    'alerts.csv': '/exports/alerts.csv',
    'maintenances.csv': '/exports/maintenances.csv',
  };
  const res = await fetch(`/api/v1${map[file]}`, { headers: authHeaders() });
  const blob = await res.blob();
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = file;
  a.click();
  URL.revokeObjectURL(url);
}

async function openReport() {
  const res = await fetch('/api/v1/exports/fleet-report', { headers: authHeaders() });
  const html = await res.text();
  const w = window.open('', '_blank');
  w.document.write(html);
  w.document.close();
}
</script>

<style scoped>
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; }
.export-card h3 { margin-bottom: 0.5rem; }
.export-card p { color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem; min-height: 2.5rem; }
</style>
