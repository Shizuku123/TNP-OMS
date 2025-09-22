// create-user.js
// This script creates a user in Firebase Authentication and Firestore with the required fields.
import { auth, db } from "./firebase.js";
import { createUserWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
import { doc, setDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

async function createUser() {
  const email = "guecoszanil4@gmail.com";
  const password = "admin123";
  const userData = {
    firstName: "Szanil",
    middleName: "Iquitan",
    lastName: "Gueco",
    emailAddress: email,
    contactNumber: "09171234567",
    username: "szanilgueco",
    photoString: "",
    gender: "Male",
    civilStatus: "Single",
    nationality: "Filipino",
    religion: "Catholic",
    placeOfBirth: "Angeles City",
    dateOfBirth: "1995-01-01",
    currentAddress: "Angeles City, Pampanga",
    assignedSchedule: "Day Shift",
    department: "IT",
    position: "Admin",
    supervisor: "None",
    employmentStatus: "Active",
    createAccount: "System",
    dateAdded: new Date().toISOString(),
    dateHired: "2020-01-01",
    role: "admin",
    isActive: true,
    isBlocked: false,
    isVerified: false
  };

  try {
    // Create user in Firebase Auth
    const userCredential = await createUserWithEmailAndPassword(auth, email, password);
    const user = userCredential.user;
    userData.uid = user.uid;
    // Save user data in Firestore
    await setDoc(doc(db, "users", user.uid), userData);
    console.log("User created and saved in Firestore:", userData);
  } catch (error) {
    console.error("Error creating user:", error);
  }
}

// Run the function
createUser();
