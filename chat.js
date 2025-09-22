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
const chatWindow = document.querySelector('main .flex-1.p-4');
const messageInput = document.querySelector('main input[type="text"]');
const sendButton = document.querySelector('main button');

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
    } else {
      showAuthUI();
    }
  });
});


// --- Load Inbox with Users (Real-Time) ---
function loadInbox() {
  const userQuery = query(
    collection(db, 'users'),
    where('role', 'in', ['staff', 'volunteer', 'admin'])
  );

  onSnapshot(userQuery, snapshot => {
    if (snapshot.empty) {
      renderInbox([]); // No users found
      return;
    }

    const users = [];
    snapshot.forEach(doc => {
      const data = doc.data();
      users.push({
        uid: data.uid,
        fullName: `${data.firstName || ''} ${data.lastName || ''}`.trim(),
        role: data.role,
        email: data.emailAddress || '',
        department: data.department || '',
        position: data.position || '',
        photo: data.photoString || 'user.png'
      });
    });
    renderInbox(users);
  }, error => {
    console.error("Error fetching users:", error);
    renderInbox([], true); // Pass error flag
  });
}

// --- Render Inbox List with Users ---
function renderInbox(users, error = false) {
  inboxList.innerHTML = '';

  if (error) {
    inboxList.innerHTML = `
      <li class="p-4 text-sm text-red-500">⚠️ Failed to load users. Check console.</li>
    `;
    return;
  }

  if (users.length === 0) {
    inboxList.innerHTML = `
      <li class="p-4 text-sm text-gray-500">No users found.</li>
    `;
    return;
  }

  users.forEach(user => {
    const li = document.createElement('li');
    li.className = 'p-4 hover:bg-gray-50 cursor-pointer flex items-center gap-3';
    li.innerHTML = `
      <img src="${user.photo}" alt="avatar" class="w-10 h-10 rounded-full object-cover bg-gray-200 border" />
      <div>
        <p class="font-medium text-gray-900">${user.fullName}</p>
        <p class="text-sm text-gray-500">${capitalizeRole(user.role)}</p>
      </div>
    `;
  li.onclick = () => selectContact(user.uid, user.fullName, user.photo, user.role);
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
  selectedContact = contactId;
  selectedContactName = contactName || '';
  selectedContactPhoto = contactPhoto || 'user.png';
  selectedContactRole = contactRole || '';
  updateChatHeader();
  if (unsubscribeChat) unsubscribeChat();
  // Query messages between currentUser and selectedContact
  const q = query(collection(db, 'messages'),
    where('senderId', 'in', [currentUser.uid, contactId]),
    where('receiverId', 'in', [currentUser.uid, contactId]),
    orderBy('timestamp'));
  unsubscribeChat = onSnapshot(q, snapshot => {
    const messages = [];
    snapshot.forEach(doc => messages.push(doc.data()));
    renderChat(messages);
  });
}

// --- Update Chat Header with Selected Name ---
function updateChatHeader() {
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
  chatWindow.innerHTML = '';
  messages.forEach(msg => {
    const div = document.createElement('div');
    if (msg.senderId === currentUser.uid) {
      div.className = 'flex justify-end items-start space-x-2';
      div.innerHTML = `<div class="bg-blue-500 px-4 py-2 rounded-2xl text-white max-w-xs">${msg.text}</div>`;
    } else {
      div.className = 'flex items-start space-x-2';
      div.innerHTML = `<div class="bg-gray-100 px-4 py-2 rounded-2xl text-gray-800 max-w-xs">${msg.text}</div>`;
    }
    chatWindow.appendChild(div);
  });  
  chatWindow.scrollTop = chatWindow.scrollHeight;
}

// --- Send Message ---
sendButton.onclick = async () => {
  if (!selectedContact) return alert('Select a contact first.');
  const text = messageInput.value.trim();
  if (!text) return;
  await addDoc(collection(db, 'messages'), {
    senderId: currentUser.uid,
    receiverId: selectedContact,
    text,
    timestamp: serverTimestamp()
  });
  messageInput.value = '';
};

// --- Optional: Sign Out Button ---
// Add a sign out button somewhere in your UI and call:
// signOut(auth);

// --- End chat.js ---
