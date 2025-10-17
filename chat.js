// --- Polling-based browser notification for new messages ---
// Helper function to show notifications
function showNotification(title, message) {
  if (Notification.permission === "granted") {
    new Notification(title, {
      body: message,
      icon: 'tahanan-logo.jpg'
    });
  }
}

// Request notification permission if not already granted
if (Notification.permission !== "granted") {
  Notification.requestPermission();
}

// Track the last seen message ID
let lastMessageId = null;

// Poll the server every 5 seconds for new messages
// chat.js - Real-time Messenger Integration with Firebase
// --- Firebase SDK imports (for module environments) ---
// If using <script> tags, use CDN links for Firebase Auth and Firestore instead.
// For local dev, use: <script src="https://www.gstatic.com/firebasejs/10.11.0/firebase-app.js"></script>
//                     <script src="https://www.gstatic.com/firebasejs/10.11.0/firebase-auth.js"></script>
//                     <script src="https://www.gstatic.com/firebasejs/10.11.0/firebase-firestore.js"></script>


import { initializeApp } from "https://www.gstatic.com/firebasejs/10.11.0/firebase-app.js";
import { getAuth, onAuthStateChanged, signInWithEmailAndPassword, GoogleAuthProvider, signInWithPopup, signOut } from "https://www.gstatic.com/firebasejs/10.11.0/firebase-auth.js";
import { getFirestore, collection, query, where, orderBy, onSnapshot, addDoc, serverTimestamp, getDocs, doc, updateDoc, arrayUnion } from "https://www.gstatic.com/firebasejs/10.11.0/firebase-firestore.js";

// --- Session Manager (copied from index.html) ---
const sessionManager = {
  getCurrentUser: function() {
    const user = localStorage.getItem('currentUser');
    return user ? JSON.parse(user) : null;
  },
  requireLogin: function() {
    const user = localStorage.getItem('currentUser');
    if (!user) {
      window.location.href = 'login.html';
      return false;
    }
    return true;
  },
  logout: function() {
    localStorage.removeItem('currentUser');
    window.location.href = 'login.html';
  }
};

// --- Firebase Config ---
const firebaseConfig = {
  apiKey: "AIzaSyBKh0X9zMvJYwPmld1dngMBqkw-UWLGO7M",
  authDomain: "tnp-oms-2b2c7.firebaseapp.com",
  projectId: "tnp-oms-2b2c7",
  storageBucket: "tnp-oms-2b2c7.firebasestorage.app",
  messagingSenderId: "101796900523",
  appId: "1:101796900523:web:ff0a5dbc63bb16131f91ee",
  measurementId: "G-H52TVZV37N"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const db = getFirestore(app);

// --- DOM Elements ---
const inboxList = document.querySelector('#inbox-list');
let chatWindow = document.getElementById('chat-messages');
const messageInput = document.querySelector('main input[placeholder="Type a message..."]');
const sendButton = document.querySelector('main button.bg-blue-500');

// Skeleton elements
const inboxSkeleton = document.getElementById('inbox-skeleton');
const chatSkeleton = document.getElementById('chat-skeleton');
const profileSkeleton = document.getElementById('profile-skeleton');

// Mobile skeleton elements
const mobileInboxSkeleton = document.getElementById('mobileInboxSkeleton');
const mobileChatHeaderSkeleton = document.getElementById('mobileChatHeaderSkeleton');
const mobileMessagesSkeleton = document.getElementById('mobileMessagesSkeleton');
// Ensure chat window has proper classes
if (chatWindow) {
  chatWindow.className = 'flex-1 p-8 space-y-6 overflow-y-auto bg-gray-50 hidden-scrollbar';
}

let currentUser = null;
let selectedContact = null;
let selectedContactName = '';
let selectedContactPhoto = '';
let selectedContactRole = '';
let unsubscribeChat = null;

// --- Authentication UI ---
function showAuthUI() {
  // Simple modal for login (customize as needed)
  const modal = document.createElement('div');
  modal.innerHTML = `
    <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg w-80">
        <h2 class="text-lg font-bold mb-4">Sign In</h2>
        <input id="email" type="email" placeholder="Email" class="w-full mb-2 px-3 py-2 border rounded">
        <input id="password" type="password" placeholder="Password" class="w-full mb-4 px-3 py-2 border rounded">
        <button id="loginBtn" class="w-full bg-blue-500 text-white py-2 rounded mb-2">Login</button>
        <button id="googleBtn" class="w-full bg-red-500 text-white py-2 rounded">Sign in with Google</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
  document.getElementById('loginBtn').onclick = async () => {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    try {
      await signInWithEmailAndPassword(auth, email, password);
      modal.remove();
    } catch (e) {
      alert('Login failed: ' + e.message);
    }
  };
  document.getElementById('googleBtn').onclick = async () => {
    try {
      await signInWithPopup(auth, new GoogleAuthProvider());
      modal.remove();
    } catch (e) {
      alert('Google sign-in failed: ' + e.message);
    }
  };
}


// --- Validate session before Firebase Auth ---
document.addEventListener('DOMContentLoaded', function() {
  if (!sessionManager.requireLogin()) return;
  // Use session user for display and as fallback for Firebase Auth
  const sessionUser = sessionManager.getCurrentUser();
  // Listen for Firebase Auth state
  onAuthStateChanged(auth, user => {
    if (user) {
      currentUser = user;
      loadInbox();
      // --- PATCH: Always show inbox overlay on mobile after load ---
      setTimeout(() => {
        if (window.innerWidth < 1024) {
          const mobileInboxOverlay = document.getElementById('mobileInboxOverlay');
          if (mobileInboxOverlay) {
            mobileInboxOverlay.style.display = '';
            mobileInboxOverlay.classList.remove('hidden');
            mobileInboxOverlay.style.visibility = 'visible';
          }
        }
      }, 300);
    } else {
      console.log('No authenticated user');
      // Clean up inbox listener when user logs out
      if (inboxUnsubscribe) {
        inboxUnsubscribe();
        inboxUnsubscribe = null;
      }
      showAuthUI();
    }
  });
});


// --- Contact Search Bar with Fuzzy Matching ---
let allContacts = [];
let inboxDisplayCache = [];
let inboxUnsubscribe = null;

async function loadInbox() {
  if (!currentUser) return;
  try {
    // Fetch all users
    const usersSnap = await getDocs(query(collection(db, 'users'), where('role', 'in', ['staff', 'volunteer', 'admin'])));
  allContacts = [];
  window.allContacts = allContacts;
    usersSnap.forEach(d => {
      const data = d.data();
      if (data.uid !== currentUser.uid) {
        allContacts.push({
          uid: data.uid,
          fullName: `${data.firstName || ''} ${data.lastName || ''}`.trim(),
          role: data.role,
          photo: data.photoString || 'user.png'
        });
      }
    });

    // Fetch all messages involving current user
    const messagesRef = collection(db, 'messages');
    const q = query(messagesRef,
      where('receiverId', '==', currentUser.uid)
    );
    const sentQ = query(messagesRef,
      where('senderId', '==', currentUser.uid)
    );
    const [receivedSnap, sentSnap] = await Promise.all([getDocs(q), getDocs(sentQ)]);
    const allMessages = [];
  receivedSnap.forEach(d => allMessages.push({...d.data(), id: d.id}));
  sentSnap.forEach(d => allMessages.push({...d.data(), id: d.id}));

    // Group by contact (other user)
    const latestByContact = {};
    allMessages.forEach(msg => {
      const contactId = msg.senderId === currentUser.uid ? msg.receiverId : msg.senderId;
      if (!latestByContact[contactId] || (msg.timestamp && msg.timestamp.seconds > latestByContact[contactId].timestamp.seconds)) {
        latestByContact[contactId] = msg;
      }
    });

    // Build inbox display items, show all users (with or without conversations)
    const inboxDisplay = allContacts.map(user => {
      const latestMsg = latestByContact[user.uid];
      let status = 'Available';
      
      if (latestMsg) {
        if (Array.isArray(latestMsg.hiddenFor) && latestMsg.hiddenFor.includes(currentUser.uid)) {
          return null; // skip hidden conversations
        }
        if (latestMsg.senderId === currentUser.uid) {
          // You sent the message
          if (latestMsg.seen) {
            status = 'Seen';
          } else if (latestMsg.receiverId === user.uid) {
            status = 'Delivered';
          }
        } else {
          // You received the message
          status = 'Delivered';
        }
      }
      
      return {
        ...user,
        lastMessage: latestMsg ? latestMsg.text : 'No messages yet',
        lastTimestamp: latestMsg ? latestMsg.timestamp : null,
        messageId: latestMsg ? latestMsg.id : null,
        unseen: latestMsg ? (latestMsg.receiverId === currentUser.uid && !latestMsg.seen) : false,
        status: status,
        hasConversation: !!latestMsg
      };
    })
    // Show all users (including those without conversations), but skip hidden ones
    .filter(user => user !== null);

    // Sort inbox: users with conversations first (by latest message), then users without conversations (alphabetically)
    inboxDisplay.sort((a, b) => {
      // If both have conversations, sort by timestamp
      if (a.hasConversation && b.hasConversation) {
        const ta = a.lastTimestamp ? a.lastTimestamp.seconds : 0;
        const tb = b.lastTimestamp ? b.lastTimestamp.seconds : 0;
        return tb - ta;
      }
      // If only one has conversation, prioritize it
      if (a.hasConversation && !b.hasConversation) return -1;
      if (!a.hasConversation && b.hasConversation) return 1;
      // If neither has conversation, sort alphabetically by name
      return a.fullName.localeCompare(b.fullName);
    });

    inboxDisplayCache = inboxDisplay;
    renderInbox(inboxDisplay, false);
    setupContactSearch();
    
    // Set up real-time inbox updates
    setupInboxRealTimeUpdates();

    // Auto-select the first conversation if available
    if (inboxDisplay.length > 0) {
      // Use a short timeout to ensure DOM is updated
      setTimeout(() => {
        // Find the first contact in the inbox list
        const first = inboxDisplay[0];
        if (first && typeof selectContact === 'function') {
          selectContact(first.uid, first.fullName, first.photo, first.role);
        }
      }, 100);
    }
  } catch (error) {
    console.error('Error loading inbox:', error);
    renderInbox([], true);
  }
    // End try block
}

// --- Real-time Inbox Updates ---
function setupInboxRealTimeUpdates() {
  if (!currentUser) return;
  
  // Unsubscribe from previous listener if exists
  if (inboxUnsubscribe) {
    inboxUnsubscribe();
  }
  
  
    // Listen to all messages involving current user
    const messagesRef = collection(db, 'messages');
    const q = query(
      messagesRef,
      where('participants', 'array-contains', currentUser.uid),
      orderBy('timestamp', 'desc')
    );
  inboxUnsubscribe = onSnapshot(q, async (snapshot) => {

    // Update inbox cache with latest messages
    await updateInboxCache();
  }, (error) => {
    console.error('Error in inbox real-time listener:', error);
  });
}

// Update inbox cache with latest messages
async function updateInboxCache() {
  if (!currentUser || !allContacts.length) return;
  
  try {
    // Fetch all messages involving current user
    const messagesRef = collection(db, 'messages');
    const q = query(messagesRef,
      where('receiverId', '==', currentUser.uid)
    );
    const sentQ = query(messagesRef,
      where('senderId', '==', currentUser.uid)
    );
    const [receivedSnap, sentSnap] = await Promise.all([getDocs(q), getDocs(sentQ)]);
    const allMessages = [];
      receivedSnap.forEach(d => allMessages.push({...d.data(), id: d.id}));
      sentSnap.forEach(d => allMessages.push({...d.data(), id: d.id}));

    // Group by contact (other user)
    const latestByContact = {};
    allMessages.forEach(msg => {
      const contactId = msg.senderId === currentUser.uid ? msg.receiverId : msg.senderId;
      if (!latestByContact[contactId] || (msg.timestamp && msg.timestamp.seconds > latestByContact[contactId].timestamp.seconds)) {
        latestByContact[contactId] = msg;
      }
    });

    // Update inbox display cache
    const updatedInboxDisplay = allContacts.map(user => {
      const latestMsg = latestByContact[user.uid];
      let status = 'Available';
      let showNotif = false;
      if (latestMsg) {
        if (Array.isArray(latestMsg.hiddenFor) && latestMsg.hiddenFor.includes(currentUser.uid)) {
          return null; // skip hidden conversations
        }
        if (latestMsg.senderId === currentUser.uid) {
          // You sent the message
          if (latestMsg.seen) {
            status = 'Seen';
          } else if (latestMsg.receiverId === user.uid) {
            status = 'Delivered';
          }
        } else {
          // You received the message
          status = 'Delivered';
          // If message is unseen, trigger notification
          if (!latestMsg.seen && latestMsg.receiverId === currentUser.uid) {
            showNotif = true;
          }
        }
      }
      // Show notification for unseen message
      if (showNotif) {
        showNotification('New Message From ' + (user.fullName || 'Unknown'), latestMsg.text || 'You have a new message');
      }
      return {
        ...user,
        lastMessage: latestMsg ? latestMsg.text : 'No messages yet',
        lastTimestamp: latestMsg ? latestMsg.timestamp : null,
        messageId: latestMsg ? latestMsg.id : null,
        unseen: latestMsg ? (latestMsg.receiverId === currentUser.uid && !latestMsg.seen) : false,
        status: status,
        hasConversation: !!latestMsg
      };
    })
    .filter(user => user !== null);

    // Sort inbox: users with conversations first (by latest message), then users without conversations (alphabetically)
    updatedInboxDisplay.sort((a, b) => {
      // If both have conversations, sort by timestamp
      if (a.hasConversation && b.hasConversation) {
        const ta = a.lastTimestamp ? a.lastTimestamp.seconds : 0;
        const tb = b.lastTimestamp ? b.lastTimestamp.seconds : 0;
        return tb - ta;
      }
      // If only one has conversation, prioritize it
      if (a.hasConversation && !b.hasConversation) return -1;
      if (!a.hasConversation && b.hasConversation) return 1;
      // If neither has conversation, sort alphabetically by name
      return a.fullName.localeCompare(b.fullName);
    });

    // Only update if there are actual changes
    const hasChanges = JSON.stringify(inboxDisplayCache) !== JSON.stringify(updatedInboxDisplay);
    if (hasChanges) {
      console.log('Inbox changes detected, updating display');
      inboxDisplayCache = updatedInboxDisplay;
      renderInbox(updatedInboxDisplay, false);
    }
    
  } catch (error) {
    console.error('Error updating inbox cache:', error);
  }
}

// --- Fuzzy Matching Helper ---
function fuzzyMatch(str, pattern) {
  str = str.toLowerCase();
  pattern = pattern.toLowerCase();
  if (!pattern) return 1; // Show all if empty
  let score = 0, lastIdx = -1;
  for (let i = 0; i < pattern.length; i++) {
    let idx = str.indexOf(pattern[i], lastIdx + 1);
    if (idx === -1) return 0;
    score += 1 / (idx - lastIdx);
    lastIdx = idx;
  }
  return score;
}

// --- Setup Contact Search Bar and Suggestions ---
function setupContactSearch() {
  const searchInput = document.querySelector('#inbox input[type="text"]');
  let suggestionPanel = document.getElementById('contact-suggestions');
  if (!suggestionPanel) {
    suggestionPanel = document.createElement('div');
    suggestionPanel.id = 'contact-suggestions';
    suggestionPanel.className = 'absolute z-10 left-0 right-0 bg-white border rounded-lg shadow-lg mt-2 max-h-64 overflow-y-auto';
    searchInput.parentNode.appendChild(suggestionPanel);
    suggestionPanel.style.display = 'none';
  }

  searchInput.oninput = function() {
      const val = searchInput.value.trim();
      if (!val) {
        suggestionPanel.style.display = 'none';
        renderInbox(inboxDisplayCache, false);
        return;
      }
      console.log('Searching for:', val);
      // Fuzzy match all contacts for suggestions only
      let matches = allContacts.map(u => ({
        ...u,
        score: fuzzyMatch(u.fullName, val)
      })).filter(u => u.score > 0)
        .sort((a, b) => b.score - a.score)
        .slice(0, 10);
      console.log('Found matches:', matches);
      suggestionPanel.innerHTML = '';
      matches.forEach(user => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-3 p-3 hover:bg-blue-50 cursor-pointer';
        div.innerHTML = `
          <img src="${user.photo}" alt="avatar" class="w-8 h-8 rounded-full object-cover bg-gray-200 border" />
          <div>
            <p class="font-medium text-gray-900">${user.fullName}</p>
            <p class="text-xs text-gray-500">${capitalizeRole(user.role)}</p>
          </div>
        `;
        // Add click event listener
        // Use mousedown for more reliable event triggering
        div.addEventListener('mousedown', function(e) {
          e.preventDefault();
          e.stopPropagation();
          console.log('Contact selected from search bar (mousedown):', user);
          suggestionPanel.style.display = 'none';
          searchInput.value = '';
          selectContact(user.uid, user.fullName, user.photo, user.role);
          setTimeout(() => {
            const chatWindow = document.getElementById('chat-messages');
            if (chatWindow) {
              chatWindow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            if (messageInput) {
              messageInput.focus();
            }
          }, 200);
        });
        suggestionPanel.appendChild(div);
      });
      suggestionPanel.style.display = matches.length ? 'block' : 'none';
      suggestionPanel.style.display = matches.length ? 'block' : 'none';
      if (!matches.length) {
        suggestionPanel.innerHTML = '<div class="p-3 text-gray-500 text-center">No matching contacts found</div>';
      }
    };  // Hide suggestions on blur
  searchInput.onblur = function() {
    setTimeout(() => { suggestionPanel.style.display = 'none'; }, 200);
  };

  // Fix: Focus chat box and scroll to message input after selecting contact
  suggestionPanel.addEventListener('click', function(e) {
    setTimeout(() => {
      const chatWindow = document.getElementById('chat-messages');
      if (chatWindow) {
        chatWindow.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      if (messageInput) {
        messageInput.focus();
      }
    }, 200);
  });
}

// --- Render Inbox List with Users ---
// Render inbox with latest message, bold if unseen
function renderInbox(users, error = false, inboxItems = []) {
  // Hide skeleton loading
  if (inboxSkeleton) {
    inboxSkeleton.style.display = 'none';
  }
  
  inboxList.innerHTML = '';
  if (error) {
    inboxList.innerHTML = `<li class="p-4 text-sm text-red-500">⚠️ Failed to load users. Check console.</li>`;
    return;
  }
  if (users.length === 0) {
    inboxList.innerHTML = `<li class="p-4 text-sm text-gray-500">No users found.</li>`;
    return;
  }
  users.forEach((user, idx) => {
    if (currentUser && user.uid === currentUser.uid) return;
    
    // Bold if unseen, different styling for users without conversations
    const messageClass = user.unseen ? 'font-bold text-gray-900' : 
                        user.hasConversation ? 'font-normal text-gray-700' : 'font-normal text-gray-500 italic';
    
    // Add highlight class for unseen messages
    const highlightClass = user.unseen ? 'bg-blue-50 border-l-4 border-blue-500' : '';
    
    const li = document.createElement('li');
    li.className = `p-4 hover:bg-gray-50 cursor-pointer flex items-center gap-3 ${highlightClass}`;
    li.setAttribute('data-uid', user.uid);
    // Format timestamp
    let timeStr = '';
    if (user.lastTimestamp) {
      let dateObj = user.lastTimestamp.seconds ? new Date(user.lastTimestamp.seconds * 1000) : new Date(user.lastTimestamp);
      let options = { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      timeStr = `<span class='text-xs text-gray-400 ml-2'>${dateObj.toLocaleString('en-US', options)}</span>`;
    } else if (!user.hasConversation) {
      timeStr = `<span class='text-xs text-gray-400 ml-2'>Available</span>`;
    }
    // Delete button (trash icon) - only show for users with conversations
    const deleteBtn = document.createElement('button');
    if (user.hasConversation) {
      deleteBtn.className = 'delete-inbox-btn ml-2 p-1 rounded-full hover:bg-red-100 flex items-center justify-center';
      deleteBtn.title = 'Delete conversation';
      deleteBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>`;
    } else {
      deleteBtn.className = 'ml-2 p-1 rounded-full flex items-center justify-center opacity-0';
      deleteBtn.style.visibility = 'hidden';
    }
    // Conversation content
    const contentDiv = document.createElement('div');
    contentDiv.className = 'flex-1';
    contentDiv.innerHTML = `
      <div class="flex justify-between items-center">
        <p class="font-medium text-gray-900">${user.fullName}</p>
        ${timeStr}
      </div>
      <p class="${messageClass}">${user.lastMessage || ''}</p>
      <p class="text-sm text-gray-500">${capitalizeRole(user.role)}</p>
    `;
    // Layout: avatar | content | delete
    const img = document.createElement('img');
    img.src = user.photo;
    img.alt = 'avatar';
    img.className = 'w-10 h-10 rounded-full object-cover bg-gray-200 border';
    // If image fails to load, use a fallback
    img.onerror = function(event) {
      event.preventDefault();
      this.onerror = null;
      // Suppress error output in the console
      try { window.event = null; } catch (e) {}
      this.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.fullName || 'User');
    };
    li.appendChild(img);
    li.appendChild(contentDiv);
    li.appendChild(deleteBtn);
    // Click to select conversation
    contentDiv.onclick = async () => {
      selectContact(user.uid, user.fullName, user.photo, user.role);
      // Only mark as seen if there's an unseen message
      if (user.unseen && user.messageId && user.hasConversation) {
        try {
          const { doc, updateDoc } = await import('https://www.gstatic.com/firebasejs/10.11.0/firebase-firestore.js');
          await updateDoc(doc(db, 'messages', user.messageId), { seen: true });
        } catch (err) {
          console.error('Failed to mark message as seen:', err);
        }
        const msgElem = li.querySelector('p.font-bold');
        if (msgElem) {
          msgElem.classList.remove('font-bold', 'text-gray-900');
          msgElem.classList.add('font-normal', 'text-gray-700');
        }
        const statusElem = li.querySelector('p.text-xs');
        if (statusElem) {
          statusElem.textContent = 'Seen';
          statusElem.classList.remove('text-blue-500');
          statusElem.classList.add('text-green-600', 'font-semibold');
        }
      }
    };
    // Delete button logic (only for users with conversations)
    if (user.hasConversation) {
      deleteBtn.onclick = async (e) => {
        e.stopPropagation();
        if (confirm('Delete this conversation?')) {
        li.remove();
        // Remove from inboxDisplayCache so it stays hidden until reload
        const idx = inboxDisplayCache.findIndex(u => u.uid === user.uid);
        if (idx !== -1) inboxDisplayCache.splice(idx, 1);
        // Hide all messages between currentUser and user for current user only
        try {
          const { collection, query, where, getDocs, updateDoc, doc, arrayUnion } = await import('https://www.gstatic.com/firebasejs/10.11.0/firebase-firestore.js');
          const messagesRef = collection(db, 'messages');
          // Query for messages where participants contains both user IDs
          const q = query(messagesRef, where('participants', 'array-contains', currentUser.uid));
          const snap = await getDocs(q);
          const toHide = snap.docs.filter(d => {
            const data = d.data();
            return Array.isArray(data.participants) && data.participants.includes(user.uid);
          });
          for (const d of toHide) {
            await updateDoc(doc(db, 'messages', d.id), { hiddenFor: arrayUnion(currentUser.uid) });
          }
        } catch (err) {
          alert('Failed to hide conversation.');
          console.error('Error hiding conversation:', err);
        }
      }
    };
    }
    inboxList.appendChild(li);
  });
}


// --- Helper: Capitalize role ---
function capitalizeRole(role) {
  if (!role) return '';
  return role.charAt(0).toUpperCase() + role.slice(1);
}

// --- Select Contact and Load Chat ---
function selectContact(contactId, contactName, contactPhoto, contactRole, skipLoading = false) {
  window.selectContact = selectContact;

  
  if (!contactId) {
    console.error('No contact ID provided');
    return;
  }
  if (!currentUser) {
    console.error('No current user - cannot select contact');
    return;
  }
  
  // Check if we're selecting the same contact (avoid unnecessary reload)
  const isSameContact = selectedContact === contactId;
  
  selectedContact = contactId;
  selectedContactName = contactName || '';
  selectedContactPhoto = contactPhoto || 'user.png';
  selectedContactRole = contactRole || '';



  // --- Update mobile chat header (always) ---
  var mobileChatUserName = document.getElementById('mobileChatUserName');
  var mobileChatUsername = document.getElementById('mobileChatUsername');
  var mobileChatUserAvatar = document.getElementById('mobileChatUserAvatar');
  
  // Hide mobile chat header skeleton and show real content
  if (mobileChatHeaderSkeleton) {
    mobileChatHeaderSkeleton.style.display = 'none';
  }
  
  const mobileChatHeaderContent = document.getElementById('mobileChatHeaderContent');
  if (mobileChatHeaderContent) {
    mobileChatHeaderContent.style.display = 'flex';
  }
  
  if (mobileChatUserName) mobileChatUserName.textContent = contactName || '';
  if (mobileChatUsername) mobileChatUsername.textContent = contactRole ? '@' + contactRole : '';
  if (mobileChatUserAvatar) {
    if (contactPhoto) {
      mobileChatUserAvatar.innerHTML = `<img src="${contactPhoto}" alt="avatar" class="w-8 h-8 rounded-full object-cover bg-gray-200" />`;
    } else {
      mobileChatUserAvatar.textContent = (contactName && contactName[0]) ? contactName[0].toUpperCase() : 'U';
    }
  }

  updateChatHeader();
  updateProfileInfo(contactName, contactPhoto, contactRole);

  if (unsubscribeChat) unsubscribeChat();

  // ✅ Show loading message only for new contacts or when explicitly requested
  let chatWindow = document.getElementById('chat-messages');
  if (chatWindow && (!isSameContact || !skipLoading)) {
    chatWindow.innerHTML = `<div class="text-center text-gray-400 mt-10">
      Loading messages...
    </div>`;
  }

  // ✅ Sort participant IDs for consistent queries
  const participants = [currentUser.uid, contactId].sort();

  // Query messages between current user and selected contact
  const messagesRef = collection(db, 'messages');
  const q = query(
    messagesRef,
    where('senderId', 'in', [currentUser.uid, contactId]),
    where('receiverId', 'in', [currentUser.uid, contactId]),
    orderBy('timestamp')
  );


  try {
    unsubscribeChat = onSnapshot(q, snapshot => {
      const messages = [];
      snapshot.forEach(d => {
        const data = d.data();
        if (
          (data.senderId === currentUser.uid && data.receiverId === contactId) ||
          (data.senderId === contactId && data.receiverId === currentUser.uid)
        ) {
          messages.push({...data, id: d.id});
        }
      });

      // --- DESKTOP CHAT RENDER ---
      if (chatWindow) {
        if (messages.length === 0) {
          chatWindow.innerHTML = `<div class="text-center text-gray-400 mt-10">
            No messages yet. Start the conversation with ${contactName || "this user"}!
          </div>`;
        } else {
          renderChat(messages);
        }
      }

      // --- MOBILE CHAT RENDER ---
      var mobileMessagesLoading = document.getElementById('mobileMessagesLoading');
      var mobileMessagesEmpty = document.getElementById('mobileMessagesEmpty');
      var mobileMessagesList = document.getElementById('mobileMessagesList');
      if (mobileMessagesLoading) mobileMessagesLoading.style.display = 'none';
      
      // Hide mobile messages skeleton
      if (mobileMessagesSkeleton) {
        mobileMessagesSkeleton.style.display = 'none';
      }
      
      if (mobileMessagesList) {
        // Remove any optimistic messages before rendering real messages
        const optimisticMessages = mobileMessagesList.querySelectorAll('[id^="temp-"]');
        optimisticMessages.forEach(msg => msg.remove());
        
        mobileMessagesList.innerHTML = '';
        // Filter out hidden messages for current user
        var uid = currentUser && currentUser.uid ? currentUser.uid : (sessionManager.getCurrentUser() && sessionManager.getCurrentUser().uid);
        var filtered = messages.filter(function(msg) { return !Array.isArray(msg.hiddenFor) || !msg.hiddenFor.includes(uid); });
        if (filtered.length === 0) {
          if (mobileMessagesEmpty) mobileMessagesEmpty.style.display = '';
          mobileMessagesList.classList.add('hidden');
        } else {
          if (mobileMessagesEmpty) mobileMessagesEmpty.style.display = 'none';
          mobileMessagesList.classList.remove('hidden');
          filtered.forEach(function(msg) {
            var isMine = msg.senderId === (currentUser && currentUser.uid);
            var bubbleClass = isMine ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-800';
            var flexClass = isMine ? 'flex justify-end' : 'flex justify-start';
            var div = document.createElement('div');
            div.className = flexClass;
            var content = '';
            if (msg.unsentForEveryone) {
              var placeholder = isMine ? 'You unsent this message' : 'This message was unsent';
              content = `<div class="${isMine ? 'bg-blue-100 text-blue-500 italic' : 'bg-gray-200 text-gray-500 italic'} px-4 py-2 rounded-2xl max-w-xs opacity-85">${placeholder}</div>`;
            } else {
              // Add delete button for user's own messages
              var deleteBtn = '';
              if (isMine) {
                deleteBtn = `<button class="mobile-delete-msg-btn absolute top-1 right-1 p-1 rounded-full hover:bg-red-100" style="display:none;" title="Delete">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>`;
              }
              content = `<div class="${bubbleClass} px-4 py-2 rounded-2xl max-w-xs relative group" style="position:relative;">${msg.text || ''}${deleteBtn}</div>`;
            }
            div.innerHTML = content;
            
            // Add delete button functionality for mobile messages
            if (isMine && !msg.unsentForEveryone) {
              var bubble = div.querySelector('.group');
              var delBtn = bubble.querySelector('.mobile-delete-msg-btn');
              if (bubble && delBtn) {
                bubble.addEventListener('mouseenter', function() { delBtn.style.display = 'block'; });
                bubble.addEventListener('mouseleave', function() { delBtn.style.display = 'none'; });
                delBtn.addEventListener('click', function(e) {
                  e.stopPropagation();
                  showUnsendModal(msg);
                });
              }
            }
            
            mobileMessagesList.appendChild(div);
          });
          // Scroll to bottom
          setTimeout(function() { mobileMessagesList.scrollTop = mobileMessagesList.scrollHeight; }, 100);
        }
      }

      // Mark unread messages as seen ONLY if this chat is currently open
      messages.forEach(msg => {
        if (
          msg.receiverId === currentUser.uid &&
          !msg.seen &&
          selectedContact === (msg.senderId === currentUser.uid ? msg.receiverId : msg.senderId)
        ) {
          const docRef = doc(db, 'messages', msg.id);
          updateDoc(docRef, { seen: true })
            .then(() => console.log('Message marked as seen:', msg.id))
            .catch(err => console.error('Failed to mark message as seen:', err));
        }
      });
    }, error => {
      console.error('Error listening to messages:', error);
      if (chatWindow) {
        chatWindow.innerHTML = `<div class="text-center text-red-500 mt-10">
          Error loading messages. Please try again.
        </div>`;
      }
      var mobileMessagesLoading = document.getElementById('mobileMessagesLoading');
      if (mobileMessagesLoading) mobileMessagesLoading.style.display = 'none';
      var mobileMessagesEmpty = document.getElementById('mobileMessagesEmpty');
      if (mobileMessagesEmpty) mobileMessagesEmpty.style.display = '';
    });
  } catch (error) {
    console.error('Error setting up message listener:', error);
    if (chatWindow) {
      chatWindow.innerHTML = `<div class="text-center text-red-500 mt-10">
        Error loading messages. Please try again.
      </div>`;
    }
    var mobileMessagesLoading = document.getElementById('mobileMessagesLoading');
    if (mobileMessagesLoading) mobileMessagesLoading.style.display = 'none';
    var mobileMessagesEmpty = document.getElementById('mobileMessagesEmpty');
    if (mobileMessagesEmpty) mobileMessagesEmpty.style.display = '';
  }
}


// Update Profile Info Panel
function updateProfileInfo(name, photo, role) {
  let profilePanel = document.getElementById('profile-info');
  if (!profilePanel) return; // Use existing panel from HTML
  
  // Hide skeleton loading
  if (profileSkeleton) {
    profileSkeleton.style.display = 'none';
  }
  
  profilePanel.innerHTML = `
    <div class="flex flex-col items-center gap-6">
      <img src="${photo || 'user.png'}" alt="Profile" class="w-24 h-24 rounded-full object-cover border">
      <div class="text-center">
        <div class="font-semibold text-gray-800 text-xl">${name || ''}</div>
        <div class="text-sm text-gray-500">${capitalizeRole(role) || ''}</div>
      </div>
    </div>
    <div class="mt-10">
      <div class="font-medium text-gray-700 mb-2">Status</div>
      <div class="bg-green-100 text-green-700 px-4 py-3 rounded-lg text-base">Available to chat</div>
    </div>
  `;
}

// --- Update Chat Header with Selected Name ---
function updateChatHeader() {
  // Ensure we have a contact selected
  if (!selectedContactName) return;

  // Mobile header
  const mobileHeader = document.querySelector('main > .lg\\:hidden.flex.items-center');
  if (mobileHeader) {
    const nameSpan = mobileHeader.querySelector('span.font-medium');
    if (nameSpan) nameSpan.textContent = selectedContactName;
    const avatar = mobileHeader.querySelector('div.w-8.h-8.rounded-full');
    if (avatar) {
      avatar.innerHTML = `<img src="${selectedContactPhoto}" alt="avatar" class="w-8 h-8 rounded-full object-cover bg-gray-200" />`;
    }
    const roleSpan = mobileHeader.querySelector('span.text-xs.text-gray-500');
    if (roleSpan) roleSpan.textContent = capitalizeRole(selectedContactRole);
  }

  // Desktop header (above chat window)
  let desktopHeader = document.getElementById('desktop-chat-header');
  if (!desktopHeader) {
    // Create desktop header if not present
    desktopHeader = document.createElement('div');
    desktopHeader.id = 'desktop-chat-header';
    desktopHeader.className = 'hidden lg:flex items-center gap-3 px-4 py-2 border-b bg-white';
    desktopHeader.innerHTML = `
      <div class="w-8 h-8 rounded-full bg-gray-300" id="desktop-chat-avatar"></div>
      <div class="flex flex-col">
        <span class="font-medium text-gray-800"></span>
        <span class="text-xs text-gray-500" id="desktop-chat-role"></span>
      </div>
    `;
    // Insert above chat window (after main tag open, before messages)
    const main = document.querySelector('main');
    if (main) main.insertBefore(desktopHeader, main.children[0]);
  }
  // Update name
  const nameSpan = desktopHeader.querySelector('span.font-medium');
  if (nameSpan) nameSpan.textContent = selectedContactName;
  // Update avatar
  const avatar = desktopHeader.querySelector('#desktop-chat-avatar');
  if (avatar) {
    avatar.innerHTML = `<img src="${selectedContactPhoto}" alt="avatar" class="w-8 h-8 rounded-full object-cover bg-gray-200" />`;
  }
  // Update role
  const roleSpan = desktopHeader.querySelector('#desktop-chat-role');
  if (roleSpan) roleSpan.textContent = capitalizeRole(selectedContactRole);
}

// --- Optimistic Message Functions ---
function addOptimisticMessage(tempMessage) {
  let chatWindow = document.getElementById('chat-messages');
  if (!chatWindow) return;
  
  const div = document.createElement('div');
  const isMine = tempMessage.senderId === currentUser?.uid;
  const bubbleClass = isMine ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-800';
  const flexClass = isMine ? 'flex justify-end items-start space-x-2' : 'flex items-start space-x-2';
  
  div.className = flexClass;
  div.id = tempMessage.id; // Set ID for removal
  div.innerHTML = `<div class="${bubbleClass} px-4 py-2 rounded-2xl max-w-xs group relative opacity-75" style="position:relative;">
    <div class="break-words">${tempMessage.text || ''}</div>
  </div>`;
  
  chatWindow.appendChild(div);
  
  // Scroll to bottom
  setTimeout(() => {
    chatWindow.scrollTop = chatWindow.scrollHeight;
  }, 100);
}

function removeOptimisticMessage(messageId) {
  const tempElement = document.getElementById(messageId);
  if (tempElement) {
    tempElement.remove();
  }
}

function addOptimisticMobileMessage(tempMessage) {
  const mobileMessagesList = document.getElementById('mobileMessagesList');
  if (!mobileMessagesList) return;
  
  const div = document.createElement('div');
  const isMine = tempMessage.senderId === currentUser?.uid;
  const bubbleClass = isMine ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-800';
  const flexClass = isMine ? 'flex justify-end' : 'flex justify-start';
  
  div.className = flexClass;
  div.id = tempMessage.id; // Set ID for removal
  div.innerHTML = `<div class="${bubbleClass} px-4 py-2 rounded-2xl max-w-xs opacity-75">${tempMessage.text || ''}</div>`;
  
  mobileMessagesList.appendChild(div);
  
  // Scroll to bottom
  setTimeout(() => {
    mobileMessagesList.scrollTop = mobileMessagesList.scrollHeight;
  }, 100);
}

function removeOptimisticMobileMessage(messageId) {
  const tempElement = document.getElementById(messageId);
  if (tempElement) {
    tempElement.remove();
  }
}

// --- Render Chat Window ---
function renderChat(messages) {
  
  // Always re-query the chatWindow to ensure it's available
  let chatWindow = document.getElementById('chat-messages');
  if (!chatWindow) {
    console.error('Chat window not found');
    return;
  }
  
  // Hide skeleton loading
  if (chatSkeleton) {
    chatSkeleton.style.display = 'none';
  }
  
  // Remove any optimistic messages before rendering real messages
  const optimisticMessages = chatWindow.querySelectorAll('[id^="temp-"]');
  optimisticMessages.forEach(msg => msg.remove());
  
  chatWindow.innerHTML = '';
  
  // Sort and filter messages by timestamp, and skip those hidden for current user
  let uid = currentUser && currentUser.uid ? currentUser.uid : (sessionManager.getCurrentUser() && sessionManager.getCurrentUser().uid);
  const sortedMessages = [...messages]
    .sort((a, b) => {
      const timeA = a.timestamp?.seconds || 0;
      const timeB = b.timestamp?.seconds || 0;
      return timeA - timeB;
    })
    .filter(msg => !Array.isArray(msg.hiddenFor) || !msg.hiddenFor.includes(uid));

  sortedMessages.forEach(msg => {
    if (msg.unsentForEveryone) {
      const isMine = msg.senderId === currentUser?.uid;
      const flexClass = isMine ? 'flex justify-end items-start space-x-2' : 'flex items-start space-x-2';
      const bubbleClass = isMine ? 'bg-blue-100 text-blue-500 italic' : 'bg-gray-200 text-gray-500 italic';
      const placeholder = isMine ? 'You unsent this message' : 'This message was unsent';
      const div = document.createElement('div');
      div.className = flexClass;
      div.innerHTML = `<div class="${bubbleClass} px-4 py-2 rounded-2xl max-w-xs group relative" style="position:relative; opacity:0.85;">
        <div class="break-words">${placeholder}</div>
      </div>`;
      chatWindow.appendChild(div);
      return;
    }
    // ...existing code for normal messages...
    const div = document.createElement('div');
    let timeStr = '';
    if (msg.timestamp) {
      let dateObj = msg.timestamp.seconds ? new Date(msg.timestamp.seconds * 1000) : new Date(msg.timestamp);
      let options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      timeStr = `<div class='chat-timestamp text-xs mt-2 text-right text-gray-400 hidden group-hover:block'>${dateObj.toLocaleString('en-US', options)}</div>`;
    }
    let isMine = msg.senderId === currentUser?.uid;
    let bubbleClass = isMine ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-800';
    let flexClass = isMine ? 'flex justify-end items-start space-x-2' : 'flex items-start space-x-2';
    div.className = flexClass;
    let deleteIcon = '';
    if (isMine) {
      deleteIcon = `<button class=\"delete-msg-btn absolute top-2 right-2 p-1 rounded-full hover:bg-red-100\" style=\"display:none;\" title=\"Delete\">
        <svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-4 w-4 text-red-500\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">
          <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\" />
        </svg>
      </button>`;
    }
    div.innerHTML = `<div class=\"${bubbleClass} px-4 py-2 rounded-2xl max-w-xs group relative\" style=\"position:relative;\">
      <div class=\"break-words\">${msg.text || ''}</div>
      ${timeStr}
      ${deleteIcon}
    </div>`;
    if (isMine) {
      const bubble = div.querySelector('.group');
      const delBtn = bubble.querySelector('.delete-msg-btn');
      bubble.addEventListener('mouseenter', () => { delBtn.style.display = 'block'; });
      bubble.addEventListener('mouseleave', () => { delBtn.style.display = 'none'; });
      delBtn.onclick = (e) => {
        e.stopPropagation();
        showUnsendModal(msg);
      };
    }
    chatWindow.appendChild(div);
  });

  // Scroll to bottom after rendering
  setTimeout(() => {
    chatWindow.scrollTop = chatWindow.scrollHeight;
  }, 100);
}

// --- Send Message ---
async function sendMessage() {
  if (!selectedContact || !selectedContactName) {
    console.error('No contact selected', { selectedContact, selectedContactName });
    return alert('Please select a contact from the list first.');
  }
  const text = messageInput.value.trim();
  if (!text) return;
  
  // Clear input immediately for better UX
  messageInput.value = '';
  
  // Optimistic UI update - add message to chat immediately
  const tempMessage = {
    id: 'temp-' + Date.now(),
    senderId: currentUser.uid,
    receiverId: selectedContact,
    text: text,
    timestamp: { seconds: Date.now() / 1000 },
    seen: false,
    isOptimistic: true
  };
  
  // Add optimistic message to chat
  addOptimisticMessage(tempMessage);
  
  // Ensure participants array is sorted for consistency
  const participants = [currentUser.uid, selectedContact].sort();
  try {
    // Add message document with participants array
    await addDoc(collection(db, 'messages'), {
      senderId: currentUser.uid,
      receiverId: selectedContact,
      participants: participants,
      text,
      timestamp: serverTimestamp(),
      seen: false
    });
    
    // Don't reload inbox immediately - let the real-time listener handle updates
    // Only reload if this is a new conversation
    if (!inboxDisplayCache.find(u => u.uid === selectedContact)) {
      setTimeout(() => { loadInbox(); }, 500);
    }
  } catch (error) {
    console.error('Failed to send message:', error);
    alert('Failed to send message. Please try again.');
    // Remove optimistic message on error
    removeOptimisticMessage(tempMessage.id);
  }
}

sendButton.onclick = sendMessage;

// Send message on Enter key
messageInput.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});


// Add CSS for chat-timestamp hover effect, delete icon/menu, inbox delete button, and Messenger-style modal
const style = document.createElement('style');
style.innerHTML = `
.group:hover .chat-timestamp { display: block !important; }
.chat-timestamp { display: none; color: #374151 !important; font-weight: 600; }
.group:hover .delete-msg-btn { display: block !important; }
.delete-msg-btn { display: none; position: absolute; top: 0.5rem; right: 0.5rem; }
.group:hover .mobile-delete-msg-btn { display: block !important; }
.mobile-delete-msg-btn { display: none; position: absolute; top: 0.25rem; right: 0.25rem; }
.delete-inbox-btn { transition: background 0.15s; }
.unsend-modal-overlay {
  position: fixed; z-index: 1000; left: 0; top: 0; width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center;
  transition: background 0.2s;
}
.unsend-modal {
  background: #fff; border-radius: 1.25rem; box-shadow: 0 8px 32px rgba(0,0,0,0.18);
  min-width: 340px; max-width: 90vw; padding: 2rem 1.5rem 1.5rem 1.5rem;
  font-family: 'Inter', 'Roboto', Arial, sans-serif;
  animation: modalPop 0.18s cubic-bezier(.4,1.6,.6,1) 1;
}
@keyframes modalPop {
  0% { transform: scale(0.95) translateY(30px); opacity: 0; }
  100% { transform: scale(1) translateY(0); opacity: 1; }
}
.unsend-modal h3 {
  font-size: 1.15rem; font-weight: 600; margin-bottom: 1.2rem; color: #222;
}
.unsend-modal .radio-group { margin-bottom: 1.2rem; }
.unsend-modal label { display: flex; align-items: center; gap: 0.7em; font-size: 1rem; margin-bottom: 0.7em; cursor: pointer; }
.unsend-modal input[type="radio"] { accent-color: #2563eb; width: 1.1em; height: 1.1em; }
.unsend-modal .modal-actions { display: flex; gap: 0.7em; justify-content: flex-end; }
.unsend-modal .modal-btn {
  padding: 0.5em 1.2em; border-radius: 0.7em; font-weight: 500; font-size: 1rem; border: none;
  transition: background 0.15s, color 0.15s;
}
.unsend-modal .modal-btn.cancel { background: #f3f4f6; color: #374151; }
.unsend-modal .modal-btn.confirm { background: #2563eb; color: #fff; }
.unsend-modal .modal-btn.confirm:disabled { background: #a5b4fc; color: #fff; cursor: not-allowed; }
`;
document.head.appendChild(style);

// Messenger-style Unsend/Delete Modal
function showUnsendModal(msg) {
  // Remove any existing modal
  const old = document.getElementById('unsend-modal-overlay');
  if (old) old.remove();
  // Modal overlay
  const overlay = document.createElement('div');
  overlay.className = 'unsend-modal-overlay';
  overlay.id = 'unsend-modal-overlay';
  overlay.tabIndex = -1;
  overlay.innerHTML = `
    <div class="unsend-modal">
      <h3>Delete message?</h3>
      <form>
        <div class="radio-group">
          <label><input type="radio" name="deleteType" value="forMe"> Delete for you</label>
          <label><input type="radio" name="deleteType" value="forEveryone"> Unsend for everyone</label>
        </div>
        <div class="modal-actions">
          <button type="button" class="modal-btn cancel">Cancel</button>
          <button type="submit" class="modal-btn confirm" disabled>Delete</button>
        </div>
      </form>
    </div>
  `;
  document.body.appendChild(overlay);
  // Focus overlay for accessibility
  overlay.focus();
  // Modal logic
  const form = overlay.querySelector('form');
  const confirmBtn = overlay.querySelector('.modal-btn.confirm');
  const cancelBtn = overlay.querySelector('.modal-btn.cancel');
  let selected = null;
  form.addEventListener('change', (e) => {
    if (e.target.name === 'deleteType') {
      selected = e.target.value;
      confirmBtn.disabled = false;
    }
  });
  cancelBtn.onclick = () => overlay.remove();
  overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };
  form.onsubmit = async (e) => {
    e.preventDefault();
    if (!selected) return;
    confirmBtn.disabled = true;
    // Debug: log currentUser and selectedContact
    console.log('[UnsendModal] currentUser:', currentUser);
    console.log('[UnsendModal] selectedContact:', selectedContact);
  const msgRef = doc(db, 'messages', msg.id);
    try {
      // Defensive: ensure currentUser.uid is set
      let uid = currentUser && currentUser.uid ? currentUser.uid : (sessionManager.getCurrentUser() && sessionManager.getCurrentUser().uid);
      if (!uid) {
        alert('User not authenticated. Please log in again.');
        overlay.remove();
        return;
      }
      if (selected === 'forMe') {
        // Use arrayUnion for safety in concurrent updates
        console.log('[UnsendModal] Hiding for UID:', uid);
        await updateDoc(msgRef, { hiddenFor: arrayUnion(uid) });
      } else if (selected === 'forEveryone') {
        console.log('[UnsendModal] Unsend for everyone');
        await updateDoc(msgRef, { unsentForEveryone: true });
      }
    } catch (err) {
      alert('Failed to delete message. Please try again.');
      console.error('[UnsendModal] Firestore error:', err);
    }
    overlay.remove();
    // Don't reload chat - the real-time listener will handle the changes
    // The message will automatically disappear from the chat due to the real-time updates
  };
}

// --- Optional: Sign Out Button ---
// Add a sign out button somewhere in your UI and call:
// signOut(auth);

// --- Mobile Inbox Delete Functionality ---
// This function enhances the mobile inbox with delete functionality
function enhanceMobileInbox() {
  const mobileInboxList = document.getElementById('mobileInboxList');
  if (!mobileInboxList) return;
  
  // Add delete functionality to existing mobile inbox items
  const mobileItems = mobileInboxList.querySelectorAll('li');
  mobileItems.forEach(li => {
    const deleteBtn = li.querySelector('.delete-inbox-btn');
    if (deleteBtn && !deleteBtn.hasAttribute('data-mobile-enhanced')) {
      deleteBtn.setAttribute('data-mobile-enhanced', 'true');
      deleteBtn.addEventListener('click', async function(e) {
        e.stopPropagation();
        if (confirm('Delete this conversation?')) {
          li.remove();
          // Remove from inboxDisplayCache so it stays hidden until reload
          const userId = li.getAttribute('data-uid');
          if (inboxDisplayCache) {
            const idx = inboxDisplayCache.findIndex(u => u.uid === userId);
            if (idx !== -1) inboxDisplayCache.splice(idx, 1);
          }
          // Hide all messages between currentUser and user for current user only
          try {
            const { collection, query, where, getDocs, updateDoc, doc, arrayUnion } = await import('https://www.gstatic.com/firebasejs/10.11.0/firebase-firestore.js');
            const messagesRef = collection(db, 'messages');
            // Query for messages where participants contains both user IDs
            const q = query(messagesRef, where('participants', 'array-contains', currentUser.uid));
            const snap = await getDocs(q);
            const toHide = snap.docs.filter(d => {
              const data = d.data();
              return Array.isArray(data.participants) && data.participants.includes(userId);
            });
            for (const d of toHide) {
              await updateDoc(doc(db, 'messages', d.id), { hiddenFor: arrayUnion(currentUser.uid) });
            }
          } catch (err) {
            alert('Failed to hide conversation.');
            console.error('Error hiding conversation:', err);
          }
        }
      });
    }
  });
}

// Run mobile inbox enhancement periodically to catch new items
setInterval(enhanceMobileInbox, 1000);

// --- Mobile Message Sending Functionality ---
function setupMobileMessageSending() {
  const mobileMessageInput = document.getElementById('mobileMessageInput');
  const mobileSendBtn = document.getElementById('mobileSendMessageBtn');
  
  if (!mobileMessageInput || !mobileSendBtn) return;
  
  // Mobile send button click handler
  mobileSendBtn.addEventListener('click', function() {
    sendMobileMessage();
  });
  
  // Mobile input enter key handler
  mobileMessageInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMobileMessage();
    }
  });
}

// Mobile message sending function
async function sendMobileMessage() {
  const mobileMessageInput = document.getElementById('mobileMessageInput');
  if (!mobileMessageInput) return;
  
  if (!selectedContact || !selectedContactName) {
    console.error('No contact selected', { selectedContact, selectedContactName });
    return alert('Please select a contact from the list first.');
  }
  
  const text = mobileMessageInput.value.trim();
  if (!text) return;
  
  // Clear input immediately for better UX
  mobileMessageInput.value = '';
  
  // Optimistic UI update - add message to mobile chat immediately
  const tempMessage = {
    id: 'temp-mobile-' + Date.now(),
    senderId: currentUser.uid,
    receiverId: selectedContact,
    text: text,
    timestamp: { seconds: Date.now() / 1000 },
    seen: false,
    isOptimistic: true
  };
  
  // Add optimistic message to mobile chat
  addOptimisticMobileMessage(tempMessage);
  
  // Ensure participants array is sorted for consistency
  const participants = [currentUser.uid, selectedContact].sort();
  try {
    // Add message document with participants array
    await addDoc(collection(db, 'messages'), {
      senderId: currentUser.uid,
      receiverId: selectedContact,
      participants: participants,
      text,
      timestamp: serverTimestamp(),
      seen: false
    });
    
    // Don't reload inbox immediately - let the real-time listener handle updates
    // Only reload if this is a new conversation
    if (!inboxDisplayCache.find(u => u.uid === selectedContact)) {
      setTimeout(() => { loadInbox(); }, 500);
    }
  } catch (error) {
    console.error('Failed to send message:', error);
    alert('Failed to send message. Please try again.');
    // Remove optimistic message on error
    removeOptimisticMobileMessage(tempMessage.id);
  }
}

// Initialize mobile message sending when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  setupMobileMessageSending();
});

// --- End chat.js ---
