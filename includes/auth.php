<?php
// Function to check if user has employee management permissions
function canManageEmployees()
{
    // Allow access to any logged-in user
    return isset($_SESSION['user_id']);
}

// Function to check if user can view other employees' attendance
function canViewOthersAttendance()
{
    // For now, using the same permissions as employee management
    // You can modify this to be more restrictive if needed
    return canManageEmployees();
}
