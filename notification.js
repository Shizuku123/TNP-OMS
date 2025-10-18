// notification.js - Firestore-based notification for new messages (per contact)
// Only shows notifications, does not display inbox UI

import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js";
import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
import { getFirestore, collection, query, where, getDocs } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

// Firebase config (copy from chat.js)
const firebaseConfig = {
  apiKey: "AIzaSyBKh0X9zMvJYwPmld1dngMBqkw-UWLGO7M",
  authDomain: "tnp-oms-2b2c7.firebaseapp.com",
  projectId: "tnp-oms-2b2c7",
  storageBucket: "tnp-oms-2b2c7.appspot.com",
  messagingSenderId: "101796900523",
  appId: "1:101796900523:web:ff0a5dbc63bb16131f91ee",
  measurementId: "G-H52TVZV37N"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const db = getFirestore(app);

function showNotification(title, message) {
  if (Notification.permission === "granted") {
    new Notification(title, {
      body: message,
      icon: 'tahanan-logo.jpg'
    });

    const sound = new Audio('notif.mp3');
    sound.play();

  }
}

// Request notification permission if not already granted
if (Notification.permission !== "granted") {
  Notification.requestPermission();
}

// Track last notified message per contact
let lastNotifiedByContact = {};

onAuthStateChanged(auth, (user) => {
  if (!user) return;
  setInterval(async () => {
    try {
      // Fetch all messages where user is receiver or sender
      const messagesRef = collection(db, 'messages');
      const qRecv = query(messagesRef, where('receiverId', '==', user.uid));
      const qSent = query(messagesRef, where('senderId', '==', user.uid));
      const [recvSnap, sentSnap] = await Promise.all([getDocs(qRecv), getDocs(qSent)]);
      const allMessages = [];
      recvSnap.forEach(d => allMessages.push({ ...d.data(), id: d.id }));
      sentSnap.forEach(d => allMessages.push({ ...d.data(), id: d.id }));

      // Group by contact (other user)
      const latestByContact = {};
      allMessages.forEach(msg => {
        const contactId = msg.senderId === user.uid ? msg.receiverId : msg.senderId;
        if (!latestByContact[contactId] || (msg.timestamp && msg.timestamp.seconds > (latestByContact[contactId].timestamp?.seconds || 0))) {
          latestByContact[contactId] = msg;
        }
      });

      // For each contact, if latest message is unseen and addressed to user, show notification
      Object.values(latestByContact).forEach(msg => {
        if (
          msg.receiverId === user.uid &&
          !msg.seen &&
          lastNotifiedByContact[msg.senderId] !== msg.id
        ) {
          const senderName = msg.senderName || 'Unknown';
          showNotification('New Message From ' + senderName, msg.text || 'You have a new message');
          lastNotifiedByContact[msg.senderId] = msg.id;
        }
      });
    } catch (err) {
      // Silently ignore errors
    }
  }, 5000);
});