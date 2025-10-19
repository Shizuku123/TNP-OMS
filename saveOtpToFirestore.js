// saveOtpToFirestore.js
// Usage: saveOtpToFirestore(userId, otp)
// Requires Firebase v11+ loaded in your page

import { doc, updateDoc } from 'https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js';
import { db } from './firebase.js'; // Assumes firebase.js exports the Firestore instance as 'db'

export async function saveOtpToFirestore(userId, otp) {
  const userRef = doc(db, 'users', userId);
  await updateDoc(userRef, {
    otp: otp,
    otpCreated: new Date().toISOString()
  });
}
