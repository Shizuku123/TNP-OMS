class JSONHandler {
  constructor() {
    this.dataPath = "data/"
    this.initializeData()
  }

  initializeData() {
    // Initialize default data if not exists
    if (!localStorage.getItem("users")) {
      const defaultUsers = [
        {
          id: "admin001",
          username: "admin",
          password: "admin123",
          name: "System Administrator",
          email: "admin@tnp.org",
          role: "admin",
          department: "Administration",
          dateAdded: new Date().toISOString(),
        },
      ]
      localStorage.setItem("users", JSON.stringify(defaultUsers))
    }

    if (!localStorage.getItem("children")) {
      localStorage.setItem("children", JSON.stringify([]))
    }

    if (!localStorage.getItem("staff")) {
      localStorage.setItem("staff", JSON.stringify([]))
    }

    if (!localStorage.getItem("volunteers")) {
      localStorage.setItem("volunteers", JSON.stringify([]))
    }

    if (!localStorage.getItem("attendance")) {
      localStorage.setItem("attendance", JSON.stringify([]))
    }

    if (!localStorage.getItem("systemActivities")) {
      localStorage.setItem("systemActivities", JSON.stringify([]))
    }

    if (!localStorage.getItem("systemSettings")) {
      const defaultSettings = {
        operatingHoursStart: "08:00",
        operatingHoursEnd: "17:00",
        lateThresholdMinutes: "15",
        organizationName: "Tahanan ng Pagmamahal",
        timezone: "Asia/Manila",
        requireLocationVerification: "yes",
        autoLogoutHours: "12",
        lastUpdated: new Date().toISOString(),
      }
      localStorage.setItem("systemSettings", JSON.stringify(defaultSettings))
    }
  }

  loadData(filename) {
    try {
      const data = localStorage.getItem(filename)
      return data ? JSON.parse(data) : null
    } catch (error) {
      console.error("Error loading data:", error)
      return null
    }
  }

  saveData(data, filename) {
    try {
      localStorage.setItem(filename, JSON.stringify(data))
      return true
    } catch (error) {
      console.error("Error saving data:", error)
      return false
    }
  }

  // User Management
  authenticateUser(username, password) {
    const users = JSON.parse(localStorage.getItem("users") || "[]")
    return users.find((user) => user.username === username && user.password === password)
  }

  getUserDetails(username) {
    const users = JSON.parse(localStorage.getItem("users") || "[]")
    const staff = JSON.parse(localStorage.getItem("staff") || "[]")
    const volunteers = JSON.parse(localStorage.getItem("volunteers") || "[]")

    const user = users.find((u) => u.username === username)
    if (!user) return null

    // Find additional details from staff or volunteer records
    const staffRecord = staff.find((s) => s.username === username)
    const volunteerRecord = volunteers.find((v) => v.username === username)

    return {
      ...user,
      ...(staffRecord || {}),
      ...(volunteerRecord || {}),
    }
  }

  updateAccount(username, updateData) {
    try {
      const users = JSON.parse(localStorage.getItem("users") || "[]")
      const userIndex = users.findIndex((u) => u.username === username)

      if (userIndex === -1) return false

      // Update user record
      if (updateData.name) users[userIndex].name = updateData.name
      if (updateData.email) users[userIndex].email = updateData.email
      if (updateData.password) users[userIndex].password = updateData.password

      localStorage.setItem("users", JSON.stringify(users))

      // Update staff record if exists
      if (users[userIndex].role === "staff") {
        const staff = JSON.parse(localStorage.getItem("staff") || "[]")
        const staffIndex = staff.findIndex((s) => s.username === username)
        if (staffIndex !== -1) {
          if (updateData.name) {
            const nameParts = updateData.name.split(" ")
            staff[staffIndex].firstName = nameParts[0] || ""
            staff[staffIndex].lastName = nameParts[nameParts.length - 1] || ""
          }
          if (updateData.email) staff[staffIndex].emailAddress = updateData.email
          localStorage.setItem("staff", JSON.stringify(staff))
        }
      }

      // Update volunteer record if exists
      if (users[userIndex].role === "volunteer") {
        const volunteers = JSON.parse(localStorage.getItem("volunteers") || "[]")
        const volunteerIndex = volunteers.findIndex((v) => v.username === username)
        if (volunteerIndex !== -1) {
          if (updateData.name) {
            const nameParts = updateData.name.split(" ")
            volunteers[volunteerIndex].firstName = nameParts[0] || ""
            volunteers[volunteerIndex].lastName = nameParts[nameParts.length - 1] || ""
          }
          if (updateData.email) volunteers[volunteerIndex].emailAddress = updateData.email
          localStorage.setItem("volunteers", JSON.stringify(volunteers))
        }
      }

      return true
    } catch (error) {
      console.error("Error updating account:", error)
      return false
    }
  }

  // Children Management
  getChildren() {
    return JSON.parse(localStorage.getItem("children") || "[]")
  }

  getChildById(childId) {
    const children = this.getChildren()
    return children.find((child) => child.childId === childId)
  }

  addChildRecord(data) {
    try {
      const children = this.getChildren()
      const childId = "CH" + Date.now().toString().slice(-6)

      const childRecord = {
        childId: childId,
        ...data,
        dateAdded: new Date().toISOString(),
      }

      children.push(childRecord)
      localStorage.setItem("children", JSON.stringify(children))
      return true
    } catch (error) {
      console.error("Error adding child record:", error)
      return false
    }
  }

  updateChild(childId, data) {
    try {
      const children = this.getChildren()
      const index = children.findIndex((child) => child.childId === childId)

      if (index === -1) return false

      children[index] = { ...children[index], ...data, lastModified: new Date().toISOString() }
      localStorage.setItem("children", JSON.stringify(children))
      return true
    } catch (error) {
      console.error("Error updating child:", error)
      return false
    }
  }

  deleteChild(childId) {
    try {
      const children = this.getChildren()
      const filteredChildren = children.filter((child) => child.childId !== childId)
      localStorage.setItem("children", JSON.stringify(filteredChildren))
      return true
    } catch (error) {
      console.error("Error deleting child:", error)
      return false
    }
  }

  // Staff Management
  getStaff() {
    return JSON.parse(localStorage.getItem("staff") || "[]")
  }

  getStaffById(staffId) {
    const staff = this.getStaff()
    return staff.find((s) => s.staffId === staffId)
  }

  addStaffRecord(data) {
    try {
      const staff = this.getStaff()
      const staffId = "ST" + Date.now().toString().slice(-6)

      const staffRecord = {
        staffId: staffId,
        ...data,
        dateAdded: new Date().toISOString(),
      }

      staff.push(staffRecord)
      localStorage.setItem("staff", JSON.stringify(staff))

      // Create user account if username and password provided
      if (data.username && data.password) {
        const users = JSON.parse(localStorage.getItem("users") || "[]")
        const userRecord = {
          id: staffId,
          username: data.username,
          password: data.password,
          name: `${data.firstName} ${data.lastName}`,
          email: data.emailAddress,
          role: "staff",
          department: data.department || "Staff",
          dateAdded: new Date().toISOString(),
        }
        users.push(userRecord)
        localStorage.setItem("users", JSON.stringify(users))
      }

      return true
    } catch (error) {
      console.error("Error adding staff record:", error)
      return false
    }
  }

  updateStaff(staffId, data) {
    try {
      const staff = this.getStaff()
      const index = staff.findIndex((s) => s.staffId === staffId)

      if (index === -1) return false

      staff[index] = { ...staff[index], ...data, lastModified: new Date().toISOString() }
      localStorage.setItem("staff", JSON.stringify(staff))

      // Update user account if exists
      if (data.username) {
        const users = JSON.parse(localStorage.getItem("users") || "[]")
        const userIndex = users.findIndex((u) => u.id === staffId)
        if (userIndex !== -1) {
          if (data.firstName && data.lastName) {
            users[userIndex].name = `${data.firstName} ${data.lastName}`
          }
          if (data.emailAddress) users[userIndex].email = data.emailAddress
          if (data.password) users[userIndex].password = data.password
          localStorage.setItem("users", JSON.stringify(users))
        }
      }

      return true
    } catch (error) {
      console.error("Error updating staff:", error)
      return false
    }
  }

  deleteStaff(staffId) {
    try {
      const staff = this.getStaff()
      const filteredStaff = staff.filter((s) => s.staffId !== staffId)
      localStorage.setItem("staff", JSON.stringify(filteredStaff))

      // Delete user account
      const users = JSON.parse(localStorage.getItem("users") || "[]")
      const filteredUsers = users.filter((u) => u.id !== staffId)
      localStorage.setItem("users", JSON.stringify(filteredUsers))

      return true
    } catch (error) {
      console.error("Error deleting staff:", error)
      return false
    }
  }

  // Volunteer Management
  getVolunteers() {
    return JSON.parse(localStorage.getItem("volunteers") || "[]")
  }

  getVolunteerById(volunteerId) {
    const volunteers = this.getVolunteers()
    return volunteers.find((v) => v.volunteerId === volunteerId)
  }

  addVolunteerRecord(data) {
    try {
      const volunteers = this.getVolunteers()
      const volunteerId = "VL" + Date.now().toString().slice(-6)

      const volunteerRecord = {
        volunteerId: volunteerId,
        ...data,
        dateAdded: new Date().toISOString(),
      }

      volunteers.push(volunteerRecord)
      localStorage.setItem("volunteers", JSON.stringify(volunteers))

      // Create user account if username and password provided
      if (data.username && data.password) {
        const users = JSON.parse(localStorage.getItem("users") || "[]")
        const userRecord = {
          id: volunteerId,
          username: data.username,
          password: data.password,
          name: `${data.firstName} ${data.lastName}`,
          email: data.emailAddress,
          role: "volunteer",
          department: data.preferredDepartment || "Volunteer",
          dateAdded: new Date().toISOString(),
        }
        users.push(userRecord)
        localStorage.setItem("users", JSON.stringify(users))
      }

      return true
    } catch (error) {
      console.error("Error adding volunteer record:", error)
      return false
    }
  }

  updateVolunteer(volunteerId, data) {
    try {
      const volunteers = this.getVolunteers()
      const index = volunteers.findIndex((v) => v.volunteerId === volunteerId)

      if (index === -1) return false

      volunteers[index] = { ...volunteers[index], ...data, lastModified: new Date().toISOString() }
      localStorage.setItem("volunteers", JSON.stringify(volunteers))

      // Update user account if exists
      if (data.username) {
        const users = JSON.parse(localStorage.getItem("users") || "[]")
        const userIndex = users.findIndex((u) => u.id === volunteerId)
        if (userIndex !== -1) {
          if (data.firstName && data.lastName) {
            users[userIndex].name = `${data.firstName} ${data.lastName}`
          }
          if (data.emailAddress) users[userIndex].email = data.emailAddress
          if (data.password) users[userIndex].password = data.password
          localStorage.setItem("users", JSON.stringify(users))
        }
      }

      return true
    } catch (error) {
      console.error("Error updating volunteer:", error)
      return false
    }
  }

  deleteVolunteer(volunteerId) {
    try {
      const volunteers = this.getVolunteers()
      const filteredVolunteers = volunteers.filter((v) => v.volunteerId !== volunteerId)
      localStorage.setItem("volunteers", JSON.stringify(filteredVolunteers))

      // Delete user account
      const users = JSON.parse(localStorage.getItem("users") || "[]")
      const filteredUsers = users.filter((u) => u.id !== volunteerId)
      localStorage.setItem("users", JSON.stringify(filteredUsers))

      return true
    } catch (error) {
      console.error("Error deleting volunteer:", error)
      return false
    }
  }

  // Attendance Management
  getAttendance() {
    return JSON.parse(localStorage.getItem("attendance") || "[]")
  }

  addAttendanceRecord(data) {
    try {
      const attendance = this.getAttendance()
      const attendanceId = "ATT" + Date.now().toString().slice(-6)

      const attendanceRecord = {
        attendanceId: attendanceId,
        ...data,
        timestamp: new Date().toISOString(),
      }

      attendance.push(attendanceRecord)
      localStorage.setItem("attendance", JSON.stringify(attendance))
      return true
    } catch (error) {
      console.error("Error adding attendance record:", error)
      return false
    }
  }

  updateAttendanceRecord(attendanceId, data) {
    try {
      const attendance = this.getAttendance()
      const index = attendance.findIndex((a) => a.attendanceId === attendanceId)

      if (index === -1) return false

      attendance[index] = { ...attendance[index], ...data }
      localStorage.setItem("attendance", JSON.stringify(attendance))
      return true
    } catch (error) {
      console.error("Error updating attendance:", error)
      return false
    }
  }

  // System Activity Logging
  logActivity(userId, username, action, details) {
    try {
      const activities = JSON.parse(localStorage.getItem("systemActivities") || "[]")
      const activity = {
        id: "ACT" + Date.now().toString().slice(-6),
        userId: userId,
        username: username,
        action: action,
        details: details,
        timestamp: new Date().toISOString(),
        ipAddress: "localhost",
      }

      activities.unshift(activity) // Add to beginning

      // Keep only last 1000 activities
      if (activities.length > 1000) {
        activities.splice(1000)
      }

      localStorage.setItem("systemActivities", JSON.stringify(activities))
      return true
    } catch (error) {
      console.error("Error logging activity:", error)
      return false
    }
  }

  getSystemActivities(limit = 100) {
    const activities = JSON.parse(localStorage.getItem("systemActivities") || "[]")
    return activities.slice(0, limit)
  }

  // System Settings
  getSystemSettings() {
    return JSON.parse(localStorage.getItem("systemSettings") || "{}")
  }

  updateSystemSettings(settings) {
    try {
      const currentSettings = this.getSystemSettings()
      const updatedSettings = {
        ...currentSettings,
        ...settings,
        lastUpdated: new Date().toISOString(),
      }
      localStorage.setItem("systemSettings", JSON.stringify(updatedSettings))
      return true
    } catch (error) {
      console.error("Error updating system settings:", error)
      return false
    }
  }

  // Utility Methods
  exportData() {
    return {
      users: JSON.parse(localStorage.getItem("users") || "[]"),
      children: JSON.parse(localStorage.getItem("children") || "[]"),
      staff: JSON.parse(localStorage.getItem("staff") || "[]"),
      volunteers: JSON.parse(localStorage.getItem("volunteers") || "[]"),
      attendance: JSON.parse(localStorage.getItem("attendance") || "[]"),
      systemActivities: JSON.parse(localStorage.getItem("systemActivities") || "[]"),
      systemSettings: JSON.parse(localStorage.getItem("systemSettings") || "{}"),
      exportDate: new Date().toISOString(),
    }
  }

  importData(data) {
    try {
      if (data.users) localStorage.setItem("users", JSON.stringify(data.users))
      if (data.children) localStorage.setItem("children", JSON.stringify(data.children))
      if (data.staff) localStorage.setItem("staff", JSON.stringify(data.staff))
      if (data.volunteers) localStorage.setItem("volunteers", JSON.stringify(data.volunteers))
      if (data.attendance) localStorage.setItem("attendance", JSON.stringify(data.attendance))
      if (data.systemActivities) localStorage.setItem("systemActivities", JSON.stringify(data.systemActivities))
      if (data.systemSettings) localStorage.setItem("systemSettings", JSON.stringify(data.systemSettings))
      return true
    } catch (error) {
      console.error("Error importing data:", error)
      return false
    }
  }
}

// Initialize global instance
window.jsonHandler = new JSONHandler()
