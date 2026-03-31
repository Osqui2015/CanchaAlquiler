<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ }>();

const form = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
const avatar = ref<File | null>(null);

function onFileChange(e: Event) {
  const t = e.target as HTMLInputElement;
  if (t.files && t.files.length) avatar.value = t.files[0];
}

function submit() {
  const data = new FormData();
  data.append('_method', 'PUT');
  data.append('name', form.name);
  data.append('email', form.email);
  data.append('phone', form.phone);
  if (form.password) {
    data.append('password', form.password);
    data.append('password_confirmation', form.password_confirmation);
  }
  if (avatar.value) data.append('avatar', avatar.value);

  // endpoint assumed: /panel/cliente/profile
  form.clearErrors();
  form.post('/panel/cliente/profile', { data });
}
</script>

<template>
  <section class="card shadow-sm">
    <h2 class="text-xl font-black text-emerald-600">Configuraciones</h2>
    <p class="text-sm text-slate-600 mt-2">Editá tu perfil, cambiá contraseña y agregá teléfono o foto.</p>

    <div class="mt-4 space-y-3">
      <label class="block">
        <span class="text-sm font-bold">Nombre</span>
        <input v-model="form.name" class="form-field" />
      </label>
      <label class="block">
        <span class="text-sm font-bold">Email</span>
        <input v-model="form.email" type="email" class="form-field" />
      </label>
      <label class="block">
        <span class="text-sm font-bold">Teléfono</span>
        <input v-model="form.phone" class="form-field" />
      </label>
      <label class="block">
        <span class="text-sm font-bold">Avatar</span>
        <input type="file" @change="onFileChange" />
      </label>

      <h3 class="text-sm font-bold mt-2">Cambiar contraseña</h3>
      <label class="block">
        <input v-model="form.password" type="password" placeholder="Nueva contraseña" class="form-field" />
      </label>
      <label class="block">
        <input v-model="form.password_confirmation" type="password" placeholder="Confirmar contraseña" class="form-field" />
      </label>

      <div>
        <button @click.prevent="submit" class="btn-primary">Guardar cambios</button>
      </div>
    </div>
  </section>
</template>
