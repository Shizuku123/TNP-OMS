import { auth, db } from "./firebase.js"
import {
  createUserWithEmailAndPassword,
  signInWithEmailAndPassword,
  signOut,
  onAuthStateChanged,
  sendEmailVerification,
  reload,
} from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js"
import { doc, setDoc, getDoc } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js"