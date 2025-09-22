class SessionManager {
  constructor() {
    this.currentUser = null
    this.loadSession()
  }

  login(username, password) {
    const user = window.jsonHandler.authenticateUser(username, password)
    if (user) {
      this.currentUser = user
      sessionStorage.setItem("currentUser", JSON.stringify(user))
      window.jsonHandler.logActivity(user.id, user.username, "Login", "User logged in successfully")
      return true
    }
    return false
  }

  logout() {
    if (this.currentUser) {
      window.jsonHandler.logActivity(this.currentUser.id, this.currentUser.username, "Logout", "User logged out")
    }
    this.currentUser = null
    sessionStorage.removeItem("currentUser")
  }

  loadSession() {
    const userData = sessionStorage.getItem("currentUser")
    if (userData) {
      this.currentUser = JSON.parse(userData)
    }
  }

  getCurrentUser() {
    return this.currentUser
  }

  isLoggedIn() {
    return this.currentUser !== null
  }

  hasRole(role) {
    return this.currentUser && this.currentUser.role === role
  }

  hasAnyRole(roles) {
    return this.currentUser && roles.includes(this.currentUser.role)
  }

  requireLogin() {
    if (!this.isLoggedIn()) {
      window.location.href = "login.html"
      return false
    }
    return true
  }

  requireRole(role) {
    if (!this.requireLogin()) return false
    if (!this.hasRole(role)) {
      alert("Access denied. Insufficient permissions.")
      window.location.href = "homepage.html"
      return false
    }
    return true
  }

  requireAnyRole(roles) {
    if (!this.requireLogin()) return false
    if (!this.hasAnyRole(roles)) {
      alert("Access denied. Insufficient permissions.")
      window.location.href = "homepage.html"
      return false
    }
    return true
  }
}

// Initialize global session manager
window.sessionManager = new SessionManager()
