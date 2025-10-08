// chat.js - Real-time Messenger Integration with Firebase
// --- Firebase SDK imports (for module environments) ---
// If using <script> tags, use CDN links for Firebase Auth and Firestore instead.
// For local dev, use: <script src="https://www.gstatic.com/firebasejs/10.11.0/firebase-app.js"></script>
//                     <script src="https://www.gstatic.com/firebasejs/10.11.0/firebase-auth.js"></script>
//                     <script src="https://www.gstatic.com/firebasejs/10.11.0/firebase-firestore.js"></script>


import { initializeApp } from "https://www.gstatic.com/firebasejs/10.11.0/firebase-app.js";
import { getAuth, onAuthStateChanged, signInWithEmailAndPassword, GoogleAuthProvider, signInWithPopup, signOut } from "https://www.gstatic.com/firebasejs/10.11.0/firebase-auth.js";
import { getFirestore, collection, query, where, orderBy, onSnapshot, addDoc, serverTimestamp, getDocs } from "https://www.gstatic.com/firebasejs/10.11.0/firebase-firestore.js";

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
const inboxList = document.querySelector('#inbox ul');
let chatWindow = document.getElementById('chat-messages');
const messageInput = document.querySelector('main input[placeholder="Type a message..."]');
const sendButton = document.querySelector('main button.bg-blue-500');
const attachButton = document.querySelector('main button[title="Attach file"]');

// Ensure chat window has proper classes
if (chatWindow) {
  chatWindow.className = 'flex-1 p-8 space-y-6 overflow-y-auto bg-gray-50 hidden-scrollbar';
}

// Create hidden file input for attachment
let fileInput = document.getElementById('chat-attachment-input');
if (!fileInput) {
  fileInput = document.createElement('input');
  fileInput.type = 'file';
  fileInput.id = 'chat-attachment-input';
  fileInput.style.display = 'none';
  document.body.appendChild(fileInput);
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
      console.log('User authenticated:', user.uid);
      loadInbox();
    } else {
      console.log('No authenticated user');
      showAuthUI();
    }
  });
});


// --- Contact Search Bar with Fuzzy Matching ---
let allContacts = [];
let inboxDisplayCache = [];

async function loadInbox() {
  if (!currentUser) return;
  try {
    // Fetch all users
    const usersSnap = await getDocs(query(collection(db, 'users'), where('role', 'in', ['staff', 'volunteer', 'admin'])));
    allContacts = [];
    usersSnap.forEach(doc => {
      const data = doc.data();
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
    receivedSnap.forEach(doc => allMessages.push({...doc.data(), id: doc.id}));
    sentSnap.forEach(doc => allMessages.push({...doc.data(), id: doc.id}));

    // Group by contact (other user)
    const latestByContact = {};
    allMessages.forEach(msg => {
      const contactId = msg.senderId === currentUser.uid ? msg.receiverId : msg.senderId;
      if (!latestByContact[contactId] || (msg.timestamp && msg.timestamp.seconds > latestByContact[contactId].timestamp.seconds)) {
        latestByContact[contactId] = msg;
      }
    });

    // Build inbox display items, skip messages hidden for current user
    const inboxDisplay = allContacts.map(user => {
      const latestMsg = latestByContact[user.uid];
      let status = 'Sent';
      if (latestMsg) {
        if (Array.isArray(latestMsg.hiddenFor) && latestMsg.hiddenFor.includes(currentUser.uid)) {
          return null; // skip hidden
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
        lastMessage: latestMsg ? latestMsg.text : '',
        lastTimestamp: latestMsg ? latestMsg.timestamp : null,
        messageId: latestMsg ? latestMsg.id : null,
        unseen: latestMsg ? (latestMsg.receiverId === currentUser.uid && !latestMsg.seen) : false,
        status: status
      };
    })
    // Only show users with a conversation and not hidden
    .filter(user => user && user.lastMessage && user.lastTimestamp);

    // Sort inbox by latest message timestamp (descending)
    inboxDisplay.sort((a, b) => {
      const ta = a.lastTimestamp ? a.lastTimestamp.seconds : 0;
      const tb = b.lastTimestamp ? b.lastTimestamp.seconds : 0;
      return tb - ta;
    });

    inboxDisplayCache = inboxDisplay;
    renderInbox(inboxDisplay, false);
    setupContactSearch();
  } catch (error) {
    console.error('Error loading inbox:', error);
    renderInbox([], true);
  }
    // End try block
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
  inboxList.innerHTML = '';
  if (error) {
    inboxList.innerHTML = `<li class="p-4 text-sm text-red-500">⚠️ Failed to load users. Check console.</li>`;
    return;
  }
  if (users.length === 0) {
    inboxList.innerHTML = `<li class="p-4 text-sm text-gray-500">No conversations yet.</li>`;
    return;
  }
  users.forEach((user, idx) => {
    if (currentUser && user.uid === currentUser.uid) return;
    const li = document.createElement('li');
    li.className = 'p-4 hover:bg-gray-50 cursor-pointer flex items-center gap-3';
    // Bold if unseen
    const messageClass = user.unseen ? 'font-bold text-gray-900' : 'font-normal text-gray-700';
    // Format timestamp
    let timeStr = '';
    if (user.lastTimestamp) {
      let dateObj = user.lastTimestamp.seconds ? new Date(user.lastTimestamp.seconds * 1000) : new Date(user.lastTimestamp);
      let options = { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      timeStr = `<span class='text-xs text-gray-400 ml-2'>${dateObj.toLocaleString('en-US', options)}</span>`;
    }
    // Delete button (trash icon)
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'delete-inbox-btn ml-2 p-1 rounded-full hover:bg-red-100 flex items-center justify-center';
    deleteBtn.title = 'Delete conversation';
    deleteBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>`;
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
    li.appendChild(document.createElement('img'));
    li.firstChild.src = user.photo;
    li.firstChild.alt = 'avatar';
    li.firstChild.className = 'w-10 h-10 rounded-full object-cover bg-gray-200 border';
    li.appendChild(contentDiv);
    li.appendChild(deleteBtn);
    // Click to select conversation
    contentDiv.onclick = async () => {
      selectContact(user.uid, user.fullName, user.photo, user.role);
      if (user.unseen && user.messageId) {
        const msgDocRef = collection(db, 'messages');
        try {
          const { doc, updateDoc } = await import('https://www.gstatic.com/firebasejs/10.11.0/firebase-firestore.js');
          await updateDoc(doc(msgDocRef, user.messageId), { seen: true });
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
    // Delete button logic (hide for current user only)
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
    inboxList.appendChild(li);
  });
}


// --- Helper: Capitalize role ---
function capitalizeRole(role) {
  if (!role) return '';
  return role.charAt(0).toUpperCase() + role.slice(1);
}

// --- Select Contact and Load Chat ---
function selectContact(contactId, contactName, contactPhoto, contactRole) {
  console.log('selectContact called with:', { contactId, contactName, contactPhoto, contactRole });
  
  if (!contactId) {
    console.error('No contact ID provided');
    return;
  }
  if (!currentUser) {
    console.error('No current user - cannot select contact');
    return;
  }
  
  selectedContact = contactId;
  selectedContactName = contactName || '';
  selectedContactPhoto = contactPhoto || 'user.png';
  selectedContactRole = contactRole || '';
  
  console.log('Contact selected:', { 
    selectedContact, 
    selectedContactName, 
    selectedContactPhoto, 
    selectedContactRole 
  });

  updateChatHeader();
  updateProfileInfo(contactName, contactPhoto, contactRole);

  if (unsubscribeChat) unsubscribeChat();

  // ✅ Show loading message immediately
  let chatWindow = document.getElementById('chat-messages');
  if (chatWindow) {
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

  console.log('Querying messages between', currentUser.uid, 'and', contactId);

  try {
    unsubscribeChat = onSnapshot(q, snapshot => {
      const messages = [];
      snapshot.forEach(doc => {
        const data = doc.data();
        // Check if message belongs to current conversation
        if (
          (data.senderId === currentUser.uid && data.receiverId === contactId) ||
          (data.senderId === contactId && data.receiverId === currentUser.uid)
        ) {
          console.log('Found message:', data);
          messages.push({...data, id: doc.id});
        }
      });

      if (!chatWindow) {
        console.error('Chat window element not found');
        return;
      }

      console.log('Rendering', messages.length, 'messages');

      if (messages.length === 0) {
        chatWindow.innerHTML = `<div class="text-center text-gray-400 mt-10">
          No messages yet. Start the conversation with ${contactName || "this user"}!
        </div>`;
      } else {
        renderChat(messages);
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
      }
    }, error => {
      console.error('Error listening to messages:', error);
      if (chatWindow) {
        chatWindow.innerHTML = `<div class="text-center text-red-500 mt-10">
          Error loading messages. Please try again.
        </div>`;
      }
    });
  } catch (error) {
    console.error('Error setting up message listener:', error);
    if (chatWindow) {
      chatWindow.innerHTML = `<div class="text-center text-red-500 mt-10">
        Error loading messages. Please try again.
      </div>`;
    }
  }
}


// Update Profile Info Panel
function updateProfileInfo(name, photo, role) {
  let profilePanel = document.getElementById('profile-info');
  if (!profilePanel) return; // Use existing panel from HTML
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

// --- Render Chat Window ---
function renderChat(messages) {
  console.log('Rendering chat messages:', messages);
  
  // Always re-query the chatWindow to ensure it's available
  let chatWindow = document.getElementById('chat-messages');
  if (!chatWindow) {
    console.error('Chat window not found');
    return;
  }
  
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
    messageInput.value = '';
    // Force reload inbox so new contact appears
    setTimeout(() => { loadInbox(); }, 500);
  } catch (error) {
    console.error('Failed to send message:', error);
    alert('Failed to send message. Please try again.');
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

// Attachment button functionality
attachButton.onclick = function() {
  fileInput.click();
};

fileInput.onchange = function(e) {
  const file = e.target.files[0];
  if (file) {
    alert('Selected file: ' + file.name);
    // TODO: Upload file to Firebase Storage and send as message
  }
};

// Add CSS for chat-timestamp hover effect, delete icon/menu, inbox delete button, and Messenger-style modal
const style = document.createElement('style');
style.innerHTML = `
.group:hover .chat-timestamp { display: block !important; }
.chat-timestamp { display: none; color: #374151 !important; font-weight: 600; }
.group:hover .delete-msg-btn { display: block !important; }
.delete-msg-btn { display: none; position: absolute; top: 0.5rem; right: 0.5rem; }
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
    const { doc, updateDoc, arrayUnion } = await import('https://www.gstatic.com/firebasejs/10.11.0/firebase-firestore.js');
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
    // Reload chat to reflect changes
    if (typeof selectContact === 'function' && selectedContact) {
      // Re-select the contact to reload chat
      console.log('[UnsendModal] Reloading chat for:', selectedContact, selectedContactName);
      selectContact(selectedContact, selectedContactName, selectedContactPhoto, selectedContactRole);
    }
  };
}

// --- Optional: Sign Out Button ---
// Add a sign out button somewhere in your UI and call:
// signOut(auth);

// --- End chat.js ---
