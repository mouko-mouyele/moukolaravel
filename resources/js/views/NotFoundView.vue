<template>
  <div class="not-found">
    <h1>404</h1>
    <p>Page introuvable</p>
    <router-link to="/" class="btn btn-primary">Retour au tableau de bord</router-link>
  </div>
</template>

<style scoped>
.not-found { text-align: center; padding: 4rem 2rem; }
.not-found h1 { font-size: 4rem; margin-bottom: 0.5rem; color: var(--text-muted); }
</style>
