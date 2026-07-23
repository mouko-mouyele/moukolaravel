<template>
  <div class="password-field">
    <input
      :type="visible ? 'text' : 'password'"
      class="input"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :autocomplete="autocomplete"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <button
      type="button"
      class="toggle-btn"
      :aria-label="visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
      @click="visible = !visible"
    >
      {{ visible ? '🙈' : '👁' }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  required: { type: Boolean, default: false },
  autocomplete: { type: String, default: 'current-password' },
});

defineEmits(['update:modelValue']);

const visible = ref(false);
</script>

<style scoped>
.password-field {
  position: relative;
  display: flex;
  align-items: center;
}
.password-field .input {
  width: 100%;
  padding-right: 2.75rem;
}
.toggle-btn {
  position: absolute;
  right: 0.5rem;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 1.125rem;
  line-height: 1;
  padding: 0.25rem;
  opacity: 0.7;
}
.toggle-btn:hover { opacity: 1; }
</style>
