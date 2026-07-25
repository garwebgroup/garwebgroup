// Base API URL
const API_URL = 'http://localhost/'; // TODO: Replace with your actual server IP later

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
                // token is in data.token if needed
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
        
        // Using email username part as username if not available
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

// ----- CRUD LOGIC FOR DASHBOARD (EMPLOYEES TABLE) -----
const recordsTbody = document.getElementById('records-tbody');
const recordForm = document.getElementById('record-form');
const btnRetrieve = document.getElementById('btn-retrieve');

// Load records on dashboard start
if (recordsTbody) {
    fetchRecords();
}

if (btnRetrieve) {
    btnRetrieve.addEventListener('click', fetchRecords);
}

async function fetchRecords() {
    try {
        const response = await fetch(API_URL + '?action=crud&table=employees');
        const data = await response.json();
        
        // The API might return an array directly or inside a property. Assuming it's an array or data.records/data.data
        const records = Array.isArray(data) ? data : (data.records || data.data || []);

        if (records) {
            recordsTbody.innerHTML = '';
            records.forEach(record => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${record.id}</td>
                    <td>${record.name || record.full_name || ''}</td>
                    <td>${record.email}</td>
                    <td>${record.department || ''}</td>
                    <td>${record.position || ''}</td>
                    <td>${record.phone || ''}</td>
                    <td>
                        <button class="action-btn btn-edit" onclick='editRecord(${JSON.stringify(record)})'><i class="ph-bold ph-pencil-simple"></i> Edit</button>
                        <button class="action-btn btn-delete" onclick='deleteRecord(${record.id})'><i class="ph-bold ph-trash"></i> Delete</button>
                    </td>
                `;
                recordsTbody.appendChild(tr);
            });
            
            if (records.length === 0) {
                recordsTbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No records found.</td></tr>';
            }
        }
    } catch (error) {
        console.error('Error fetching records:', error);
        recordsTbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#e74c3c;">Failed to load data from database. Ensure API base URL is correct.</td></tr>';
    }
}

if (recordForm) {
    recordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('record-id').value;
        const name = document.getElementById('record-name').value;
        const email = document.getElementById('record-email').value;
        const department = document.getElementById('record-department').value;
        const position = document.getElementById('record-position').value;
        const phone = document.getElementById('record-phone').value;
        const msg = document.getElementById('form-message');
        
        const isUpdate = !!id;
        const method = isUpdate ? 'PUT' : 'POST';
        let endpoint = API_URL + '?action=crud&table=employees';
        if (isUpdate) endpoint += '&id=' + id;
        
        try {
            const csrfToken = await getCsrfToken();
            if (!csrfToken) {
                msg.textContent = 'Failed to obtain security token.';
                return;
            }

            const payload = {
                name: name,
                email: email,
                department: department,
                position: position,
                phone: phone
            };

            const response = await fetch(endpoint, {
                method: method,
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            
            if (data.success || response.ok) {
                msg.textContent = `Record successfully ${isUpdate ? 'updated' : 'added'}!`;
                recordForm.reset();
                document.getElementById('record-id').value = '';
                document.getElementById('form-title').textContent = 'Add New Record';
                document.getElementById('btn-cancel').style.display = 'none';
                fetchRecords(); // Refresh table
                setTimeout(() => msg.textContent = '', 3000);
            } else {
                msg.textContent = data.message || 'Operation failed.';
            }
        } catch (error) {
            msg.textContent = 'Error saving record.';
            console.error(error);
        }
    });
    
    document.getElementById('btn-cancel').addEventListener('click', () => {
        recordForm.reset();
        document.getElementById('record-id').value = '';
        document.getElementById('form-title').textContent = 'Add New Record';
        document.getElementById('btn-cancel').style.display = 'none';
    });
}

// Global functions for inline HTML event handlers
window.editRecord = function(record) {
    document.getElementById('record-id').value = record.id;
    document.getElementById('record-name').value = record.name || record.full_name || '';
    document.getElementById('record-email').value = record.email || '';
    document.getElementById('record-department').value = record.department || '';
    document.getElementById('record-position').value = record.position || '';
    document.getElementById('record-phone').value = record.phone || '';
    
    document.getElementById('form-title').textContent = 'Update Record ID: ' + record.id;
    document.getElementById('btn-cancel').style.display = 'block';
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.deleteRecord = async function(id) {
    if (confirm('Are you sure you want to delete this record?')) {
        try {
            const csrfToken = await getCsrfToken();
            if (!csrfToken) {
                alert('Failed to obtain security token.');
                return;
            }

            const response = await fetch(API_URL + '?action=crud&table=employees&id=' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': csrfToken
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
