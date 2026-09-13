<template>
  <div class="waspi-dashboard">
    <header class="header">
      <h1>🏆 WASPI REWARDS</h1>
      <p>Gestion du programme de fidélité et des récompenses</p>
    </header>

    <!-- Barre de filtres -->
    <section class="filters-bar">
      <div class="filter-group">
        <label for="badge-filter">Filtrer par Badge :</label>
        <select id="badge-filter" v-model="selectedBadge" @change="fetchUsers">
          <option value="">Tous les badges</option>
          <option value="beginner-badge">beginner-badge</option>
          <option value="beginner">beginner</option>
          <option value="top-fan">top-fan</option>
          <option value="super-fan">super-fan</option>
        </select>
      </div>

      <div class="filter-group">
        <label for="points-filter">Filtrer par Points :</label>
        <input 
          id="points-filter"
          type="number" 
          v-model="selectedPoints" 
          placeholder="Ex: 50" 
          @change="fetchUsers"
        />
      </div>

      <button class="btn-reset" @click="resetFilters">Réinitialiser</button>
    </section>

    <!-- Tableau des utilisateurs -->
    <div class="table-wrapper">
      <table class="users-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Points</th>
            <th>Badge Actuel</th>
            <th>Prochain Badge</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>#{{ user.id }}</td>
            <td class="font-bold">{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td><span class="points-badge">{{ user.points }} pts</span></td>
            <td>
              <span :class="['badge-tag', formatBadgeClass(user.current_badge)]">
                {{ user.current_badge || 'aucun' }}
              </span>
            </td>
            <td>
              <small v-if="user.next_badge_info && user.next_badge_info.next_badge && user.next_badge_info.next_badge !== 'Niveau Max'">
                {{ user.next_badge_info.next_badge }} ({{ user.next_badge_info.points_needed }} pts restants)
              </small>
              <small v-else class="text-success">Niveau Maximum Atteint !</small>
            </td>
            <td>
              <button class="btn-action" @click="openModal(user)">
                Détails & Actions
              </button>
            </td>
          </tr>
          <tr v-if="users.length === 0">
            <td colspan="7" class="empty-state">Aucun utilisateur trouvé avec ces critères.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Fenêtre modale sécurisée -->
    <div v-if="isModalOpen && activeUser" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <header class="modal-header">
          <h3>Détails du profil : {{ activeUser.name }}</h3>
          <button class="close-btn" @click="closeModal">&times;</button>
        </header>

        <div class="modal-body">
          <div class="stats-grid">
            <div class="stat-box">
              <span>Points</span>
              <strong>{{ activeUser.points }}</strong>
            </div>
            <div class="stat-box">
              <span>Commentaires</span>
              <strong>{{ activeUser.comments_count || 0 }}</strong>
            </div>
            <div class="stat-box">
              <span>Likes</span>
              <strong>{{ activeUser.likes_count || 0 }}</strong>
            </div>
          </div>

          <!-- Action 1: Ajouter un commentaire -->
          <div class="action-block">
            <h4>Ajouter un commentaire</h4>
            <textarea 
              v-model="newComment" 
              placeholder="Écrivez un commentaire..." 
              rows="3"
            ></textarea>
            <button class="btn-primary" @click="submitComment" :disabled="!newComment.trim() || submitting">
              {{ submitting ? 'Envoi...' : 'Envoyer le commentaire' }}
            </button>
          </div>

          <!-- Action 2: Liker -->
          <div class="action-block">
            <h4>Simuler une interaction Like</h4>
            <p>Aimer une publication pour créditer l'utilisateur.</p>
            <button class="btn-secondary" @click="submitLike" :disabled="submitting">
              👍 Ajouter un Like
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pop-up de Notification (Toast Flottant) -->
    <transition name="toast-fade">
      <div v-if="toastMessage" :class="['toast-notification', toastType]">
        <span class="toast-icon">{{ toastType === 'success' ? '✅' : '⚠️' }}</span>
        <span class="toast-text">{{ toastMessage }}</span>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

// Instance HTTP dédiée avec en-têtes préconfigurés
const api = axios.create({
  headers: {
    'Accept': 'application/json',
    'Authorization': 'Bearer waspi_secret_token_2026'
  }
});

const users = ref([]);
const selectedBadge = ref('');
const selectedPoints = ref('');
const isModalOpen = ref(false);
const activeUser = ref(null);
const newComment = ref('');
const submitting = ref(false);

// État de la notification pop-up (Toast)
const toastMessage = ref('');
const toastType = ref('success');

const showToast = (message, type = 'success') => {
  toastMessage.value = message;
  toastType.value = type;
  setTimeout(() => {
    toastMessage.value = '';
  }, 3000);
};

const formatBadgeClass = (badge) => {
  if (!badge) return 'none';
  return badge.toLowerCase().replace(/[^a-z0-9]/g, '-');
};

const fetchUsers = async () => {
  try {
    const params = {};
    if (selectedBadge.value) {
      params.type = selectedBadge.value;
    }
    if (selectedPoints.value !== null && selectedPoints.value !== '' && !isNaN(selectedPoints.value)) {
      params.points = selectedPoints.value;
    }

    const response = await api.get('/api/users', { params });
    users.value = response.data.data || [];

    // Mettre à jour les données de l'utilisateur actif si la modale est ouverte
    if (activeUser.value) {
      const updated = users.value.find(u => u.id === activeUser.value.id);
      if (updated) {
        activeUser.value = { ...updated };
      }
    }
  } catch (error) {
    console.error("Erreur lors du chargement des utilisateurs :", error.response?.data || error.message);
  }
};

const resetFilters = () => {
  selectedBadge.value = '';
  selectedPoints.value = '';
  fetchUsers();
};

const openModal = (user) => {
  activeUser.value = { ...user };
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  activeUser.value = null;
  newComment.value = '';
};

const submitComment = async () => {
  if (!newComment.value.trim() || !activeUser.value) return;
  
  submitting.value = true;
  try {
    await api.post('/api/comments', {
      user_id: activeUser.value.id,
      content: newComment.value
    });
    
    newComment.value = '';
    await fetchUsers();
    showToast("Commentaire publié avec succès !", "success");
  } catch (error) {
    showToast("Erreur lors de l'ajout du commentaire", "error");
  } finally {
    submitting.value = false;
  }
};

const submitLike = async () => {
  if (!activeUser.value) return;

  submitting.value = true;
  try {
    await api.post('/api/likes', {
      user_id: activeUser.value.id,
      comment_id: 1
    });

    await fetchUsers();
    showToast("Like enregistré avec succès !", "success");
  } catch (error) {
    showToast("Erreur lors de l'enregistrement du like", "error");
  } finally {
    submitting.value = false;
  }
};

onMounted(fetchUsers);
</script>