import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Injection automatique de l'access_token réquis par le middleware
const apiToken = 'waspi_secret_token_2026';
window.axios.defaults.headers.common['Authorization'] = `Bearer ${apiToken}`;