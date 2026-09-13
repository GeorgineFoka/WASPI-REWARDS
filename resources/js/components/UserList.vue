<template>
  <div class="max-w-5xl mx-auto p-4 sm:p-6 bg-slate-50 min-h-screen space-y-8">
    <!-- Header -->
    <header>
      <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight">🏆 WASPI REWARDS</h1>
      <p class="text-slate-500 mt-1 text-sm sm:text-base">User management, points, badges, and activity feed</p>
    </header>

    <!-- Alert Modal (Success / Error) -->
    <div v-if="alertModal.visible" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-xl border border-slate-100 text-center space-y-4">
        <div class="text-4xl">
          <span v-if="alertModal.type === 'error'">⚠️</span>
        </div>

        <h3 class="text-lg font-bold" :class="alertModal.type === 'error' ? 'text-red-600' : 'text-emerald-600'">
          {{ alertModal.type === 'error' ? 'Error' : 'Success' }}
        </h3>

        <p class="text-slate-600 text-sm">
          {{ alertModal.message }}
        </p>

        <div class="pt-2">
          <button 
            @click="closeAlertModal" 
            class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-sm transition cursor-pointer"
          >
            OK
          </button>
        </div>
      </div>
    </div>

    <!-- Filter Bar & General Actions -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-col sm:flex-row gap-4 items-center justify-between">
      <div class="flex flex-col sm:flex-row gap-3 items-center w-full sm:w-auto">
        <select 
          v-model="selectedBadge" 
          @change="fetchUsers" 
          class="bg-slate-50 border border-slate-300 text-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2.5 outline-none transition w-full sm:w-auto"
        >
          <option value="">All Badges</option>
          <option value="aucun">No Badge</option>
          <option value="beginner-badge">Beginner Badge (1st com. / 50 pts)</option>
          <option value="beginner">Beginner (10 likes / 500 pts)</option>
          <option value="top-fan">Top Fan (30 com. / 2500 pts)</option>
          <option value="super-fan">Super Fan (50+ com. / 5000 pts)</option>
        </select>

        <input 
          type="number" 
          v-model="minPoints" 
          @input="handlePointsInput" 
          placeholder="Min points e.g.: 100" 
          class="bg-slate-50 border border-slate-300 text-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2.5 outline-none transition w-full sm:w-52"
        />
      </div>

      <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
        <button 
          @click="showCommentsListModal = true" 
          class="w-full sm:w-auto px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-lg transition shadow-sm cursor-pointer flex items-center justify-center gap-1.5"
        >
          <span>📜</span> Comments List
        </button>

        <button 
          @click="openCommentModalForUser(null)" 
          class="w-full sm:w-auto px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-lg transition shadow-sm cursor-pointer flex items-center justify-center gap-1.5"
        >
          <span>💬</span> New Message
        </button>
      </div>
    </div>

    <!-- Users Section (Responsive Cards on Mobile / Table on Desktop) -->
    <section>
      <div v-if="loadingUsers" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        <span class="ml-3 text-slate-600 font-medium">Loading users...</span>
      </div>

      <!-- Mode Desktop : Tableau classique (caché sur mobile) -->
      <div v-else-if="users.length" class="hidden md:block bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-100/80 text-slate-600 text-xs font-semibold uppercase tracking-wider border-b border-slate-200">
              <th class="p-4">User</th>
              <th class="p-4 text-center">Points</th>
              <th class="p-4 text-center">Current Badge</th>
              <th class="p-4">Next Step</th>
              <th class="p-4 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="p-4 font-semibold text-slate-900">{{ user.name }}</td>
              <td class="p-4 text-center">
                <span class="inline-block px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-md border border-emerald-200">
                  {{ user.points }} pts
                </span>
              </td>
              <td class="p-4 text-center">
                <span :class="['px-3 py-1 rounded-full text-xs font-medium border', getBadgeStyle(user.current_badge)]">
                  {{ formatBadge(user.current_badge) }}
                </span>
              </td>
              <td class="p-4 text-xs">
                <template v-if="user.next_badge_info && user.next_badge_info.next_badge && user.next_badge_info.next_badge !== 'Niveau Max' && user.next_badge_info.next_badge !== 'Max Level'">
                  <span class="font-medium text-slate-800">{{ formatBadge(user.next_badge_info.next_badge) }}</span>
                  <span class="text-slate-400 ml-1">({{ user.next_badge_info.points_needed }} pts needed)</span>
                </template>
                <template v-else>
                  <span class="text-emerald-600 font-semibold inline-flex items-center gap-1">
                    Maximum level reached
                  </span>
                </template>
              </td>
              <td class="p-4 text-center">
                <button 
                  @click="openRewardModal(user)" 
                  class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-lg transition shadow-sm cursor-pointer"
                >
                  Credit Points
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mode Mobile : Cartes empilées verticalement (caché sur bureau) -->
      <div v-if="!loadingUsers && users.length" class="md:hidden space-y-4">
        <div v-for="user in users" :key="user.id" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 space-y-3">
          <div class="flex justify-between items-start">
            <div>
              <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">User</span>
              <span class="font-bold text-slate-900 text-base">{{ user.name }}</span>
            </div>
            <div>
              <span class="inline-block px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-md border border-emerald-200 text-sm">
                {{ user.points }} pts
              </span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-sm">
            <div>
              <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Current Badge</span>
              <span :class="['inline-block px-2.5 py-0.5 rounded-full text-xs font-medium border', getBadgeStyle(user.current_badge)]">
                {{ formatBadge(user.current_badge) }}
              </span>
            </div>
            <div>
              <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Next Step</span>
              <template v-if="user.next_badge_info && user.next_badge_info.next_badge && user.next_badge_info.next_badge !== 'Niveau Max' && user.next_badge_info.next_badge !== 'Max Level'">
                <span class="text-xs font-medium text-slate-800 block">{{ formatBadge(user.next_badge_info.next_badge) }}</span>
                <span class="text-[11px] text-slate-400">({{ user.next_badge_info.points_needed }} pts needed)</span>
              </template>
              <template v-else>
                <span class="text-xs text-emerald-600 font-semibold">Max level reached</span>
              </template>
            </div>
          </div>

          <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button 
              @click="openRewardModal(user)" 
              class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-lg transition shadow-sm cursor-pointer text-center"
            >
              Credit Points
            </button>
          </div>
        </div>
      </div>

      <div v-else-if="!loadingUsers" class="text-center py-12 bg-white rounded-xl border border-slate-200 shadow-sm">
        <p class="text-slate-400">No users match these criteria.</p>
      </div>
    </section>

    <!-- Comments List Modal -->
    <div v-if="showCommentsListModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl border border-slate-100 max-h-[85vh] flex flex-col">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
          <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            <span>💬</span> Comments Feed
          </h3>
          <button @click="showCommentsListModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <!-- Selector for the active user performing the Like action -->
        <div class="mb-4 p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3">
          <label class="text-xs font-semibold text-slate-600">Like as:</label>
          <select 
            v-model="activeLikerId" 
            class="bg-white border border-slate-300 text-slate-800 text-xs rounded-lg p-2 outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.points }} pts)
            </option>
          </select>
        </div>

        <div class="overflow-y-auto flex-1 space-y-4 pr-1">
          <div v-if="loadingComments" class="py-6 text-center text-slate-500">
            Loading comments...
          </div>

          <div v-else-if="comments.length" class="space-y-3">
            <div 
              v-for="comment in comments" 
              :key="comment.id" 
              class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
              <div>
                <span class="font-semibold text-slate-900 text-sm">{{ comment.user?.name || 'Unknown Author' }}</span>
                <p class="text-slate-700 text-sm mt-1">{{ comment.content }}</p>
              </div>

              <div class="flex items-center gap-2 self-end sm:self-center">
                <!-- Reactive Like / Unlike Button -->
                <button 
                  @click="toggleLike(comment)" 
                  :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer flex items-center gap-1', 
                    isCommentLikedByActiveUser(comment) ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-100'
                  ]"
                >
                  <span>{{ isCommentLikedByActiveUser(comment) ? '❤️' : '🤍' }}</span>
                  <span>{{ comment.likes_count || 0 }}</span>
                </button>

                <button 
                  @click="deleteComment(comment.id)" 
                  class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-red-50 hover:text-red-600 hover:border-red-200 text-slate-600 rounded-lg text-xs transition cursor-pointer"
                >
                  🗑️ Delete
                </button>
              </div>
            </div>
          </div>

          <p v-else class="text-slate-400 text-sm text-center py-6">No comments yet.</p>
        </div>

        <div class="mt-4 pt-3 border-t border-slate-100 flex justify-end">
          <button 
            @click="showCommentsListModal = false" 
            class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm hover:bg-slate-200 transition font-medium cursor-pointer"
          >
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- Add Comment / New Message Modal -->
    <div v-if="showCommentModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-slate-800">Add a New Message</h3>
          <button @click="showCommentModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitComment">
          <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Author</label>
            <select 
              v-model="newComment.user_id" 
              required 
              class="w-full bg-slate-50 border border-slate-300 text-slate-800 text-sm rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="" disabled>Select a user</option>
              <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
            </select>
          </div>

          <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Message</label>
            <textarea 
              v-model="newComment.content" 
              required 
              rows="3" 
              placeholder="Write your message..." 
              class="w-full bg-slate-50 border border-slate-300 text-slate-800 text-sm rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div class="flex justify-end gap-2 mt-6">
            <button 
              type="button" 
              @click="showCommentModal = false" 
              class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm hover:bg-slate-200 transition font-medium cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="submittingComment"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition font-medium flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <span v-if="submittingComment" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
              Post
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Points Credit Modal -->
    <div v-if="selectedUser" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-slate-800">Add Points</h3>
          <button @click="selectedUser = null" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <p class="text-sm text-slate-600 mb-4">
          Reward allocation for <strong class="text-slate-900">{{ selectedUser.name }}</strong> (currently {{ selectedUser.points }} pts).
        </p>

        <form @submit.prevent="addPoints">
          <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Number of points to add</label>
            <input 
              type="number" 
              v-model="pointsToAdd" 
              min="1" 
              required
              placeholder="e.g.: 50"
              class="w-full bg-slate-50 border border-slate-300 text-slate-800 text-sm rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-500 outline-none"
            />
          </div>

          <div class="flex justify-end gap-2 mt-6">
            <button 
              type="button" 
              @click="selectedUser = null" 
              class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm hover:bg-slate-200 transition font-medium cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="submittingPoints"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition font-medium flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <span v-if="submittingPoints" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
              Confirm
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const users = ref([]);
const comments = ref([]);

const loadingUsers = ref(false);
const loadingComments = ref(false);
const submittingPoints = ref(false);
const submittingComment = ref(false);

const activeLikerId = ref(null);

const alertModal = ref({
  visible: false,
  message: '',
  type: 'error'
});

const showAlert = (message, type = 'error') => {
  alertModal.value = { visible: true, message, type };
};

const closeAlertModal = () => {
  alertModal.value.visible = false;
};

const selectedUser = ref(null);
const pointsToAdd = ref(50);

const selectedBadge = ref('');
const minPoints = ref('');

const showCommentModal = ref(false);
const showCommentsListModal = ref(false);
const newComment = ref({ user_id: '', content: '' });

let debounceTimeout = null;
const ACCESS_TOKEN = 'waspi_secret_token_2026';

const badgeLabels = {
  'beginner-badge': 'Beginner Badge',
  'beginner': 'Beginner',
  'top-fan': 'Top Fan',
  'super-fan': 'Super Fan',
  'aucun': 'None'
};

const formatBadge = (slug) => {
  if (!slug) return 'None';
  return badgeLabels[slug] || slug;
};

const getBadgeStyle = (badge) => {
  switch (badge) {
    case 'beginner-badge':
      return 'bg-blue-50 text-blue-700 border-blue-200';
    case 'beginner':
      return 'bg-indigo-50 text-indigo-700 border-indigo-200';
    case 'top-fan':
      return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'super-fan':
      return 'bg-purple-50 text-purple-700 border-purple-200';
    default:
      return 'bg-slate-100 text-slate-600 border-slate-200';
  }
};

const openCommentModalForUser = (user) => {
  newComment.value = { user_id: user ? user.id : (activeLikerId.value || ''), content: '' };
  showCommentModal.value = true;
};

const isCommentLikedByActiveUser = (comment) => {
  if (!comment.liked_by_users) return comment.is_liked || false;
  return comment.liked_by_users.includes(activeLikerId.value);
};

const fetchUsers = async () => {
  loadingUsers.value = true;

  try {
    const response = await axios.get('/api/users', {
      params: {
        access_token: ACCESS_TOKEN,
        type: selectedBadge.value || undefined,
        points: minPoints.value || undefined
      }
    });

    users.value = response.data.data || response.data || [];
    if (users.value.length && !activeLikerId.value) {
      activeLikerId.value = users.value[0].id;
    }
  } catch (error) {
    const msg = error.response?.status === 401
      ? 'Authorization error: Invalid access token.'
      : 'Failed to load users.';
    showAlert(msg, 'error');
  } finally {
    loadingUsers.value = false;
  }
};

const fetchComments = async () => {
  loadingComments.value = true;
  try {
    const response = await axios.get('/api/comments', {
      params: { access_token: ACCESS_TOKEN }
    });
    comments.value = response.data.data || response.data || [];
  } catch (error) {
    console.error('Comments API Error:', error);
  } finally {
    loadingComments.value = false;
  }
};

const submitComment = async () => {
  if (!newComment.value.user_id || !newComment.value.content.trim()) return;

  submittingComment.value = true;

  try {
    await axios.post('/api/comments', newComment.value, {
      params: { access_token: ACCESS_TOKEN }
    });

    showCommentModal.value = false;
    newComment.value = { user_id: activeLikerId.value || '', content: '' };

    await fetchUsers();
    await fetchComments();

    showAlert('Message published successfully!', 'success');
  } catch (error) {
    const msg = error.response?.data?.message || 'Error sending the message.';
    showAlert(msg, 'error');
  } finally {
    submittingComment.value = false;
  }
};

const toggleLike = async (comment) => {
  if (!activeLikerId.value) {
    showAlert('Please select a user to like.', 'error');
    return;
  }

  const isAlreadyLiked = isCommentLikedByActiveUser(comment);

  try {
    if (isAlreadyLiked) {
      await axios.delete(`/api/comments/${comment.id}/like`, {
        params: { 
          access_token: ACCESS_TOKEN,
          user_id: activeLikerId.value 
        }
      });
    } else {
      await axios.post(`/api/comments/${comment.id}/like`, {
        user_id: activeLikerId.value,
        comment_id: comment.id
      }, {
        params: { access_token: ACCESS_TOKEN }
      });
    }

    await fetchUsers();
    await fetchComments();
  } catch (error) {
    showAlert('Error updating like status.', 'error');
  }
};

const deleteComment = async (id) => {
  try {
    await axios.delete(`/api/comments/${id}`, {
      params: { access_token: ACCESS_TOKEN }
    });
    await fetchUsers();
    await fetchComments();
    showAlert('Message deleted successfully.', 'success');
  } catch (error) {
    showAlert('Error deleting message.', 'error');
  }
};

const openRewardModal = (user) => {
  selectedUser.value = user;
  pointsToAdd.value = 50;
};

const addPoints = async () => {
  if (!selectedUser.value || !pointsToAdd.value) return;

  submittingPoints.value = true;

  try {
    await axios.post(`/api/users/${selectedUser.value.id}/points`, {
      points: Number(pointsToAdd.value)
    }, {
      params: { access_token: ACCESS_TOKEN }
    });

    const userName = selectedUser.value.name;
    selectedUser.value = null;
    await fetchUsers();
    showAlert(`Points successfully credited for ${userName}!`, 'success');
  } catch (error) {
    const msg = error.response?.data?.message || 'Error awarding points.';
    showAlert(msg, 'error');
  } finally {
    submittingPoints.value = false;
  }
};

const handlePointsInput = () => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    fetchUsers();
    }, 400);
};

onMounted(() => {
  fetchUsers();
  fetchComments();
});
</script>