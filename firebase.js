// firebase.js
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js"
import { getAuth } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js"
import {
  getFirestore,
  collection,
  getDocs,
  setDoc,
  addDoc,
  deleteDoc,
  doc,
  updateDoc,
  getDoc,
  query,
  orderBy,
  where,
  limit,
} from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js"
import {
  getStorage,
  ref,
  uploadBytes,
  getDownloadURL,
} from "https://www.gstatic.com/firebasejs/11.0.1/firebase-storage.js"

// Your Firebase config
// const firebaseConfig = {
//   apiKey: "AIzaSyB77G8wkD94q6AGGbDNtusEFUEc8X708ug",
//   authDomain: "tnp-oms-e4036.firebaseapp.com",
//   projectId: "tnp-oms-e4036",
//   storageBucket: "tnp-oms-e4036.appspot.com",
//   messagingSenderId: "822922660224",
//   appId: "1:822922660224:web:846c38ace3cc4787ee9965"
// };

const firebaseConfig = {
  apiKey: "AIzaSyBKh0X9zMvJYwPmld1dngMBqkw-UWLGO7M",
  authDomain: "tnp-oms-2b2c7.firebaseapp.com",
  projectId: "tnp-oms-2b2c7",
  storageBucket: "tnp-oms-2b2c7.appspot.com",
  messagingSenderId: "101796900523",
  appId: "1:101796900523:web:ff0a5dbc63bb16131f91ee",
  measurementId: "G-H52TVZV37N",
}

// Initialize Firebase
const app = initializeApp(firebaseConfig)

// Firestore, Storage, and Auth instances
export const db = getFirestore(app)
export const storage = getStorage(app)
export const auth = getAuth(app)

// Export all Firestore and Storage helpers needed for your app
export {
  collection,
  getDocs,
  getDoc,
  setDoc,
  addDoc,
  deleteDoc,
  doc,
  updateDoc,
  query,
  where,
  orderBy,
  ref,
  uploadBytes,
  getDownloadURL,
  limit,
  getAuth,
}
