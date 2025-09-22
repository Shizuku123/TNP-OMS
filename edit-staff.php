<?php
require_once 'includes/session.php';
require_once 'includes/XMLHandler.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Only admin and staff can edit records
if (!in_array($_SESSION['user_role'], ['admin', 'staff'])) {
    header('Location: homepage.php');
    exit();
}

$xmlHandler = new XMLHandler();
$staffId = $_GET['id'] ?? '';
$staff = null;

if ($staffId) {
    $staff = $xmlHandler->getStaffById($staffId);
}

if (!$staff) {
    $_SESSION['error'] = 'Staff record not found.';
    header('Location: staff-records.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff Record - Tahanan ng Pagmamahal OMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'red-primary': '#dc2626',
                        'red-secondary': '#ef4444',
                        'red-light': '#fef2f2'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="staff-records.php" class="flex items-center text-gray-600 hover:text-red-primary">
                        <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Staff Records
                    </a>
                </div>
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Edit Staff Record</h1>
                <div></div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <form action="update-staff.php" method="POST" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="staffId" value="<?php echo htmlspecialchars($staff['staffId']); ?>">
            
            <!-- Personal Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Personal Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label for="staffId" class="block text-sm font-medium text-gray-700 mb-2">Staff ID</label>
                        <input type="text" id="staffIdDisplay" value="<?php echo htmlspecialchars($staff['staffId']); ?>" readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none">
                    </div>
                    <div>
                        <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($staff['firstName'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="middleName" class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                        <input type="text" id="middleName" name="middleName" value="<?php echo htmlspecialchars($staff['middleName'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($staff['lastName'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="gender" value="Male" <?php echo ($staff['gender'] ?? '') === 'Male' ? 'checked' : ''; ?> required class="text-red-primary focus:ring-red-primary">
                                <span class="ml-2">Male</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="gender" value="Female" <?php echo ($staff['gender'] ?? '') === 'Female' ? 'checked' : ''; ?> required class="text-red-primary focus:ring-red-primary">
                                <span class="ml-2">Female</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label for="dateOfBirth" class="block text-sm font-medium text-gray-700 mb-2">Date of Birth *</label>
                        <input type="date" id="dateOfBirth" name="dateOfBirth" value="<?php echo htmlspecialchars($staff['dateOfBirth'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="placeOfBirth" class="block text-sm font-medium text-gray-700 mb-2">Place of Birth</label>
                        <input type="text" id="placeOfBirth" name="placeOfBirth" value="<?php echo htmlspecialchars($staff['placeOfBirth'] ?? ''); ?>" placeholder="Municipality, City, Province"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="nationality" class="block text-sm font-medium text-gray-700 mb-2">Nationality</label>
                        <input type="text" id="nationality" name="nationality" value="<?php echo htmlspecialchars($staff['nationality'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="religion" class="block text-sm font-medium text-gray-700 mb-2">Religion</label>
                        <input type="text" id="religion" name="religion" value="<?php echo htmlspecialchars($staff['religion'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="civilStatus" class="block text-sm font-medium text-gray-700 mb-2">Civil Status</label>
                        <select id="civilStatus" name="civilStatus"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                            <option value="">Select status</option>
                            <option value="Single" <?php echo ($staff['civilStatus'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                            <option value="Married" <?php echo ($staff['civilStatus'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                            <option value="Separated" <?php echo ($staff['civilStatus'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                            <option value="Divorced" <?php echo ($staff['civilStatus'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                            <option value="Widowed" <?php echo ($staff['civilStatus'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <label for="currentAddress" class="block text-sm font-medium text-gray-700 mb-2">Current Address *</label>
                        <textarea id="currentAddress" name="currentAddress" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary"><?php echo htmlspecialchars($staff['currentAddress'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label for="contactNumber" class="block text-sm font-medium text-gray-700 mb-2">Contact Number *</label>
                        <input type="tel" id="contactNumber" name="contactNumber" value="<?php echo htmlspecialchars($staff['contactNumber'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="emailAddress" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <input type="email" id="emailAddress" name="emailAddress" value="<?php echo htmlspecialchars($staff['emailAddress'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                </div>
            </div>

            <!-- Photo Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Photo</h2>
                <div class="flex flex-col md:flex-row md:items-start space-y-4 md:space-y-0 md:space-x-6">
                    <div class="flex-shrink-0">
                        <img id="currentPhoto" src="<?php echo htmlspecialchars($staff['photoData'] ?? '/placeholder.svg?height=200&width=200'); ?>" alt="Current Photo" 
                             class="h-32 w-32 md:h-48 md:w-48 rounded-lg object-cover border">
                    </div>
                    <div class="flex-1">
                        <label for="photo" class="block text-sm font-medium text-gray-700 mb-2">Update Photo (Optional)</label>
                        <input type="file" id="photo" name="photo" accept="image/*"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                        <p class="mt-2 text-sm text-gray-500">Leave empty to keep current photo. Supported formats: JPG, PNG, GIF (Max 5MB)</p>
                    </div>
                </div>
            </div>

            <!-- Employment Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Employment Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                        <select id="position" name="position" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                            <option value="">Select position</option>
                            <option value="Director" <?php echo ($staff['position'] ?? '') === 'Director' ? 'selected' : ''; ?>>Director</option>
                            <option value="Assistant Director" <?php echo ($staff['position'] ?? '') === 'Assistant Director' ? 'selected' : ''; ?>>Assistant Director</option>
                            <option value="Social Worker" <?php echo ($staff['position'] ?? '') === 'Social Worker' ? 'selected' : ''; ?>>Social Worker</option>
                            <option value="Child Care Worker" <?php echo ($staff['position'] ?? '') === 'Child Care Worker' ? 'selected' : ''; ?>>Child Care Worker</option>
                            <option value="Teacher" <?php echo ($staff['position'] ?? '') === 'Teacher' ? 'selected' : ''; ?>>Teacher</option>
                            <option value="Nurse" <?php echo ($staff['position'] ?? '') === 'Nurse' ? 'selected' : ''; ?>>Nurse</option>
                            <option value="Cook" <?php echo ($staff['position'] ?? '') === 'Cook' ? 'selected' : ''; ?>>Cook</option>
                            <option value="Security Guard" <?php echo ($staff['position'] ?? '') === 'Security Guard' ? 'selected' : ''; ?>>Security Guard</option>
                            <option value="Maintenance" <?php echo ($staff['position'] ?? '') === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="Administrative Assistant" <?php echo ($staff['position'] ?? '') === 'Administrative Assistant' ? 'selected' : ''; ?>>Administrative Assistant</option>
                            <option value="Counselor" <?php echo ($staff['position'] ?? '') === 'Counselor' ? 'selected' : ''; ?>>Counselor</option>
                        </select>
                    </div>
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department / Unit</label>
                        <select id="department" name="department"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                            <option value="">Select department</option>
                            <option value="Administration" <?php echo ($staff['department'] ?? '') === 'Administration' ? 'selected' : ''; ?>>Administration</option>
                            <option value="Child Care" <?php echo ($staff['department'] ?? '') === 'Child Care' ? 'selected' : ''; ?>>Child Care</option>
                            <option value="Education" <?php echo ($staff['department'] ?? '') === 'Education' ? 'selected' : ''; ?>>Education</option>
                            <option value="Health Services" <?php echo ($staff['department'] ?? '') === 'Health Services' ? 'selected' : ''; ?>>Health Services</option>
                            <option value="Social Services" <?php echo ($staff['department'] ?? '') === 'Social Services' ? 'selected' : ''; ?>>Social Services</option>
                            <option value="Kitchen" <?php echo ($staff['department'] ?? '') === 'Kitchen' ? 'selected' : ''; ?>>Kitchen</option>
                            <option value="Maintenance" <?php echo ($staff['department'] ?? '') === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="Security" <?php echo ($staff['department'] ?? '') === 'Security' ? 'selected' : ''; ?>>Security</option>
                        </select>
                    </div>
                    <div>
                        <label for="employmentStatus" class="block text-sm font-medium text-gray-700 mb-2">Employment Status</label>
                        <select id="employmentStatus" name="employmentStatus"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                            <option value="">Select status</option>
                            <option value="Regular" <?php echo ($staff['employmentStatus'] ?? '') === 'Regular' ? 'selected' : ''; ?>>Regular</option>
                            <option value="Contractual" <?php echo ($staff['employmentStatus'] ?? '') === 'Contractual' ? 'selected' : ''; ?>>Contractual</option>
                            <option value="Part-time" <?php echo ($staff['employmentStatus'] ?? '') === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                            <option value="Probationary" <?php echo ($staff['employmentStatus'] ?? '') === 'Probationary' ? 'selected' : ''; ?>>Probationary</option>
                        </select>
                    </div>
                    <div>
                        <label for="dateHired" class="block text-sm font-medium text-gray-700 mb-2">Date Hired</label>
                        <input type="date" id="dateHired" name="dateHired" value="<?php echo htmlspecialchars($staff['dateHired'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="assignedSchedule" class="block text-sm font-medium text-gray-700 mb-2">Assigned Schedule</label>
                        <input type="text" id="assignedSchedule" name="assignedSchedule" value="<?php echo htmlspecialchars($staff['assignedSchedule'] ?? ''); ?>" placeholder="e.g., Monday-Friday 8AM-5PM"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="supervisor" class="block text-sm font-medium text-gray-700 mb-2">Supervisor</label>
                        <input type="text" id="supervisor" name="supervisor" value="<?php echo htmlspecialchars($staff['supervisor'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="salary" class="block text-sm font-medium text-gray-700 mb-2">Salary</label>
                        <input type="number" id="salary" name="salary" value="<?php echo htmlspecialchars($staff['salary'] ?? ''); ?>" min="0" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Account Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($staff['username'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" id="password" name="password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                        <p class="mt-1 text-sm text-gray-500">Leave empty to keep current password</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4 pt-6">
                <a href="staff-records.php" class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors text-center">
                    Cancel
                </a>
                <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-red-primary text-white rounded-md hover:bg-red-700 transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <script>
        // Photo preview
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('currentPhoto').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
