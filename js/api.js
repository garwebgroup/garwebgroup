const API_URL = 'api/index.php'; // Updated to point to the central API router

// Helper to get CSRF token
async function getCsrfToken() {
    try {
        const response = await fetch(API_URL + '?action=csrf');
        const data = await response.json();
        return data.csrf_token;
    } catch (e) {
        console.error("Failed to fetch CSRF token", e);
        return null;
    }
}

// ----- AUTHENTICATION LOGIC -----
const loginForm = document.getElementById('login-form');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        const msg = document.getElementById('login-message');
        
        try {
            const response = await fetch(API_URL + '?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ identifier: email, password: password })
            });
            const data = await response.json();
            
            if (data.success) {
                localStorage.setItem('hotel_user', JSON.stringify(data.user));
                if(data.token) localStorage.setItem('hotel_token', data.token);
                window.location.href = 'dashboard.html';
            } else {
                msg.textContent = data.message || 'Login failed. Please check your credentials.';
            }
        } catch (error) {
            msg.textContent = 'Error connecting to the server.';
            console.error(error);
        }
    });
}

const registerForm = document.getElementById('register-form');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('reg-name').value;
        const email = document.getElementById('reg-email').value;
        const password = document.getElementById('reg-password').value;
        const msg = document.getElementById('reg-message');
        
        const username = email.split('@')[0];

        try {
            const response = await fetch(API_URL + '?action=register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    username: username, 
                    email: email, 
                    password: password, 
                    full_name: name 
                })
            });
            const data = await response.json();
            
            if (data.success) {
                msg.style.color = '#577A7D';
                msg.textContent = 'Registration successful! You can now log in.';
                registerForm.reset();
            } else {
                msg.style.color = '#e74c3c';
                msg.textContent = data.message || 'Registration failed.';
            }
        } catch (error) {
            msg.style.color = '#e74c3c';
            msg.textContent = 'Error connecting to the server.';
            console.error(error);
        }
    });
}

// ----- CONTACT FORM LOGIC -----
const contactForm = document.getElementById('contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('contact-name').value;
        const email = document.getElementById('contact-email').value;
        const message = document.getElementById('contact-message').value;
        const responseMsg = document.getElementById('contact-response-msg');
        
        responseMsg.textContent = 'Sending...';
        responseMsg.style.color = 'var(--color-dark-teal)';

        try {
            const response = await fetch(API_URL + '?action=contact', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, message })
            });
            const data = await response.json();
            
            if (data.success) {
                responseMsg.style.color = '#577A7D';
                responseMsg.textContent = data.message || 'Message sent successfully!';
                contactForm.reset();
            } else {
                responseMsg.style.color = '#e74c3c';
                responseMsg.textContent = data.message || 'Failed to send message.';
            }
            
            setTimeout(() => {
                responseMsg.textContent = '';
            }, 5000);
        } catch (error) {
            responseMsg.style.color = '#e74c3c';
            responseMsg.textContent = 'Error connecting to the server.';
            console.error(error);
        }
    });
}


// ----- DYNAMIC DASHBOARD CRUD LOGIC -----
const tableSchemas = {
    employees: {
        title: "Employees Database",
        columns: [
            { key: "id", label: "ID", hiddenForm: true },
            { key: "full_name", label: "Full Name", type: "text" },
            { key: "position", label: "Position", type: "text" },
            { key: "department", label: "Department", type: "text" },
            { key: "email", label: "Email", type: "email" },
            { key: "phone", label: "Phone", type: "text" },
            { key: "created_at", label: "Created At", hiddenForm: true }
        ]
    },
    users: {
        title: "System Users",
        columns: [
            { key: "id", label: "ID", hiddenForm: true },
            { key: "username", label: "Username", type: "text" },
            { key: "email", label: "Email", type: "email" },
            { key: "password", label: "Password (Set New)", type: "password", hiddenTable: true },
            { key: "full_name", label: "Full Name", type: "text" },
            { key: "role", label: "Role", type: "text" },
            { key: "created_at", label: "Created At", hiddenForm: true }
        ]
    },
    company_profile: {
        title: "Company Profile",
        columns: [
            { key: "id", label: "ID", hiddenForm: true },
            { key: "company_name", label: "Company Name", type: "text" },
            { key: "description", label: "Description", type: "text" },
            { key: "mission", label: "Mission", type: "text" },
            { key: "vision", label: "Vision", type: "text" },
            { key: "founded_year", label: "Founded Year", type: "number" },
            { key: "logo_path", label: "Logo Path", type: "text" }
        ]
    },
    contact_messages: {
        title: "Contact Messages",
        columns: [
            { key: "id", label: "ID", hiddenForm: true },
            { key: "name", label: "Name", type: "text" },
            { key: "email", label: "Email", type: "email" },
            { key: "message", label: "Message", type: "text" },
            { key: "created_at", label: "Date", hiddenForm: true }
        ]
    },
    attendance: {
        title: "Attendance",
        columns: [
            { key: "id", label: "ID", hiddenForm: true },
            { key: "empid", label: "Employee ID", type: "number" },
            { key: "deptid", label: "Dept ID/Date", type: "text" },
            { key: "workstart", label: "Work Start", type: "text" },
            { key: "workend", label: "Work End", type: "text" },
            { key: "entrydate", label: "Entry Date", hiddenForm: true }
        ]
    },
    employeebank: {
        title: "Employee Bank",
        columns: [
            { key: "id", label: "ID", hiddenForm: true },
            { key: "name", label: "Bank Name", type: "text" }
        ]
    },
    helpdesk: {
        title: "Helpdesk Tickets",
        columns: [
            { key: "id", label: "ID", type: "number", hint: "Required if DB has no auto_increment" },
            { key: "name", label: "Name", type: "text" },
            { key: "complaint", label: "Complaint", type: "text" },
            { key: "assignedto", label: "Assigned To (ID)", type: "number" },
            { key: "entrydate", label: "Entry Date", type: "text" },
            { key: "status", label: "Status", type: "text" },
            { key: "feedback", label: "Feedback", type: "text" }
        ]
    },
    payroll: {
        title: "Payroll",
        columns: [
            { key: "id", label: "ID", hiddenForm: true },
            { key: "month", label: "Month", type: "text" },
            { key: "employee", label: "Employee ID", type: "number" },
            { key: "grosssalary", label: "Gross Salary", type: "number" },
            { key: "deductions", label: "Deductions", type: "number" },
            { key: "netsalary", label: "Net Salary", type: "number" },
            { key: "bank", label: "Bank ID", type: "number" },
            { key: "accountno", label: "Account No", type: "text" },
            { key: "ssnitid", label: "SSNIT ID", type: "text" },
            { key: "entrydate", label: "Entry Date", hiddenForm: true }
        ]
    }
};

let currentTable = 'employees';

const dynamicThead = document.getElementById('dynamic-thead');
const recordsTbody = document.getElementById('records-tbody');
const recordForm = document.getElementById('record-form');
const dynamicFormFields = document.getElementById('dynamic-form-fields');
const dashboardTitle = document.getElementById('dashboard-title');
const btnRetrieve = document.getElementById('btn-retrieve');

if (btnRetrieve) {
    btnRetrieve.addEventListener('click', fetchRecords);
}

// Render dynamic forms
function renderFormFields() {
    if (!dynamicFormFields) return;
    
    const schema = tableSchemas[currentTable];
    dynamicFormFields.innerHTML = '';
    
    schema.columns.forEach(col => {
        if (col.hiddenForm) return;
        
        const group = document.createElement('div');
        group.className = 'form-group';
        
        let label = `<label>${col.label}</label>`;
        if (col.hint) label = `<label>${col.label} <small>(${col.hint})</small></label>`;
        
        const input = `<input type="${col.type}" id="field-${col.key}" data-key="${col.key}" class="form-control" ${col.key !== 'password' ? 'required' : ''}>`;
        
        group.innerHTML = label + input;
        dynamicFormFields.appendChild(group);
    });
}

function renderTableHeaders() {
    if (!dynamicThead) return;
    const schema = tableSchemas[currentTable];
    
    let html = '<tr>';
    schema.columns.forEach(col => {
        if (col.hiddenTable) return;
        html += `<th>${col.label}</th>`;
    });
    html += '<th>Actions</th></tr>';
    
    dynamicThead.innerHTML = html;
}

// Global functions for inline HTML event handlers
window.changeTable = function(tableName, elem) {
    if(!tableSchemas[tableName]) return;
    
    currentTable = tableName;
    if(dashboardTitle) dashboardTitle.textContent = tableSchemas[tableName].title;
    
    // Update active class on sidebar
    document.querySelectorAll('#sidebar-tables a').forEach(a => a.classList.remove('active'));
    if(elem) elem.classList.add('active');
    
    // Reset forms and UI
    if(recordForm) {
        recordForm.reset();
        document.getElementById('record-id').value = '';
        document.getElementById('form-title').textContent = 'Add New Record';
        document.getElementById('btn-cancel').style.display = 'none';
        document.getElementById('form-message').textContent = '';
    }
    
    renderTableHeaders();
    renderFormFields();
    fetchRecords();
}

async function fetchRecords() {
    if(!recordsTbody) return;
    
    recordsTbody.innerHTML = '<tr><td colspan="100%" style="text-align:center;">Loading...</td></tr>';
    
    try {
        const cacheBuster = '&_t=' + new Date().getTime();
        const response = await fetch(API_URL + '?action=crud&table=' + currentTable + cacheBuster);
        const data = await response.json();
        
        const records = Array.isArray(data) ? data : (data.records || data.data || []);
        const schema = tableSchemas[currentTable];

        recordsTbody.innerHTML = '';
        
        if (records.length === 0) {
            recordsTbody.innerHTML = '<tr><td colspan="100%" style="text-align:center;">No records found.</td></tr>';
            return;
        }

        records.forEach(record => {
            const tr = document.createElement('tr');
            let tdHtml = '';
            
            schema.columns.forEach(col => {
                if (col.hiddenTable) return;
                const val = record[col.key] || '';
                // basic truncation for very long text
                const displayVal = val.length > 50 ? val.substring(0, 50) + '...' : val;
                tdHtml += `<td>${displayVal}</td>`;
            });
            
            tdHtml += `
                <td>
                    <button class="action-btn btn-edit" onclick='editRecord(${JSON.stringify(record).replace(/'/g, "&apos;")})'><i class="ph-bold ph-pencil-simple"></i> Edit</button>
                    <button class="action-btn btn-delete" onclick='deleteRecord(${record.id})'><i class="ph-bold ph-trash"></i> Delete</button>
                </td>
            `;
            
            tr.innerHTML = tdHtml;
            recordsTbody.appendChild(tr);
        });

    } catch (error) {
        console.error('Error fetching records:', error);
        recordsTbody.innerHTML = '<tr><td colspan="100%" style="text-align:center; color:#e74c3c;">Failed to load data.</td></tr>';
    }
}

if (recordForm) {
    recordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const id = document.getElementById('record-id').value;
        const msg = document.getElementById('form-message');
        const schema = tableSchemas[currentTable];
        
        const payload = {};
        let isValid = true;
        
        // Dynamic reading of fields
        schema.columns.forEach(col => {
            if (col.hiddenForm) return;
            const input = document.getElementById('field-' + col.key);
            if (input) {
                // Ignore empty passwords for update
                if (col.key === 'password' && id && !input.value) {
                    return; 
                }
                payload[col.key] = input.value;
            }
        });
        
        const isUpdate = !!id;
        const method = isUpdate ? 'PUT' : 'POST';
        let endpoint = API_URL + '?action=crud&table=' + currentTable;
        if (isUpdate) endpoint += '&id=' + id;
        
        msg.style.color = 'var(--color-dark-teal)';
        msg.textContent = 'Saving...';
        
        try {
            const csrfToken = await getCsrfToken();
            const response = await fetch(endpoint, {
                method: method,
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken || ''
                },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            
            if (data.success || response.ok) {
                msg.style.color = '#577A7D';
                msg.textContent = `Record successfully ${isUpdate ? 'updated' : 'added'}!`;
                recordForm.reset();
                document.getElementById('record-id').value = '';
                document.getElementById('form-title').textContent = 'Add New Record';
                document.getElementById('btn-cancel').style.display = 'none';
                fetchRecords();
                setTimeout(() => msg.textContent = '', 3000);
            } else {
                msg.style.color = '#e74c3c';
                msg.textContent = data.message || 'Operation failed.';
            }
        } catch (error) {
            msg.style.color = '#e74c3c';
            msg.textContent = 'Error saving record.';
            console.error(error);
        }
    });
    
    document.getElementById('btn-cancel').addEventListener('click', () => {
        recordForm.reset();
        document.getElementById('record-id').value = '';
        document.getElementById('form-title').textContent = 'Add New Record';
        document.getElementById('btn-cancel').style.display = 'none';
        document.getElementById('form-message').textContent = '';
    });
}

window.editRecord = function(record) {
    const schema = tableSchemas[currentTable];
    
    document.getElementById('record-id').value = record.id;
    
    schema.columns.forEach(col => {
        if (col.hiddenForm) return;
        const input = document.getElementById('field-' + col.key);
        if (input) {
            if (col.key === 'password') {
                input.value = ''; // Don't show hash, leave empty
            } else {
                input.value = record[col.key] || '';
            }
        }
    });
    
    document.getElementById('form-title').textContent = 'Update Record ID: ' + record.id;
    document.getElementById('btn-cancel').style.display = 'inline-block';
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.deleteRecord = async function(id) {
    if (confirm('Are you sure you want to delete this record?')) {
        try {
            const csrfToken = await getCsrfToken();
            const response = await fetch(API_URL + '?action=crud&table=' + currentTable + '&id=' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': csrfToken || ''
                }
            });
            const data = await response.json();
            
            if (data.success || response.ok) {
                alert('Record deleted successfully.');
                fetchRecords();
            } else {
                alert('Failed to delete record: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            alert('Error connecting to the server.');
            console.error(error);
        }
    }
}

// Initial UI setup on dashboard load
if (document.getElementById('dashboard-title')) {
    renderTableHeaders();
    renderFormFields();
    fetchRecords();
}
