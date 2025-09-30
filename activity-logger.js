// activity-logger.js - Shared utility for logging system activities to Firestore
import { db, collection, addDoc } from "./firebase.js"

/**
 * Logs a system activity to Firestore
 * @param {string} action - The action performed (e.g., "User Login", "Updated System Settings")
 * @param {string} details - Additional details about the action
 * @param {Object} user - The current user object with username, role, staffId/adminId
 * @returns {Promise<void>}
 */
export async function logActivity(action, details, user) {
  try {
    if (!user || !user.username) {
      console.error("Cannot log activity: user information missing")
      return
    }

    let userId
    if (user.role === "staff" && user.staffId) {
      userId = user.staffId
    } else if (user.role === "admin" && user.adminId) {
      userId = user.adminId
    } else {
      userId = user.username
    }

    // Format timestamp to Philippines timezone in "YYYY-MM-DD HH:mm:ss"
    const now = new Date()
    const options = { timeZone: "Asia/Manila", hour12: false }
    const phDate = new Intl.DateTimeFormat("en-CA", {
      ...options,
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    }).format(now)

    const timestamp = phDate.replace(",", "") // "2025-09-30 20:45:12"

    const activity = {
      userId: userId,
      username: user.username,
      action: action,
      details: details,
      timestamp: timestamp, // formatted for PH time
      ipAddress: "127.0.0.1", // replace with actual IP in production
      createdAt: new Date(),  // Firestore UTC timestamp for sorting
    }

    await addDoc(collection(db, "systemActivities"), activity)
    console.log("Activity logged successfully:", action)
  } catch (error) {
    console.error("Error logging activity:", error)
  }
}
