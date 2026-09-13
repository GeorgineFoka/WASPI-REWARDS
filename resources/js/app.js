import '../css/app.css'; // 👈 AJOUTER CETTE LIGNE EN HAUT
import './bootstrap';
import { createApp } from 'vue';
import UserList from './components/UserList.vue';

const app = createApp({});

// Enregistrement du composant
app.component('user-list', UserList);

// Montage de l'application sur l'élément #app
app.mount('#app');