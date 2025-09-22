// Script to automatically cleanup expired deleted records
// This can be run periodically to permanently delete records after 14 days

function cleanupExpiredRecords() {
  console.log("[v0] Starting cleanup of expired deleted records...")

  try {
    // Load deleted records
    const fs = require("fs")
    const path = "./data/children-deleted-records.json"

    let deletedData = { deletedRecords: [] }
    if (fs.existsSync(path)) {
      deletedData = JSON.parse(fs.readFileSync(path, "utf8"))
    }

    const now = new Date()
    const beforeCount = deletedData.deletedRecords.length

    // Filter out expired records
    deletedData.deletedRecords = deletedData.deletedRecords.filter((record) => {
      const expiryDate = new Date(record.expiryDate)
      const isExpired = expiryDate <= now

      if (isExpired) {
        console.log(
          `[v0] Permanently deleting expired record: ${record.firstName} ${record.lastName} (ID: ${record.childId})`,
        )
      }

      return !isExpired
    })

    const afterCount = deletedData.deletedRecords.length
    const removedCount = beforeCount - afterCount

    // Save updated deleted records
    fs.writeFileSync(path, JSON.stringify(deletedData, null, 2))

    console.log(`[v0] Cleanup completed. Removed ${removedCount} expired records. ${afterCount} records remaining.`)

    return {
      success: true,
      removedCount,
      remainingCount: afterCount,
    }
  } catch (error) {
    console.error("[v0] Error during cleanup:", error)
    return {
      success: false,
      error: error.message,
    }
  }
}

// Run cleanup
const result = cleanupExpiredRecords()
console.log("[v0] Cleanup result:", result)
