$(document).ready(function () {
  // Load saved user type preference
  const savedType = localStorage.getItem('attendanceType');
  if (savedType) {
    document.querySelector(`input[name="attendanceType"][value="${savedType}"]`).checked = true;
  }

  // initializing current date
  const today = new Date().toISOString().split("T")[0];
  $("#attendanceDate").val(today);
  handleDateChange(document.getElementById('attendanceDate')); // Just update the date format display
  
  // Initialize with empty state message
  const tbody = document.getElementById('attendanceTableBody');
  tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Select date and type, then click Apply to load attendance data</td></tr>';
  
  // If single type is selected (default or from localStorage), load employee dropdown
  const selectedType = document.querySelector('input[name="attendanceType"]:checked');
  if (selectedType && selectedType.value === 'single') {
    loadAttendanceData();
  }

  // Handle user type change
  $('input[name="attendanceType"]').change(function() {
    const type = $(this).val();
    if (type === 'single') {
      loadAttendanceData();
    } else {
      // For 'all' type, reset to initial state until Apply is clicked
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Click Apply to load attendance data</td></tr>';
    }
  });
  
  // Apply filters button click handler - only needed for 'all' type now
  $("#applyFilters").click(function() {
    const type = document.querySelector('input[name="attendanceType"]:checked').value;
    if (type === 'all') {
      loadAttendanceData();
    }
  });

  // Date change handler - format the display date
  $("#attendanceDate").change(function () {
    handleDateChange(this);
    // Automatically reload data for single type
    const type = document.querySelector('input[name="attendanceType"]:checked').value;
    if (type === 'single') {
      loadAttendanceData();
    }
  });

  // Save button click handler
  $('.btn-save-attendance').on('click', function(e) {
    e.preventDefault();
    saveAttendance();
  });

  // Save attendance form submission
  $('#attendanceForm').on('submit', function(e) {
    e.preventDefault();
    saveAttendanceData();
  });
});

function updateRowStatus(row) {
  const checkIn = row.find(".check-in").prop("checked");
  const checkOut = row.find(".check-out").prop("checked");
  const statusCell = row.find(".status-cell .badge");

  if (checkIn && checkOut) {
    statusCell.removeClass().addClass("badge bg-success").text("Present");
  } else if (checkIn) {
    statusCell.removeClass().addClass("badge bg-primary").text("Checked In");
  } else if (checkOut) {
    statusCell.removeClass().addClass("badge bg-warning").text("Checked Out");
  } else {
    statusCell.removeClass().addClass("badge bg-secondary").text("Not Set");
  }
}

function loadAttendance(date) {
  //ajax call to load attendance data for the selected date
  $.ajax({
    url: "includes/get_attendance.php",
    type: "GET",
    data: { date: date },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        response.data.forEach(function (record) {
          const row = $(`tr[data-employee-id="${record.employee_id}"]`);
          if (record.check_in) {
            row.find(".check-in").prop("checked", true);
          }
          if (record.check_out) {
            row.find(".check-out").prop("checked", true);
          }
          updateRowStatus(row);
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Error loading attendance:", error);
    },
  });
}

function showLoader() {
    const loader = document.createElement('div');
    loader.className = 'loader-container';
    loader.innerHTML = `
        <div class="loader"></div>
        <div class="loader-text">One moment please...</div>
    `;
    document.body.appendChild(loader);
}

function hideLoader() {
    const loader = document.querySelector('.loader-container');
    if (loader) {
        loader.remove();
    }
}

function loadAttendanceData() {
    const date = document.getElementById('attendanceDate').value;
    const selectedType = document.querySelector('input[name="attendanceType"]:checked');
    const type = selectedType ? selectedType.value : 'single';
    localStorage.setItem('attendanceType', type);
    const tbody = document.getElementById('attendanceTableBody');
    
    // Show save button
    const saveBtnContainer = document.getElementById('saveBtnContainer');
    if (saveBtnContainer) {
        saveBtnContainer.style.display = 'block';
        const saveBtn = saveBtnContainer.querySelector('.btn-save-attendance');
        if (saveBtn) {
            saveBtn.innerHTML = '<i class="fas fa-save"></i> ' + (type === 'single' ? 'Save' : 'Save All');
            saveBtn.onclick = saveAttendance;
        }
    }
    
    showLoader();
    
    fetch(`includes/get_available_employees.php?date=${date}`)
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                tbody.innerHTML = '';
                
                if (!response.employees || response.employees.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">No employees available for attendance</td></tr>';
                    hideLoader();
                    return;
                }

                if (type === 'single') {
                    // For single type
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>1</td>
                        <td>
                            <select class="form-select form-select-sm single-employee-select">
                                <option value="">Select Employee</option>
                                ${response.employees.map(emp => `
                                    <option value="${emp.id}">${emp.name}</option>
                                `).join('')}
                            </select>
                        </td>
                        <td><input type="time" class="form-control form-control-sm" name="in_time"></td>
                        <td><input type="time" class="form-control form-control-sm" name="out_time"></td>
                        <td><textarea class="form-control form-control-sm" rows="2" name="comments" placeholder="Add comments..."></textarea></td>
                    `;
                    tbody.appendChild(tr);
                    
                    const employeeSelect = tr.querySelector('.single-employee-select');
                    employeeSelect.addEventListener('change', function() {
                        if (this.value) {
                            tr.setAttribute('data-employee-id', this.value);
                        }
                    });
                } else {
                    // For all type
                    response.employees.forEach((emp, index) => {
                        const tr = document.createElement('tr');
                        tr.setAttribute('data-employee-id', emp.id);
                        tr.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${emp.name}</td>
                            <td><input type="time" class="form-control form-control-sm" name="in_time"></td>
                            <td><input type="time" class="form-control form-control-sm" name="out_time"></td>
                            <td><textarea class="form-control form-control-sm" rows="2" name="comments" placeholder="Add comments..."></textarea></td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
                
                hideLoader();
            } else {
                throw new Error(response.message || 'Failed to load employees');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading employees: ' + error.message);
            hideLoader();
        });
}

// Global variables for tracking employees
let availableEmployees = [];

function loadAvailableEmployees() {
    const date = document.getElementById('attendanceDate').value;
    
    return fetch(`includes/get_available_employees.php?date=${date}&_=${new Date().getTime()}`)
        .then(response => response.json())
        .then(response => {
            if (response.status === 'success') {
                availableEmployees = response.data;
                return availableEmployees;
            } else {
                throw new Error(response.message || 'Failed to load employees');
            }
        });
}

function createEmployeeDropdown() {
    const select = document.createElement('select');
    select.className = 'form-select form-select-sm employee-select';
    select.innerHTML = '<option value="" disabled selected hidden></option>';
    
    // Add available employees
    availableEmployees.forEach(emp => {
        select.innerHTML += `<option value="${emp.id}">${emp.name}</option>`;
    });
    
    return select;
}

// Function to create single employee row with dropdown
function createSingleEmployeeRow() {
    const tr = document.createElement('tr');
    const employeeSelect = createEmployeeDropdown();
    employeeSelect.classList.add('single-employee-select');
    
    tr.innerHTML = `
        <td>1</td>
        <td></td>
        <td>
            <input type="time" class="form-control form-control-sm" name="in_time">
        </td>
        <td>
            <input type="time" class="form-control form-control-sm" name="out_time">
        </td>
        <td>
            <textarea class="form-control form-control-sm" rows="2" name="comments" placeholder="Add comments..."></textarea>
        </td>
    `;
    
    // Insert the dropdown into the second cell
    tr.cells[1].appendChild(employeeSelect);
    
    // Handle employee selection
    employeeSelect.addEventListener('change', function() {
        const selectedId = this.value;
        if (selectedId) {
            tr.setAttribute('data-employee-id', selectedId);
        }
    });
    
    return tr;
}

// Update the document ready function to properly bind the save button click event
$(document).ready(function () {
  // Load saved user type preference
  const savedType = localStorage.getItem('attendanceType');
  if (savedType) {
    document.querySelector(`input[name="attendanceType"][value="${savedType}"]`).checked = true;
  }

  // initializing current date
  const today = new Date().toISOString().split("T")[0];
  $("#attendanceDate").val(today);
  handleDateChange(document.getElementById('attendanceDate')); // Just update the date format display
  
  // Initialize with empty state message
  const tbody = document.getElementById('attendanceTableBody');
  tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Select date and type, then click Apply to load attendance data</td></tr>';
  
  // If single type is selected (default or from localStorage), load employee dropdown
  const selectedType = document.querySelector('input[name="attendanceType"]:checked');
  if (selectedType && selectedType.value === 'single') {
    loadAttendanceData();
  }

  // Handle user type change
  $('input[name="attendanceType"]').change(function() {
    const type = $(this).val();
    if (type === 'single') {
      loadAttendanceData();
    } else {
      // For 'all' type, reset to initial state until Apply is clicked
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Click Apply to load attendance data</td></tr>';
    }
  });
  
  // Apply filters button click handler - only needed for 'all' type now
  $("#applyFilters").click(function() {
    const type = document.querySelector('input[name="attendanceType"]:checked').value;
    if (type === 'all') {
      loadAttendanceData();
    }
  });

  // Date change handler - format the display date
  $("#attendanceDate").change(function () {
    handleDateChange(this);
    // Automatically reload data for single type
    const type = document.querySelector('input[name="attendanceType"]:checked').value;
    if (type === 'single') {
      loadAttendanceData();
    }
  });

  // Save button click handler
  $('.btn-save-attendance').on('click', function(e) {
    e.preventDefault();
    saveAttendance();
  });

  // Save attendance form submission
  $('#attendanceForm').on('submit', function(e) {
    e.preventDefault();
    saveAttendanceData();
  });
});

// Save attendance function
function saveAttendance() {
    const date = document.getElementById('attendanceDate').value;
    const tbody = document.getElementById('attendanceTableBody');
    const rows = tbody.getElementsByTagName('tr');
    const attendance = [];
    let hasData = false;

    // Store save button reference and original text
    const saveBtn = document.querySelector('.btn-save-attendance');
    const originalText = saveBtn.innerHTML;

    // Collect attendance data from all rows
    for (const row of rows) {
        const employeeId = row.getAttribute('data-employee-id');
        if (!employeeId) continue; // Skip rows without employee ID

        const inTime = row.querySelector('input[name="in_time"]')?.value;
        const outTime = row.querySelector('input[name="out_time"]')?.value;
        const comments = row.querySelector('textarea[name="comments"]')?.value || '';

        // Only validate and include rows where any data has been entered
        if (inTime || outTime || comments.trim()) {
            hasData = true;
            // For rows with any data, in_time is required
            if (!inTime) {
                const employeeName = row.cells[1].textContent.trim();
                alert(`Please enter in time for employee: ${employeeName}`);
                return;
            }

            // Combine date with times to create full datetime strings
            const inDateTime = `${date} ${inTime}:00`;
            const outDateTime = outTime ? `${date} ${outTime}:00` : null;

            attendance.push({
                employee_id: parseInt(employeeId),
                in_time: inDateTime,
                out_time: outDateTime,
                comments: comments.trim()
            });
        }
    }

    if (!hasData) {
        alert('Please enter attendance data for at least one employee');
        return;
    }

    // Show loading state
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;

    // Send data to server
    $.ajax({
        url: 'includes/save_attendance.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            date: date,
            attendance: attendance
        }),
        success: function(response) {
            // Reset button state first
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;

            if (response && (response.status === 'success' || response.success === true)) {
                alert('Attendance saved successfully!');
                // Reload data to show updated state
                loadAttendanceData();
            } else {
                alert(response.message || 'Failed to save attendance');
            }
        },
        error: function(xhr, status, error) {
            // Reset button state first
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;

            alert('Error saving attendance: ' + error);
        },
    });
}

// Save attendance data function
function saveAttendanceData() {
    // Get the submit button
    var $submitButton = $('.btn-save-attendance');
    var originalText = $submitButton.text();

    // Disable button and show saving state
    $submitButton.text('Saving...').prop('disabled', true);

    // Get form data
    var formData = new FormData(document.getElementById('attendanceForm'));
    var jsonData = {};
    formData.forEach((value, key) => {
        jsonData[key] = value;
    });

    // Make the AJAX call
    $.ajax({
        url: 'includes/save_attendance.php',
        type: 'POST',
        data: JSON.stringify(jsonData),
        contentType: 'application/json',
        dataType: 'json',
        beforeSend: function() {
            // Double-check button state
            $submitButton.text('Saving...').prop('disabled', true);
        },
        success: function(response) {
            // Reset button immediately
            $submitButton.text(originalText).prop('disabled', false);

            if (response.status === 'success') {
                toastr.success('Attendance saved successfully');
                // Small delay before reload
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                toastr.error(response.message || 'Failed to save attendance');
            }
        },
        error: function(xhr, status, error) {
            // Reset button and show error
            $submitButton.text(originalText).prop('disabled', false);
            toastr.error('An error occurred while saving attendance');
            console.error('Save error:', error);
        },
        complete: function() {
            // Final check to ensure button is reset
            if ($submitButton.text() === 'Saving...') {
                $submitButton.text(originalText).prop('disabled', false);
            }
        }
    });
}

// Helper functions to show success/error messages in the UI
function showSuccess(message) {
    const container = document.querySelector('.message-container') || createMessageContainer();
    container.innerHTML = `<div class="alert alert-success">${message}</div>`;
    container.style.display = 'block';
    setTimeout(() => {
        container.style.display = 'none';
    }, 5000);
}

function showError(message) {
    const container = document.querySelector('.message-container') || createMessageContainer();
    container.innerHTML = `<div class="alert alert-danger">${message}</div>`;
    container.style.display = 'block';
    setTimeout(() => {
        container.style.display = 'none';
    }, 5000);
}

function createMessageContainer() {
    const container = document.createElement('div');
    container.className = 'message-container';
    document.querySelector('.att-control-box').insertAdjacentElement('afterend', container);
    return container;
}

// Format date as "12 June 2025"
function formatDate(date) {
  const d = new Date(date);
  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function handleDateChange(input) {
  if (!input) return;
  const dateValue = input.value;
  
  // Update the hidden ISO date
  const isoInput = document.getElementById('attendanceDateISO');
  if (isoInput) {
    isoInput.value = dateValue;
  }
  
  // Format the visible date
  const formattedDate = formatDate(dateValue);
  $(input).attr('title', formattedDate); // Show formatted date on hover
  
  // Create or update the formatted date display
  let dateDisplay = input.parentElement.querySelector('.formatted-date');
  if (!dateDisplay) {
    dateDisplay = document.createElement('div');
    dateDisplay.className = 'formatted-date';
    input.parentElement.appendChild(dateDisplay);
  }
  dateDisplay.textContent = formattedDate;
}

// Add this to your document.ready function
$(document).ready(function() {
    // Initialize date
    const initialDate = $('#attendanceDate').val();
    handleDateChange(document.getElementById('attendanceDate'));
});
