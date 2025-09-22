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
$volunteerId = $_GET['id'] ?? '';
$volunteer = null;

if ($volunteerId) {
    $volunteer = $xmlHandler->getVolunteerById($volunteerId);
}

if (!$volunteer) {
    $_SESSION['error'] = 'Volunteer record not found.';
    header('Location: volunteer-records.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Volunteer Record - Tahanan ng Pagmamahal OMS</title>
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
                    <a href="volunteer-records.php" class="flex items-center text-gray-600 hover:text-red-primary">
                        <svg class="h-6 w-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Volunteer Records
                    </a>
                </div>
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Edit Volunteer Record</h1>
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
        <form action="update-volunteer.php" method="POST" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="volunteerId" value="<?php echo htmlspecialchars($volunteer['volunteerId']); ?>">
            
            <!-- Personal Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Personal Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label for="volunteerId" class="block text-sm font-medium text-gray-700 mb-2">Volunteer ID</label>
                        <input type="text" id="volunteerIdDisplay" value="<?php echo htmlspecialchars($volunteer['volunteerId']); ?>" readonly
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 focus:outline-none">
                    </div>
                    <div>
                        <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($volunteer['firstName'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="middleName" class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                        <input type="text" id="middleName" name="middleName" value="<?php echo htmlspecialchars($volunteer['middleName'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($volunteer['lastName'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender *</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="gender" value="Male" <?php echo ($volunteer['gender'] ?? '') === 'Male' ? 'checked' : ''; ?> required class="text-red-primary focus:ring-red-primary">
                                <span class="ml-2">Male</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="gender" value="Female" <?php echo ($volunteer['gender'] ?? '') === 'Female' ? 'checked' : ''; ?> required class="text-red-primary focus:ring-red-primary">
                                <span class="ml-2">Female</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label for="dateOfBirth" class="block text-sm font-medium text-gray-700 mb-2">Date of Birth *</label>
                        <input type="date" id="dateOfBirth" name="dateOfBirth" value="<?php echo htmlspecialchars($volunteer['dateOfBirth'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="placeOfBirth" class="block text-sm font-medium text-gray-700 mb-2">Place of Birth</label>
                        <input type="text" id="placeOfBirth" name="placeOfBirth" value="<?php echo htmlspecialchars($volunteer['placeOfBirth'] ?? ''); ?>" placeholder="Municipality, City, Province"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="nationality" class="block text-sm font-medium text-gray-700 mb-2">Nationality</label>
                        <input type="text" id="nationality" name="nationality" value="<?php echo htmlspecialchars($volunteer['nationality'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="religion" class="block text-sm font-medium text-gray-700 mb-2">Religion</label>
                        <input type="text" id="religion" name="religion" value="<?php echo htmlspecialchars($volunteer['religion'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="civilStatus" class="block text-sm font-medium text-gray-700 mb-2">Civil Status</label>
                        <select id="civilStatus" name="civilStatus"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                            <option value="">Select status</option>
                            <option value="Single" <?php echo ($volunteer['civilStatus'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                            <option value="Married" <?php echo ($volunteer['civilStatus'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                            <option value="Separated" <?php echo ($volunteer['civilStatus'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                            <option value="Divorced" <?php echo ($volunteer['civilStatus'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                            <option value="Widowed" <?php echo ($volunteer['civilStatus'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <label for="currentAddress" class="block text-sm font-medium text-gray-700 mb-2">Current Address *</label>
                        <textarea id="currentAddress" name="currentAddress" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary"><?php echo htmlspecialchars($volunteer['currentAddress'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label for="contactNumber" class="block text-sm font-medium text-gray-700 mb-2">Contact Number *</label>
                        <input type="tel" id="contactNumber" name="contactNumber" value="<?php echo htmlspecialchars($volunteer['contactNumber'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="emailAddress" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <input type="email" id="emailAddress" name="emailAddress" value="<?php echo htmlspecialchars($volunteer['emailAddress'] ?? ''); ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                </div>
            </div>

            <!-- Photo Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Photo</h2>
                <div class="flex flex-col md:flex-row md:items-start space-y-4 md:space-y-0 md:space-x-6">
                    <div class="flex-shrink-0">
                        <img id="currentPhoto" src="<?php echo htmlspecialchars($volunteer['photoData'] ?? '/placeholder.svg?height=200&width=200'); ?>" alt="Current Photo" 
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

            <!-- Volunteer Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Volunteer Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label for="preferredDepartment" class="block text-sm font-medium text-gray-700 mb-2">Preferred Department</label>
                        <select id="preferredDepartment" name="preferredDepartment"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                            <option value="">Select department</option>
                            <option value="Child Care" <?php echo ($volunteer['preferredDepartment'] ?? '') === 'Child Care' ? 'selected' : ''; ?>>Child Care</option>
                            <option value="Education" <?php echo ($volunteer['preferredDepartment'] ?? '') === 'Education' ? 'selected' : ''; ?>>Education</option>
                            <option value="Health Services" <?php echo ($volunteer['preferredDepartment'] ?? '') === 'Health Services' ? 'selected' : ''; ?>>Health Services</option>
                            <option value="Kitchen" <?php echo ($volunteer['preferredDepartment'] ?? '') === 'Kitchen' ? 'selected' : ''; ?>>Kitchen</option>
                            <option value="Maintenance" <?php echo ($volunteer['preferredDepartment'] ?? '') === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="Events" <?php echo ($volunteer['preferredDepartment'] ?? '') === 'Events' ? 'selected' : ''; ?>>Events</option>
                            <option value="Administration" <?php echo ($volunteer['preferredDepartment'] ?? '') === 'Administration' ? 'selected' : ''; ?>>Administration</option>
                        </select>
                    </div>
                    <div>
                        <label for="volunteerStatus" class="block text-sm font-medium text-gray-700 mb-2">Volunteer Status</label>
                        <select id="volunteerStatus" name="volunteerStatus"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                            <option value="Active" <?php echo ($volunteer['volunteerStatus'] ?? 'Active') === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($volunteer['volunteerStatus'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="On Leave" <?php echo ($volunteer['volunteerStatus'] ?? '') === 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                        </select>
                    </div>
                    <div>
                        <label for="dateStarted" class="block text-sm font-medium text-gray-700 mb-2">Date Started</label>
                        <input type="date" id="dateStarted" name="dateStarted" value="<?php echo htmlspecialchars($volunteer['dateStarted'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="supervisor" class="block text-sm font-medium text-gray-700 mb-2">Supervisor</label>
                        <input type="text" id="supervisor" name="supervisor" value="<?php echo htmlspecialchars($volunteer['supervisor'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div class="md:col-span-2">
                        <label for="skillsAndExperience" class="block text-sm font-medium text-gray-700 mb-2">Skills & Experience</label>
                        <textarea id="skillsAndExperience" name="skillsAndExperience" rows="3"
                                  placeholder="Describe relevant skills, experience, and qualifications"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary"><?php echo htmlspecialchars($volunteer['skillsAndExperience'] ?? ''); ?></textarea>
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <label for="motivationForVolunteering" class="block text-sm font-medium text-gray-700 mb-2">Motivation for Volunteering</label>
                        <textarea id="motivationForVolunteering" name="motivationForVolunteering" rows="3"
                                  placeholder="Why do you want to volunteer with us?"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary"><?php echo htmlspecialchars($volunteer['motivationForVolunteering'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Availability -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Availability</h2>
                <div class="space-y-4">
                    <p class="text-sm text-gray-600">Select the days you are available to volunteer:</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                        <?php 
                        $availabilityDays = explode(', ', $volunteer['availability'] ?? '');
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($days as $day): 
                        ?>
                            <label class="flex items-center">
                                <input type="checkbox" name="availability[]" value="<?php echo $day; ?>" 
                                       <?php echo in_array($day, $availabilityDays) ? 'checked' : ''; ?>
                                       class="text-red-primary focus:ring-red-primary">
                                <span class="ml-2"><?php echo $day; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Emergency Contact</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label for="emergencyContactName" class="block text-sm font-medium text-gray-700 mb-2">Emergency Contact Name</label>
                        <input type="text" id="emergencyContactName" name="emergencyContactName" value="<?php echo htmlspecialchars($volunteer['emergencyContactName'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="emergencyContactRelationship" class="block text-sm font-medium text-gray-700 mb-2">Relationship</label>
                        <input type="text" id="emergencyContactRelationship" name="emergencyContactRelationship" value="<?php echo htmlspecialchars($volunteer['emergencyContactRelationship'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                    <div>
                        <label for="emergencyContactNumber" class="block text-sm font-medium text-gray-700 mb-2">Emergency Contact Number</label>
                        <input type="tel" id="emergencyContactNumber" name="emergencyContactNumber" value="<?php echo htmlspecialchars($volunteer['emergencyContactNumber'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-red-primary focus:border-red-primary">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4 pt-6">
                <a href="volunteer-records.php" class="w-full sm:w-auto px-6 py-3 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors text-center">
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
